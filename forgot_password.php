<?php
include 'db.php';

$message = '';
$error = '';
$fromAdmin = isset($_GET['from']) && $_GET['from'] === 'admin';
$loginUrl = $fromAdmin ? 'login.php?open=admin' : 'login.php';

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

<nav class="auth-topbar" aria-label="Account actions">
    <a href="login.php">Login</a>
    <a href="login.php?open=admin">Admin</a>
</nav>

<div class="modal fade auth-modal" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="forgotPasswordModalLabel">Reset Password</h2>
                <a href="<?php echo htmlspecialchars($loginUrl); ?>" class="btn-close" aria-label="Close"></a>
            </div>

            <div class="modal-body">
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
                    <?php if ($fromAdmin) { ?>
                        <input type="hidden" name="from" value="admin">
                    <?php } ?>

                    <div class="auth-field">
                        <input type="email" name="email" class="form-control" placeholder="Email" required>
                    </div>

                    <div class="auth-field">
                        <input type="password" name="new_password" class="form-control login-password" placeholder="New Password" minlength="8" maxlength="12" required>
                        <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false">&#128053;</button>
                    </div>

                    <div class="auth-field">
                        <input type="password" name="confirm_password" class="form-control login-password" placeholder="Confirm Password" minlength="8" maxlength="12" required>
                        <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false">&#128053;</button>
                    </div>

                    <button type="submit" name="reset_password" class="auth-submit">
                        Reset Password
                    </button>

                    <div class="auth-row auth-row-center">
                        <span>Remember your password?</span>
                        <a href="<?php echo htmlspecialchars($loginUrl); ?>" class="auth-muted-link">Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
new bootstrap.Modal(document.getElementById('forgotPasswordModal'), {
    backdrop: 'static'
}).show();

document.querySelectorAll('.login-password').forEach(function (field) {
    field.addEventListener('input', function () {
        if (field.value.length > 12) {
            field.value = field.value.slice(0, 12);
        }
    });
});
</script>
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
