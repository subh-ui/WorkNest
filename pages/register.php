<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/phone.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phoneNumber = trim((string) ($_POST['phone_number'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $normalizedPhoneNumber = normalize_indian_phone($phoneNumber);

    if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $normalizedPhoneNumber === null || strlen($password) < 8) {
        $error = 'Enter your name, a valid email, and a valid Indian mobile number such as 9876543210 or +919876543210.';
    } else {
        try {
            $connection = get_database_connection();
            $existingStatement = $connection->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            if (!$existingStatement) {
                throw new RuntimeException('Unable to prepare the account check.');
            }
            $existingStatement->bind_param('s', $email);
            $existingStatement->execute();
            $existingStatement->store_result();
            if ($existingStatement->num_rows > 0) {
                $existingStatement->close();
                $connection->close();
                $error = 'An account with that email already exists.';
            } else {
                $existingStatement->close();
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $statement = $connection->prepare('INSERT INTO users (full_name, email, phone_number, phone_verified, password_hash) VALUES (?, ?, ?, 1, ?)');
                if (!$statement) {
                    throw new RuntimeException('Unable to prepare the account registration query.');
                }
                $statement->bind_param('ssss', $fullName, $email, $normalizedPhoneNumber, $passwordHash);

                if ($statement->execute()) {
                    $statement->close();
                    $connection->close();
                    header('Location: login.php?registered=1');
                    exit;
                }

                $error = $statement->errno === 1062 ? 'An account with that email already exists.' : 'We could not create your account: ' . $statement->error;
                $statement->close();
                $connection->close();
            }
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
    <meta name="description" content="Create your Worknest account.">
    <title>Register | Worknest</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <main class="auth-shell">
        <section class="auth-intro"><a class="brand" href="index.html"><span class="brand-mark"></span>worknest</a><h1>Make room for work that matters.</h1><p>Create your profile and start discovering opportunities shaped around your strengths.</p></section>
        <section class="auth-form"><a class="back-link" href="index.html">← Back to Worknest</a><h2>Create your account</h2><p class="subcopy">Join Worknest and start your next chapter.</p><?php if ($error !== ''): ?><p class="form-error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?><form method="post" action="register.php"><label for="full_name">Full name</label><input id="full_name" name="full_name" type="text" placeholder="Jane Smith" required><label for="email">Email address</label><input id="email" name="email" type="email" placeholder="you@example.com" required><label for="phone_number">Indian mobile number</label><input id="phone_number" name="phone_number" type="tel" placeholder="9876543210" pattern="(?:[6-9][0-9]{9}|\+91[6-9][0-9]{9})" maxlength="13" required><small>Use 10 digits or include +91.</small><label for="password">Create a password</label><input id="password" name="password" type="password" placeholder="At least 8 characters" minlength="8" required><button type="submit">Create account</button></form><p class="switch-link">Already have an account? <a href="user-login.php">Sign in</a></p><p class="switch-link"><a href="admin-register.php">Register as HR</a></p></section>
    </main>
</body>
</html>
