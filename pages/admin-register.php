<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/phone.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $designation = trim((string) ($_POST['designation'] ?? ''));
    $companyName = trim((string) ($_POST['company_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phoneNumber = trim((string) ($_POST['phone_number'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $normalizedPhoneNumber = normalize_indian_phone($phoneNumber);

    if ($fullName === '' || $designation === '' || $companyName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $normalizedPhoneNumber === null || strlen($password) < 8) {
        $error = 'Enter your name, designation, company name, a valid work email, an Indian mobile number such as 9876543210, and a password with at least 8 characters.';
    } else {
        try {
            $connection = get_database_connection();
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $statement = $connection->prepare('INSERT INTO admins (full_name, designation, company_name, email, phone_number, phone_verified, password_hash) VALUES (?, ?, ?, ?, ?, 1, ?)');
            if (!$statement) {
                throw new RuntimeException('Unable to prepare the HR registration query.');
            }
            $statement->bind_param('ssssss', $fullName, $designation, $companyName, $email, $normalizedPhoneNumber, $passwordHash);
            if ($statement->execute()) {
                $statement->close();
                $connection->close();
                header('Location: admin-login.php?registered=1');
                exit;
            }
            $error = $statement->errno === 1062 ? 'An HR account with that email already exists.' : 'We could not create your HR account.';
            $statement->close();
            $connection->close();
        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Create a Worknest HR account.">
    <title>HR registration | Worknest</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <main class="auth-shell auth-admin">
        <section class="auth-intro"><a class="brand" href="index.html"><span class="brand-mark"></span>worknest</a><p class="portal-kicker">HR portal</p><h1>Bring the right people into the room.</h1><p>Create your employer account and start sharing roles with a thoughtful talent community.</p></section>
        <section class="auth-form"><a class="back-link" href="index.html">← Back to Worknest</a><h2>Register as HR</h2><p class="subcopy">Set up your employer account.</p><?php if ($error !== ''): ?><p class="form-error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?><form method="post"><label for="full_name">Your name</label><input id="full_name" name="full_name" type="text" placeholder="Jordan Lee" required><label for="designation">Designation</label><input id="designation" name="designation" type="text" placeholder="Hiring manager" maxlength="120" required><label for="company_name">Company name</label><input id="company_name" name="company_name" type="text" placeholder="Northstar Labs" maxlength="160" required><label for="email">Work email</label><input id="email" name="email" type="email" placeholder="hr@company.com" required><label for="phone_number">Indian mobile number</label><input id="phone_number" name="phone_number" type="tel" placeholder="9876543210" pattern="(?:[6-9][0-9]{9}|\+91[6-9][0-9]{9})" maxlength="13" required><small>Use 10 digits or include +91.</small><label for="password">Create a password</label><input id="password" name="password" type="password" placeholder="At least 8 characters" minlength="8" required><button type="submit">Create HR account</button></form><p class="switch-link">Already have an HR account? <a href="admin-login.php">Sign in</a></p><p class="switch-link"><a href="user-register.php">I am looking for a job</a></p></section>
    </main>
</body>
</html>
