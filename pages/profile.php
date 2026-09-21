<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

session_start();
if (($_SESSION['account_type'] ?? '') !== 'user') {
    header('Location: user-login.php');
    exit;
}

$userId = (int) ($_SESSION['user_id'] ?? 0);
$user = null;
$error = '';

try {
    $connection = get_database_connection();
    $statement = $connection->prepare('SELECT full_name, email, created_at FROM users WHERE id = ? LIMIT 1');
    if (!$statement) {
        throw new RuntimeException('Unable to prepare the profile query.');
    }

    $statement->bind_param('i', $userId);
    if (!$statement->execute()) {
        throw new RuntimeException('Unable to load your profile.');
    }

    $result = $statement->get_result();
    $user = $result->fetch_assoc();
    $statement->close();
    $connection->close();

    if (!$user) {
        session_destroy();
        header('Location: user-login.php');
        exit;
    }

} catch (RuntimeException $exception) {
    $error = $exception->getMessage();
}

$userName = htmlspecialchars((string) ($user['full_name'] ?? $_SESSION['user_name'] ?? 'job seeker'), ENT_QUOTES, 'UTF-8');
$userEmail = htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8');
$joinedDate = $user !== null ? date('F j, Y', strtotime((string) $user['created_at'])) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="View your Worknest profile details.">
    <title>My profile | Worknest</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body class="panel-page user-panel-page">
    <header class="panel-header"><a class="brand" href="index.html"><span class="brand-mark"></span>worknest</a><div class="admin-menu"><button class="admin-menu-toggle" type="button" aria-expanded="false" aria-controls="user-menu-links"><span></span><span></span><span></span><span class="sr-only">Open user menu</span></button><nav class="admin-menu-links" id="user-menu-links" aria-label="User navigation"><div class="admin-menu-profile"><div class="menu-profile-heading"><div><span>Designation</span><strong>Job Seeker</strong><span>Name</span><strong><?php echo $userName; ?></strong></div></div></div><a href="user-panel.php">Dashboard</a><a href="profile.php">My profile</a><a href="job-listing.php">Browse jobs</a><a href="applied-jobs.php">Applied jobs</a><a href="logout.php">Sign out</a></nav></div></header>
    <main class="panel-main">
        <div class="panel-heading"><div><p class="eyebrow">Your account</p><h1>My profile</h1><p>Review the details you used to create your Worknest account.</p></div><a class="hero-button" href="user-panel.php">Back to dashboard <span>↗</span></a></div>
        <?php if ($error !== ''): ?><p class="form-error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php elseif ($user !== null): ?>
            <section class="panel-card profile-card" aria-labelledby="profile-details-heading">
                <div class="panel-card-heading"><div><p class="eyebrow">Account details</p><h2 id="profile-details-heading">Registration information</h2></div><span class="profile-status">Active account</span></div>
                <dl class="profile-details"><div><dt>Full name</dt><dd><?php echo $userName; ?></dd></div><div><dt>Email address</dt><dd><?php echo $userEmail; ?></dd></div><div><dt>Member since</dt><dd><?php echo htmlspecialchars($joinedDate, ENT_QUOTES, 'UTF-8'); ?></dd></div></dl>
            </section>
        <?php endif; ?>
    </main>
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
