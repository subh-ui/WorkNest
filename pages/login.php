<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

$error = '';
$registered = isset($_GET['registered']);
$registered = isset($_GET['registered']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Enter a valid email address and password.';
    } else {
        try {
            $connection = get_database_connection();
            $statement = $connection->prepare('SELECT id, full_name, phone_number, phone_verified, password_hash FROM users WHERE email = ? ORDER BY id DESC');
            if (!$statement) {
                throw new RuntimeException('Unable to prepare the login query.');
            }

            $statement->bind_param('s', $email);
            if (!$statement->execute()) {
                throw new RuntimeException('Unable to verify the login details.');
            }

            $result = $statement->get_result();
            $passwordIsValid = false;
            $userId = 0;
            $fullName = '';
            while ($user = $result->fetch_assoc()) {
                $candidatePasswordHash = (string) $user['password_hash'];
                $candidateIsValid = password_verify($password, $candidatePasswordHash);
                if (!$candidateIsValid && strlen($candidatePasswordHash) < 20 && hash_equals($candidatePasswordHash, $password)) {
                    $rehash = $connection->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                    $newPasswordHash = password_hash($password, PASSWORD_DEFAULT);
                    $candidateId = (int) $user['id'];
                    $rehash->bind_param('si', $newPasswordHash, $candidateId);
                    $candidateIsValid = $rehash->execute();
                    $rehash->close();
                }
                if ($candidateIsValid) {
                    $userId = (int) $user['id'];
                    $fullName = (string) $user['full_name'];
                    $passwordIsValid = true;
                    break;
                }
            }
            $statement->close();

            if ($passwordIsValid) {
                session_start();
                session_regenerate_id(true);
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_name'] = $fullName;
                $_SESSION['account_type'] = 'user';
                header('Location: user-panel.php');
                exit;
            }

            $error = 'The email or password is incorrect.';
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
    <meta name="description" content="Sign in to your Worknest account.">
    <title>Sign in | Worknest</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <main class="auth-shell">
        <section class="auth-intro"><a class="brand" href="index.html"><span class="brand-mark"></span>worknest</a><h1>Welcome back to your next chapter.</h1><p>Sign in to keep exploring roles that fit the life you are building.</p></section>
        <section class="auth-form"><a class="back-link" href="index.html">← Back to Worknest</a><h2>Sign in</h2><p class="subcopy">Use your Worknest account to continue.</p><?php if ($registered): ?><p class="form-success" role="status">Your account was created. You can sign in now.</p><?php endif; ?><?php if (isset($_GET['verified'])): ?><p class="form-success" role="status">Your phone is verified. You can sign in now.</p><?php endif; ?><?php if ($error !== ''): ?><p class="form-error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?><form method="post" action="login.php"><label for="email">Email address</label><input id="email" name="email" type="email" placeholder="you@example.com" required><label for="password">Password</label><input id="password" name="password" type="password" placeholder="Enter your password" required><button type="submit">Sign in</button></form><p class="switch-link">New to Worknest? <a href="user-register.php">Create an account</a></p><p class="switch-link"><a href="admin-login.php">I manage hiring for a team</a></p></section>
    </main>
</body>
</html>
