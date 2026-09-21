<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

session_start();
if (($_SESSION['account_type'] ?? '') !== 'admin') {
    header('Location: admin-login.php');
    exit;
}

$applications = [];
$error = '';
try {
    $connection = get_database_connection();
    $result = $connection->query("SELECT applications.created_at, applications.responded_at, applications.status, applications.cover_message, applications.admin_reply, jobs.title, jobs.company_name, users.full_name, users.email FROM applications INNER JOIN jobs ON jobs.id = applications.job_id INNER JOIN users ON users.id = applications.user_id WHERE applications.status <> 'New' ORDER BY applications.responded_at DESC, applications.id DESC");
    if ($result) {
        $applications = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
    }
    $connection->close();
} catch (RuntimeException $exception) {
    $error = $exception->getMessage();
}
$adminName = htmlspecialchars((string) ($_SESSION['admin_name'] ?? 'HR manager'), ENT_QUOTES, 'UTF-8');
$companyName = htmlspecialchars((string) ($_SESSION['company_name'] ?? 'Your company'), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant history | Worknest</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body class="panel-page">
    <header class="panel-header"><a class="brand" href="index.html"><span class="brand-mark"></span>worknest</a><div class="admin-menu"><button class="admin-menu-toggle" type="button" aria-expanded="false" aria-controls="admin-menu-links"><span></span><span></span><span></span><span class="sr-only">Open admin menu</span></button><nav class="admin-menu-links" id="admin-menu-links" aria-label="Admin navigation"><div class="admin-menu-profile"><span>Company</span><strong><?php echo $companyName; ?></strong><span>Designation</span><strong><?php echo $adminName; ?></strong></div><a href="applicants.php">Applicants</a><a href="applicant-history.php">Job history</a><a href="admin-panel.php#post-role">Publish a new job role</a><a href="logout.php">Sign out</a></nav></div></header>
    <main class="panel-main"><div class="panel-heading"><div><p class="eyebrow">Hiring records</p><h1>Applicant job history</h1><p>Review applications that have already been processed.</p></div><a class="back-link" href="admin-panel.php">← Back to HR panel</a></div><?php if ($error !== ''): ?><p class="form-error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?><section class="panel-card applicant-list"><div class="panel-card-heading"><div><p class="eyebrow">Processed applications</p><h2><?php echo count($applications); ?> history records</h2></div><a href="applicant-history.php">Refresh</a></div><?php foreach ($applications as $application): ?><article class="applicant-row"><div class="company-icon"><?php echo htmlspecialchars(strtoupper(substr($application['full_name'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?></div><div class="applicant-details"><strong><?php echo htmlspecialchars($application['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong><span><?php echo htmlspecialchars($application['email'], ENT_QUOTES, 'UTF-8'); ?></span><b><?php echo htmlspecialchars($application['status'] . ' · ' . $application['title'] . ' · ' . $application['company_name'], ENT_QUOTES, 'UTF-8'); ?></b><p><?php echo nl2br(htmlspecialchars($application['cover_message'], ENT_QUOTES, 'UTF-8')); ?></p><?php if ((string) ($application['admin_reply'] ?? '') !== ''): ?><p class="admin-reply"><strong>HR reply</strong><?php echo nl2br(htmlspecialchars($application['admin_reply'], ENT_QUOTES, 'UTF-8')); ?></p><?php endif; ?></div><small class="application-status"><?php echo htmlspecialchars(date('M j, Y', strtotime($application['responded_at'] ?? $application['created_at'])), ENT_QUOTES, 'UTF-8'); ?></small></article><?php endforeach; ?><?php if (!$applications): ?><p class="empty-state">No processed applications yet.</p><?php endif; ?></section></main>
</body>
<script>
    const adminMenuToggle = document.querySelector('.admin-menu-toggle');
    const adminMenuLinks = document.querySelector('.admin-menu-links');
    adminMenuToggle.addEventListener('click', () => {
        const isOpen = adminMenuLinks.classList.toggle('is-open');
        adminMenuToggle.setAttribute('aria-expanded', isOpen);
    });
    adminMenuLinks.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => adminMenuLinks.classList.remove('is-open')));
</script>
</html>
