<?php
include 'db.php';

if(isset($_POST['register'])){

    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $plainPassword = $_POST['password'];

    if (strlen($plainPassword) < 8 || strlen($plainPassword) > 12) {
        $register_error = "Password must be 8 to 12 characters.";
    } else {
        $password = password_hash($plainPassword, PASSWORD_DEFAULT);
        $userCountQuery = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
        $userCount = mysqli_fetch_assoc($userCountQuery);
        $role = (int) $userCount['total'] === 0 ? 'admin' : 'user';

        mysqli_query($conn,
        "INSERT INTO users(fullname,email,password,role)
        VALUES('$fullname','$email','$password','$role')");

        header("Location: login.php");
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
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

        <h1>Sign up</h1>
        <p class="auth-subtitle">Create your account to manage inventory</p>

        <?php if (isset($register_error)) { ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($register_error); ?>
            </div>
        <?php } ?>

        <form method="POST" class="auth-form">
            <div class="auth-field">
                <input type="text" name="fullname" class="form-control" placeholder="Full Name" required>
            </div>

            <div class="auth-field">
                <input type="email" name="email" class="form-control" placeholder="Email" required>
            </div>

            <div class="auth-field">
                <input type="password" name="password" class="form-control" placeholder="Password" minlength="8" maxlength="12" required>
                <button type="button" class="password-toggle" aria-label="Show password" aria-pressed="false">&#128053;</button>
            </div>

            <button type="submit" name="register" class="auth-submit">
                Register
            </button>
        </form>

        <div class="auth-divider">
            <span>or</span>
        </div>

        <p class="auth-switch">
            Already have an account?
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
