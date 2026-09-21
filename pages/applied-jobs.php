<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

session_start();
if (($_SESSION['account_type'] ?? '') !== 'user') {
    header('Location: user-login.php');
    exit;
}

$applications = [];
$error = '';
$userId = (int) ($_SESSION['user_id'] ?? 0);
try {
    $connection = get_database_connection();
    $statement = $connection->prepare('SELECT applications.created_at, applications.status, applications.cover_message, applications.admin_reply, jobs.title, jobs.company_name, jobs.department, jobs.location, jobs.salary_range FROM applications INNER JOIN jobs ON jobs.id = applications.job_id WHERE applications.user_id = ? ORDER BY applications.created_at DESC, applications.id DESC');
    $statement->bind_param('i', $userId);
    $statement->execute();
    $result = $statement->get_result();
    $applications = $result->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    $connection->close();
} catch (RuntimeException $exception) {
    $error = $exception->getMessage();
}
$userName = htmlspecialchars((string) ($_SESSION['user_name'] ?? 'job seeker'), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applied jobs | Worknest</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body class="panel-page user-panel-page">
    <header class="panel-header"><a class="brand" href="index.html"><span class="brand-mark"></span>worknest</a><div class="panel-user"><span>Job seeker account</span><strong><?php echo $userName; ?></strong><a href="logout.php">Sign out</a></div></header>
    <main class="panel-main"><div class="panel-heading"><div><p class="eyebrow">Your job search</p><h1>Applied jobs</h1><p>Keep track of the roles you have sent applications for.</p></div><a class="hero-button" href="job-listing.php">Browse jobs <span>↗</span></a></div><?php if ($error !== ''): ?><p class="form-error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?><section class="panel-card applicant-list"><div class="panel-card-heading"><div><p class="eyebrow">Your applications</p><h2><?php echo count($applications); ?> applied roles</h2></div><a href="user-panel.php">Back to dashboard</a></div><?php foreach ($applications as $application): ?><article class="applied-job-row"><div><strong><?php echo htmlspecialchars($application['title'], ENT_QUOTES, 'UTF-8'); ?></strong><span><?php echo htmlspecialchars($application['company_name'] . ' · ' . $application['department'], ENT_QUOTES, 'UTF-8'); ?></span><small><?php echo htmlspecialchars($application['location'] . ' · ' . $application['salary_range'], ENT_QUOTES, 'UTF-8'); ?></small><?php if ((string) ($application['admin_reply'] ?? '') !== ''): ?><p class="admin-reply"><strong>Message from the hiring team</strong><?php echo nl2br(htmlspecialchars($application['admin_reply'], ENT_QUOTES, 'UTF-8')); ?></p><?php endif; ?></div><b><?php echo htmlspecialchars($application['status'], ENT_QUOTES, 'UTF-8'); ?><br><small><?php echo htmlspecialchars(date('M j, Y', strtotime($application['created_at'])), ENT_QUOTES, 'UTF-8'); ?></small></b></article><?php endforeach; ?><?php if (!$applications): ?><p class="empty-state">You have not applied for any roles yet.</p><?php endif; ?></section></main>
</body>
</html>
