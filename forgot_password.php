<?php
include 'db.php';

$message = '';
$error = '';

if (isset($_POST['reset_password'])) {
    $email = trim($_POST['email']);
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($newPassword) < 8 || strlen($newPassword) > 12) {
        $error = 'Password must be 8 to 12 characters.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        if (!$user) {
            $error = 'No account found with that email.';
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $update = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($update, "si", $hashedPassword, $user['id']);
            mysqli_stmt_execute($update);

            $message = 'Password updated successfully. You can now login.';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=4">
</head>

<body class="auth-page">

<main class="auth-shell">
    <section class="auth-box">
        <div class="auth-logo" aria-hidden="true">
            <div class="auth-logo-mark">
                <span class="auth-cart-basket"></span>
                <span class="auth-cart-handle"></span>
                <span class="auth-cart-wheel auth-cart-wheel-left"></span>
                <span class="auth-cart-wheel auth-cart-wheel-right"></span>
                <span class="auth-leaf auth-leaf-one"></span>
                <span class="auth-leaf auth-leaf-two"></span>
            </div>
        </div>

        <h1>Reset Password</h1>

        <?php if ($error !== '') { ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php } ?>

        <?php if ($message !== '') { ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php } ?>

        <form method="POST" class="auth-form">
            <div class="auth-field">
                <input type="email" name="email" class="form-control" placeholder="Email" required>
            </div>

            <div class="auth-field">
                <input type="password" name="new_password" class="form-control" placeholder="New Password" minlength="8" maxlength="12" required>
                <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false">&#128053;</button>
            </div>

            <div class="auth-field">
                <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" minlength="8" maxlength="12" required>
                <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false">&#128053;</button>
            </div>

            <button type="submit" name="reset_password" class="auth-submit">
                Reset Password
            </button>
        </form>

        <div class="auth-divider">
            <span>or</span>
        </div>

        <p class="auth-switch">
            Remember your password?
            <a href="login.php">Login</a>
        </p>
    </section>
</main>

<script>
document.querySelectorAll('.password-toggle').forEach(function (button) {
    button.addEventListener('click', function () {
        const field = button.closest('.auth-field').querySelector('input');
        const isHidden = field.type === 'password';

        field.type = isHidden ? 'text' : 'password';
        button.innerHTML = isHidden ? '&#128584;' : '&#128053;';
        button.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        button.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
    });
});
</script>

</body>
</html>
