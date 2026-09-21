<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

session_start();
if (($_SESSION['account_type'] ?? '') !== 'user') {
    header('Location: user-login.php');
    exit;
}

$jobId = filter_input(INPUT_GET, 'job_id', FILTER_VALIDATE_INT);
if (!$jobId) {
    header('Location: job-listing.php');
    exit;
}

$error = '';
$success = '';
$job = null;
$alreadyApplied = false;
$userId = (int) ($_SESSION['user_id'] ?? 0);

try {
    $connection = get_database_connection();
    $jobStatement = $connection->prepare('SELECT id, title, company_name, department, location, salary_range FROM jobs WHERE id = ? LIMIT 1');
    $jobStatement->bind_param('i', $jobId);
    $jobStatement->execute();
    $jobResult = $jobStatement->get_result();
    $job = $jobResult->fetch_assoc();
    $jobStatement->close();

    if (!$job) {
        $connection->close();
        header('Location: job-listing.php');
        exit;
    }

    $applicationStatement = $connection->prepare('SELECT id FROM applications WHERE job_id = ? AND user_id = ? LIMIT 1');
    $applicationStatement->bind_param('ii', $jobId, $userId);
    $applicationStatement->execute();
    $alreadyApplied = (bool) $applicationStatement->get_result()->fetch_assoc();
    $applicationStatement->close();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$alreadyApplied) {
        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $lastQualified = trim((string) ($_POST['last_qualified'] ?? ''));
        $experience = trim((string) ($_POST['experience'] ?? ''));
        $aboutMe = trim((string) ($_POST['about_me'] ?? ''));

        if ($firstName === '' || $lastName === '' || $email === '' || $phone === '' || $lastQualified === '' || $experience === '' || $aboutMe === '') {
            $error = 'Complete every field before sending your application.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid email address.';
        } elseif (strlen($aboutMe) < 20) {
            $error = 'Tell the employer a little more about yourself (at least 20 characters).';
        } else {
            $coverMessage = "First name: {$firstName}\nLast name: {$lastName}\nEmail: {$email}\nPhone: {$phone}\nLast qualified: {$lastQualified}\nExperience: {$experience}\n\nTell us about yourself:\n{$aboutMe}";

            $insert = $connection->prepare('INSERT INTO applications (job_id, user_id, cover_message) VALUES (?, ?, ?)');
            $insert->bind_param('iis', $jobId, $userId, $coverMessage);
            if ($insert->execute()) {
                $success = 'Your application was sent to the hiring team.';
                $alreadyApplied = true;
            } elseif ($insert->errno === 1062) {
                $alreadyApplied = true;
                $error = 'You have already applied for this role.';
            } else {
                $error = 'We could not send your application. Please try again.';
            }
            $insert->close();
        }
    }
    $connection->close();
} catch (RuntimeException $exception) {
    $error = $exception->getMessage();
}
$jobTitle = htmlspecialchars((string) ($job['title'] ?? 'Role'), ENT_QUOTES, 'UTF-8');
$companyName = htmlspecialchars((string) ($job['company_name'] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for <?php echo $jobTitle; ?> | Worknest</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body class="panel-page">
    <main class="auth-shell">
        <section class="auth-intro"><a class="brand" href="index.html"><span class="brand-mark"></span>worknest</a><p class="portal-kicker">Job application</p><h1><?php echo $jobTitle; ?></h1><p><?php echo $companyName; ?> is looking for someone thoughtful to join the team.</p></section>
        <section class="auth-form"><a class="back-link" href="job-listing.php">← Back to jobs</a><h2>Apply for this role</h2><p class="subcopy">Share a short note with the hiring team.</p><?php if ($error !== ''): ?><p class="form-error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?><?php if ($success !== ''): ?><p class="form-success" role="status"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?><?php if ($alreadyApplied && $success === ''): ?><p class="form-success" role="status">You have already applied for this role.</p><?php endif; ?><?php if (!$alreadyApplied): ?><form method="post" class="application-form"><div class="field-row"><div class="field-group"><label for="first_name">First name</label><input id="first_name" name="first_name" type="text" placeholder="John" required></div><div class="field-group"><label for="last_name">Last name</label><input id="last_name" name="last_name" type="text" placeholder="Doe" required></div></div><div class="field-row"><div class="field-group"><label for="email">Email</label><input id="email" name="email" type="email" placeholder="you@example.com" required></div><div class="field-group"><label for="phone">Phone number</label><input id="phone" name="phone" type="tel" placeholder="+1 234 567 890" required></div></div><div class="field-row"><div class="field-group"><label for="last_qualified">Last qualified</label><select id="last_qualified" name="last_qualified" required><option value="">Select</option><option value="High School">High School</option><option value="Diploma">Diploma</option><option value="Bachelor's Degree">Bachelor's Degree</option><option value="Master's Degree">Master's Degree</option><option value="PhD">PhD</option></select></div><div class="field-group"><label for="experience">Experience</label><select id="experience" name="experience" required><option value="">Select</option><option value="Fresher">Fresher</option><option value="1-2 years">1-2 years</option><option value="3-5 years">3-5 years</option><option value="5+ years">5+ years</option></select></div></div><div class="field-group field-group-full"><label for="about_me">Tell us about yourself</label><textarea id="about_me" name="about_me" rows="7" placeholder="Tell the hiring team about your skills, background, and interest in this role." required></textarea></div><button type="submit">Send application</button></form><?php endif; ?><p class="switch-link"><a href="applied-jobs.php">View my applied jobs</a></p></section>
    </main>
</body>
</html>
