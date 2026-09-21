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
$success = '';
try {
    $connection = get_database_connection();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $applicationId = (int) ($_POST['application_id'] ?? 0);
        $action = (string) ($_POST['action'] ?? '');
        if ($applicationId < 1) {
            $error = 'Invalid application selected.';
        } elseif ($action === 'reject') {
            $deleteStatement = $connection->prepare('DELETE FROM applications WHERE id = ?');
            $deleteStatement->bind_param('i', $applicationId);
            if ($deleteStatement->execute()) {
                $success = 'Application rejected and removed.';
            } else {
                $error = 'We could not remove this application.';
            }
            $deleteStatement->close();
        } elseif ($action === 'accept') {
            $statusStatement = $connection->prepare('UPDATE applications SET status = ?, responded_at = CURRENT_TIMESTAMP WHERE id = ?');
            $acceptedStatus = 'Accepted';
            $status = $acceptedStatus;
            $statusStatement->bind_param('si', $status, $applicationId);
            if ($statusStatement->execute()) {
                $success = 'Application accepted and removed from the pending list.';
            } else {
                $error = 'We could not update this application.';
            }
            $statusStatement->close();
        } elseif ($action === 'reply') {
            $reply = trim((string) ($_POST['admin_reply'] ?? ''));
            if ($reply === '') {
                $error = 'Write a reply before sending it.';
            } else {
                $replyStatement = $connection->prepare('UPDATE applications SET admin_reply = ?, responded_at = CURRENT_TIMESTAMP WHERE id = ?');
                $replyStatement->bind_param('si', $reply, $applicationId);
                if ($replyStatement->execute()) {
                    $success = 'Reply sent to the applicant.';
                } else {
                    $error = 'We could not send the reply.';
                }
                $replyStatement->close();
            }
        }
    }
    $result = $connection->query("SELECT applications.id, applications.created_at, applications.status, applications.cover_message, applications.admin_reply, jobs.title, jobs.company_name, users.full_name, users.email FROM applications INNER JOIN jobs ON jobs.id = applications.job_id INNER JOIN users ON users.id = applications.user_id WHERE applications.status = 'New' ORDER BY applications.created_at DESC, applications.id DESC");
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
    <meta http-equiv="refresh" content="15">
    <title>Applicants | Worknest</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body class="panel-page">
    <header class="panel-header"><a class="brand" href="index.html"><span class="brand-mark"></span>worknest</a><div class="admin-menu"><button class="admin-menu-toggle" type="button" aria-expanded="false" aria-controls="admin-menu-links"><span></span><span></span><span></span><span class="sr-only">Open admin menu</span></button><nav class="admin-menu-links" id="admin-menu-links" aria-label="Admin navigation"><div class="admin-menu-profile"><span>Company</span><strong><?php echo $companyName; ?></strong><span>Designation</span><strong><?php echo $adminName; ?></strong></div><a href="applicants.php">Applicants <strong><?php echo count($applications); ?></strong></a><a href="applicant-history.php">Job history</a><a href="admin-panel.php#post-role">Publish a new job role</a><a href="logout.php">Sign out</a></nav></div></header>
    <main class="panel-main"><div class="panel-heading"><div><p class="eyebrow">Hiring pipeline</p><h1>Applicants</h1><p>Every application appears here as soon as it is submitted.</p></div><div class="panel-heading-links"><a class="back-link" href="applicant-history.php">View job history</a><a class="back-link" href="admin-panel.php">← Back to HR panel</a></div></div><?php if ($error !== ''): ?><p class="form-error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?><?php if ($success !== ''): ?><p class="form-success" role="status"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?><section class="panel-card applicant-list"><div class="panel-card-heading"><div><p class="eyebrow">Live applications</p><h2><?php echo count($applications); ?> applicants</h2></div><a href="applicants.php">Refresh</a></div><?php foreach ($applications as $application): ?><article class="applicant-row"><div class="company-icon"><?php echo htmlspecialchars(strtoupper(substr($application['full_name'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?></div><div class="applicant-details"><strong><?php echo htmlspecialchars($application['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong><span><?php echo htmlspecialchars($application['email'], ENT_QUOTES, 'UTF-8'); ?></span><b>Applied for <?php echo htmlspecialchars($application['title'] . ' · ' . $application['company_name'], ENT_QUOTES, 'UTF-8'); ?></b><p><?php echo nl2br(htmlspecialchars($application['cover_message'], ENT_QUOTES, 'UTF-8')); ?></p><div class="applicant-actions"><form method="post"><input type="hidden" name="application_id" value="<?php echo (int) $application['id']; ?>"><button class="accept-button" name="action" value="accept" type="submit">Accept</button><button class="reject-button" name="action" value="reject" type="submit">Reject</button></form><form method="post" class="reply-form"><input type="hidden" name="application_id" value="<?php echo (int) $application['id']; ?>"><input type="hidden" name="action" value="reply"><label for="reply-<?php echo (int) $application['id']; ?>">Reply to applicant</label><textarea id="reply-<?php echo (int) $application['id']; ?>" name="admin_reply" rows="3" placeholder="Write a message to the applicant."><?php echo htmlspecialchars((string) ($application['admin_reply'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea><button type="submit">Send reply</button></form></div></div><small class="application-status"><?php echo htmlspecialchars($application['status'], ENT_QUOTES, 'UTF-8'); ?><br><?php echo htmlspecialchars(date('M j, Y', strtotime($application['created_at'])), ENT_QUOTES, 'UTF-8'); ?></small></article><?php endforeach; ?><?php if (!$applications): ?><p class="empty-state">No applications yet. New applicants will appear here automatically.</p><?php endif; ?></section></main>
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
