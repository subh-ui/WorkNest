<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

session_start();
if (($_SESSION['account_type'] ?? '') !== 'admin') {
    header('Location: admin-login.php');
    exit;
}
$error = '';
$success = '';
$jobs = [];
$applicationCount = 0;
$adminId = (int) ($_SESSION['admin_id'] ?? 0);

try {
    $connection = get_database_connection();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $department = trim((string) ($_POST['department'] ?? ''));
        $location = trim((string) ($_POST['location'] ?? ''));
        $salaryRange = trim((string) ($_POST['salary_range'] ?? ''));

        if ($title === '' || $department === '' || $location === '' || $salaryRange === '') {
            $error = 'Complete every field before posting the role.';
        } else {
            $statement = $connection->prepare('INSERT INTO jobs (admin_id, title, company_name, department, location, salary_range) VALUES (?, ?, ?, ?, ?, ?)');
            if (!$statement) {
                throw new RuntimeException('Unable to prepare the role posting query.');
            }
            $companyNameValue = (string) ($_SESSION['company_name'] ?? 'Your company');
            $statement->bind_param('isssss', $adminId, $title, $companyNameValue, $department, $location, $salaryRange);
            if (!$statement->execute()) {
                throw new RuntimeException('We could not post this role.');
            }
            $statement->close();
            $success = 'Role posted. It is now visible on the user job board.';
        }
    }

    $result = $connection->query('SELECT title, company_name, location FROM jobs ORDER BY created_at DESC, id DESC LIMIT 6');
    if ($result) {
        $jobs = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
    }
    $applicationResult = $connection->query("SELECT COUNT(*) AS total FROM applications WHERE status = 'New'");
    if ($applicationResult) {
        $applicationCount = (int) $applicationResult->fetch_assoc()['total'];
        $applicationResult->free();
    }
    $connection->close();
} catch (RuntimeException $exception) {
    $error = $exception->getMessage();
}
$adminName = htmlspecialchars((string) ($_SESSION['admin_name'] ?? 'HR manager'), ENT_QUOTES, 'UTF-8');
$designation = htmlspecialchars((string) ($_SESSION['designation'] ?? 'HR manager'), ENT_QUOTES, 'UTF-8');
$companyName = htmlspecialchars((string) ($_SESSION['company_name'] ?? 'Your company'), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR panel | Worknest</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body class="panel-page">
    <header class="panel-header"><a class="brand" href="index.html"><span class="brand-mark"></span>worknest</a><div class="admin-menu"><button class="admin-menu-toggle" type="button" aria-expanded="false" aria-controls="admin-menu-links"><span></span><span></span><span></span><span class="sr-only">Open admin menu</span></button><nav class="admin-menu-links" id="admin-menu-links" aria-label="Admin navigation"><div class="admin-menu-profile"><div class="menu-profile-heading"><div><span>Company</span><strong><?php echo $companyName; ?></strong><span>Designation</span><strong><?php echo $designation; ?></strong><span>Name</span><strong><?php echo $adminName; ?></strong></div></div></div><a href="admin-profile.php">My profile</a><a href="applicants.php">Applicants <strong><?php echo $applicationCount; ?></strong></a><a href="applicant-history.php">Job history</a><a href="#post-role">Publish a new job role</a><a href="logout.php">Sign out</a></nav></div></header>
    <main class="panel-main"><div class="panel-heading"><div><p class="eyebrow">Employer workspace</p><h1>Good morning, <?php echo $adminName; ?>.</h1><p>Keep your hiring pipeline moving with a clear view of your team needs.</p></div><a class="hero-button" href="#post-role">Post a new role <span>+</span></a></div><section class="panel-stats"><article><span>Active roles</span><strong><?php echo count($jobs); ?></strong><small>Available to job seekers</small></article><article><span>Applications</span><strong><?php echo $applicationCount; ?></strong><small><a href="applicants.php">View applicants</a></small></article><article><span>Shortlisted</span><strong>38</strong><small>Ready for review</small></article></section><?php if ($error !== ''): ?><p class="form-error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?><?php if ($success !== ''): ?><p class="form-success" role="status"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?><section class="panel-grid"><article class="panel-card"><div class="panel-card-heading"><div><p class="eyebrow">Your workspace</p><h2>Recent roles</h2></div><a href="job-listing.php">View all</a></div><?php foreach ($jobs as $job): ?><div class="role-row"><div class="company-icon"><?php echo htmlspecialchars(strtoupper(substr($job['company_name'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?></div><div><strong><?php echo htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8'); ?></strong><span><?php echo htmlspecialchars($job['company_name'] . ' · ' . $job['location'], ENT_QUOTES, 'UTF-8'); ?></span></div><b>Active</b></div><?php endforeach; ?></article><article class="panel-card panel-card-accent" id="post-role"><p class="eyebrow">Share an opportunity</p><h2>Post a new role</h2><p>New postings appear on the user job board as soon as they are saved.</p><form method="post"><label for="title">Role title</label><input id="title" name="title" type="text" maxlength="160" required><label for="department">Department</label><input id="department" name="department" type="text" maxlength="100" required><label for="location">Work location</label><input id="location" name="location" type="text" maxlength="100" placeholder="Remote, Hybrid, or On-site" required><label for="salary_range">Salary range</label><input id="salary_range" name="salary_range" type="text" maxlength="100" placeholder="$90k - $120k" required><button type="submit">Publish role</button></form></article></section></main>
</body>
<script>
    const adminMenuToggle = document.querySelector('.admin-menu-toggle');
    const adminMenuLinks = document.querySelector('.admin-menu-links');

    adminMenuToggle.addEventListener('click', () => {
        const isOpen = adminMenuLinks.classList.toggle('is-open');
        adminMenuToggle.setAttribute('aria-expanded', isOpen);
        adminMenuToggle.querySelector('.sr-only').textContent = isOpen ? 'Close admin menu' : 'Open admin menu';
    });

    adminMenuLinks.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            adminMenuLinks.classList.remove('is-open');
            adminMenuToggle.setAttribute('aria-expanded', 'false');
            adminMenuToggle.querySelector('.sr-only').textContent = 'Open admin menu';
        });
    });
</script>
</html>
