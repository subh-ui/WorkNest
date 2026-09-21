<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

$jobs = [];
$error = '';
try {
    $connection = get_database_connection();
    $result = $connection->query('SELECT id, title, company_name, department, location, salary_range FROM jobs ORDER BY created_at DESC, id DESC');
    if ($result) {
        $jobs = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
    }
    $connection->close();
} catch (RuntimeException $exception) {
    $error = $exception->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Explore open roles on Worknest.">
    <title>Find jobs | Worknest</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body class="listing-page">
    <header class="header"><a class="brand" href="index.html"><span class="brand-mark"></span>worknest</a><a class="back-link" href="index.html">← Back home</a></header>
    <main class="listing-main">
        <section class="listing-hero"><p class="eyebrow">Your next opportunity</p><h1>Find your next role.</h1><p class="listing-summary">Explore <?php echo count($jobs); ?> open positions from teams building useful things. Apply to the role that fits your next chapter.</p></section>
        <?php if ($error !== ''): ?><p class="form-error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
        <section class="jobs-directory" aria-label="Available jobs">
            <?php foreach ($jobs as $job): ?><article class="directory-job"><div class="directory-job-heading"><div class="company-icon"><?php echo htmlspecialchars(strtoupper(substr($job['company_name'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?></div><div><h2><?php echo htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8'); ?></h2><p><?php echo htmlspecialchars($job['company_name'] . ' · ' . $job['department'], ENT_QUOTES, 'UTF-8'); ?></p></div></div><div class="directory-job-meta"><span><?php echo htmlspecialchars($job['location'], ENT_QUOTES, 'UTF-8'); ?></span><span><?php echo htmlspecialchars($job['salary_range'], ENT_QUOTES, 'UTF-8'); ?></span></div><a class="apply-button" href="apply.php?job_id=<?php echo (int) $job['id']; ?>">Apply ↗</a></article><?php endforeach; ?>
        </section>
    </main>
</body>
</html>
