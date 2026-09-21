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
        $error = 'Enter a valid HR email address and password.';
    } else {
        try {
            $connection = get_database_connection();
            $statement = $connection->prepare('SELECT id, full_name, designation, company_name, phone_number, phone_verified, password_hash FROM admins WHERE email = ? ORDER BY id DESC');
            if (!$statement) {
                throw new RuntimeException('Unable to prepare the HR login query.');
            }
            $statement->bind_param('s', $email);
            $statement->execute();
            $result = $statement->get_result();
            $passwordIsValid = false;
            $adminId = 0;
            $fullName = '';
            $designation = '';
            $companyName = '';
            while ($admin = $result->fetch_assoc()) {
                $candidatePasswordHash = (string) $admin['password_hash'];
                $candidateIsValid = password_verify($password, $candidatePasswordHash);
                if (!$candidateIsValid && strlen($candidatePasswordHash) < 20 && hash_equals($candidatePasswordHash, $password)) {
                    $rehash = $connection->prepare('UPDATE admins SET password_hash = ? WHERE id = ?');
                    $newPasswordHash = password_hash($password, PASSWORD_DEFAULT);
                    $candidateId = (int) $admin['id'];
                    $rehash->bind_param('si', $newPasswordHash, $candidateId);
                    $candidateIsValid = $rehash->execute();
                    $rehash->close();
                }
                if ($candidateIsValid) {
                    $adminId = (int) $admin['id'];
                    $fullName = (string) $admin['full_name'];
                    $designation = (string) $admin['designation'];
                    $companyName = (string) $admin['company_name'];
                    $passwordIsValid = true;
                    break;
                }
            }
            $statement->close();

            if ($passwordIsValid) {
                session_start();
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $adminId;
                $_SESSION['admin_name'] = $fullName;
                $_SESSION['designation'] = $designation;
                $_SESSION['company_name'] = $companyName;
                $_SESSION['account_type'] = 'admin';
                header('Location: admin-panel.php');
                exit;
            }
            $error = 'The HR email or password is incorrect.';
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
    <meta name="description" content="Sign in to the Worknest HR portal.">
    <title>HR sign in | Worknest</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <main class="auth-shell auth-admin">
        <section class="auth-intro"><a class="brand" href="index.html"><span class="brand-mark"></span>worknest</a><p class="portal-kicker">HR portal</p><h1>Build the teams shaping tomorrow.</h1><p>Manage your open roles and connect with people ready for their next chapter.</p></section>
        <section class="auth-form"><a class="back-link" href="index.html">← Back to Worknest</a><h2>HR sign in</h2><p class="subcopy">Use your employer account to continue.</p><?php if ($registered): ?><p class="form-success" role="status">Your HR account was created. You can sign in now.</p><?php endif; ?><?php if (isset($_GET['verified'])): ?><p class="form-success" role="status">Your phone is verified. You can sign in now.</p><?php endif; ?><?php if ($error !== ''): ?><p class="form-error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?><form method="post"><label for="email">Work email</label><input id="email" name="email" type="email" placeholder="hr@company.com" required><label for="password">Password</label><input id="password" name="password" type="password" placeholder="Enter your password" required><button type="submit">Open HR panel</button></form><p class="switch-link">New HR account? <a href="admin-register.php">Register your company</a></p><p class="switch-link"><a href="user-login.php">I am looking for a job</a></p></section>
    </main>
</body>
</html>
