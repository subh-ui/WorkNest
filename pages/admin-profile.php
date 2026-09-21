<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

session_start();
if (($_SESSION['account_type'] ?? '') !== 'admin') {
    header('Location: admin-login.php');
    exit;
}

$adminId = (int) ($_SESSION['admin_id'] ?? 0);
$admin = null;
$error = '';

try {
    $connection = get_database_connection();
    $statement = $connection->prepare('SELECT full_name, designation, company_name, email, created_at FROM admins WHERE id = ? LIMIT 1');
    if (!$statement) {
        throw new RuntimeException('Unable to prepare the HR profile query.');
    }

    $statement->bind_param('i', $adminId);
    if (!$statement->execute()) {
        throw new RuntimeException('Unable to load your HR profile.');
    }

    $result = $statement->get_result();
    $admin = $result->fetch_assoc();
    $statement->close();
    $connection->close();

    if (!$admin) {
        session_destroy();
        header('Location: admin-login.php');
        exit;
    }

} catch (RuntimeException $exception) {
    $error = $exception->getMessage();
}

$adminName = htmlspecialchars((string) ($admin['full_name'] ?? $_SESSION['admin_name'] ?? 'HR manager'), ENT_QUOTES, 'UTF-8');
$designation = htmlspecialchars((string) ($admin['designation'] ?? $_SESSION['designation'] ?? 'HR manager'), ENT_QUOTES, 'UTF-8');
$companyName = htmlspecialchars((string) ($admin['company_name'] ?? $_SESSION['company_name'] ?? 'Your company'), ENT_QUOTES, 'UTF-8');
$adminEmail = htmlspecialchars((string) ($admin['email'] ?? ''), ENT_QUOTES, 'UTF-8');
$joinedDate = $admin !== null ? date('F j, Y', strtotime((string) $admin['created_at'])) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="View your Worknest HR profile details.">
    <title>HR profile | Worknest</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body class="panel-page">
    <header class="panel-header"><a class="brand" href="index.html"><span class="brand-mark"></span>worknest</a><div class="admin-menu"><button class="admin-menu-toggle" type="button" aria-expanded="false" aria-controls="admin-menu-links"><span></span><span></span><span></span><span class="sr-only">Open admin menu</span></button><nav class="admin-menu-links" id="admin-menu-links" aria-label="Admin navigation"><div class="admin-menu-profile"><div class="menu-profile-heading"><div><span>Company</span><strong><?php echo $companyName; ?></strong><span>Designation</span><strong><?php echo $designation; ?></strong><span>Name</span><strong><?php echo $adminName; ?></strong></div></div></div><a href="admin-panel.php">Dashboard</a><a href="admin-profile.php">My profile</a><a href="applicants.php">Applicants</a><a href="applicant-history.php">Job history</a><a href="admin-panel.php#post-role">Publish a new job role</a><a href="logout.php">Sign out</a></nav></div></header>
    <main class="panel-main">
        <div class="panel-heading"><div><p class="eyebrow">Employer account</p><h1>HR profile</h1><p>Review the details you used to create your Worknest employer account.</p></div><a class="hero-button" href="admin-panel.php">Back to dashboard <span>↗</span></a></div>
        <?php if ($error !== ''): ?><p class="form-error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php elseif ($admin !== null): ?>
            <section class="panel-card profile-card" aria-labelledby="profile-details-heading">
                <div class="panel-card-heading"><div><p class="eyebrow">Account details</p><h2 id="profile-details-heading">Registration information</h2></div><span class="profile-status">Active account</span></div>
                <dl class="profile-details"><div><dt>Full name</dt><dd><?php echo $adminName; ?></dd></div><div><dt>Designation</dt><dd><?php echo $designation; ?></dd></div><div><dt>Company name</dt><dd><?php echo $companyName; ?></dd></div><div><dt>Work email</dt><dd><?php echo $adminEmail; ?></dd></div><div><dt>Member since</dt><dd><?php echo htmlspecialchars($joinedDate, ENT_QUOTES, 'UTF-8'); ?></dd></div></dl>
            </section>
        <?php endif; ?>
    </main>
</body>
<script>
    const adminMenuToggle = document.querySelector('.admin-menu-toggle');
    const adminMenuLinks = document.querySelector('.admin-menu-links');

    if (adminMenuToggle && adminMenuLinks) {
        adminMenuToggle.addEventListener('click', () => {
            const isOpen = adminMenuLinks.classList.toggle('is-open');
            adminMenuToggle.setAttribute('aria-expanded', String(isOpen));
            adminMenuToggle.querySelector('.sr-only').textContent = isOpen ? 'Close admin menu' : 'Open admin menu';
        });

        adminMenuLinks.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => {
                adminMenuLinks.classList.remove('is-open');
                adminMenuToggle.setAttribute('aria-expanded', 'false');
                adminMenuToggle.querySelector('.sr-only').textContent = 'Open admin menu';
            });
        });
    }
</script>
</html>
