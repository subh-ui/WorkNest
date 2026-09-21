<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

session_start();
if (($_SESSION['account_type'] ?? '') !== 'user') {
    header('Location: user-login.php');
    exit;
}
$userName = htmlspecialchars((string) ($_SESSION['user_name'] ?? 'job seeker'), ENT_QUOTES, 'UTF-8');
$jobs = [];
$applicationCount = 0;
$userId = (int) ($_SESSION['user_id'] ?? 0);
try {
    $connection = get_database_connection();
    $result = $connection->query('SELECT title, company_name, location FROM jobs ORDER BY created_at DESC, id DESC LIMIT 3');
    if ($result) {
        $jobs = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
    }
    $applicationStatement = $connection->prepare('SELECT COUNT(*) AS total FROM applications WHERE user_id = ?');
    $applicationStatement->bind_param('i', $userId);
    $applicationStatement->execute();
    $applicationCount = (int) $applicationStatement->get_result()->fetch_assoc()['total'];
    $applicationStatement->close();
    $connection->close();
} catch (RuntimeException $exception) {
    $jobs = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My job search | Worknest</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body class="panel-page user-panel-page">
    <header class="panel-header"><a class="brand" href="index.html"><span class="brand-mark"></span>worknest</a><div class="admin-menu"><button class="admin-menu-toggle" type="button" aria-expanded="false" aria-controls="user-menu-links"><span></span><span></span><span></span><span class="sr-only">Open user menu</span></button><nav class="admin-menu-links" id="user-menu-links" aria-label="User navigation"><div class="admin-menu-profile"><div class="menu-profile-heading"><div><span>Designation</span><strong>Job Seeker</strong><span>Name</span><strong><?php echo $userName; ?></strong></div></div></div><a href="profile.php">My profile</a><a href="job-listing.php">Browse jobs</a><a href="applied-jobs.php">Applied jobs <strong><?php echo $applicationCount; ?></strong></a><a href="logout.php">Sign out</a></nav></div></header>
    <main class="panel-main"><div class="panel-heading"><div><p class="eyebrow">Your job search</p><h1>Find a role that fits, <?php echo $userName; ?>.</h1><p>Pick up where you left off or explore fresh opportunities from thoughtful teams.</p></div><a class="hero-button" href="job-listing.php">Browse jobs <span>↗</span></a></div><section class="panel-stats"><article><span>Saved roles</span><strong>6</strong><small>2 closing soon</small></article><article><span>Applications</span><strong><?php echo $applicationCount; ?></strong><small><a href="applied-jobs.php">View applied jobs</a></small></article><article><span>Profile strength</span><strong>78%</strong><small>Almost complete</small></article></section><section class="panel-grid"><article class="panel-card"><div class="panel-card-heading"><div><p class="eyebrow">Latest opportunities</p><h2>Open opportunities</h2></div><a href="job-listing.php">See all</a></div><?php foreach ($jobs as $job): ?><div class="role-row"><div class="company-icon"><?php echo htmlspecialchars(strtoupper(substr($job['company_name'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?></div><div><strong><?php echo htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8'); ?></strong><span><?php echo htmlspecialchars($job['company_name'] . ' · ' . $job['location'], ENT_QUOTES, 'UTF-8'); ?></span></div><a href="job-listing.php">View</a></div><?php endforeach; ?></article><article class="panel-card panel-card-accent"><p class="eyebrow">Keep going</p><h2>Make your profile work harder.</h2><p>Add your skills and experience to get more relevant role recommendations.</p><a class="apply-button" href="applied-jobs.php">View applied jobs ↗</a></article></section></main>
</body>
<script>
    const userMenuToggle = document.querySelector('.admin-menu-toggle');
    const userMenuLinks = document.querySelector('.admin-menu-links');

    if (userMenuToggle && userMenuLinks) {
        userMenuToggle.addEventListener('click', () => {
            const isOpen = userMenuLinks.classList.toggle('is-open');
            userMenuToggle.setAttribute('aria-expanded', String(isOpen));
            userMenuToggle.querySelector('.sr-only').textContent = isOpen ? 'Close user menu' : 'Open user menu';
        });

        userMenuLinks.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => {
                userMenuLinks.classList.remove('is-open');
                userMenuToggle.setAttribute('aria-expanded', 'false');
                userMenuToggle.querySelector('.sr-only').textContent = 'Open user menu';
            });
        });
    }
</script>
</html>
