<?php
include 'db.php';

if(isset($_POST['login'])){

    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = mysqli_query($conn,
    "SELECT * FROM users WHERE email='$email'");

    $user = mysqli_fetch_assoc($query);

    if($user && password_verify($password, $user['password'])){

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['fullname'] = $user['fullname'];

        header("Location: dashboard.php");

    } else {
        $error = "Invalid Email or Password";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=3">
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

        <h1>Aloha's Store</h1>

        <?php if(isset($error)){ ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php } ?>

        <form method="POST" class="auth-form">
            <div class="auth-field">
                <span class="auth-field-icon" aria-hidden="true">◎</span>
                <input type="email" name="email" class="form-control" placeholder="Email" required>
            </div>

            <div class="auth-field">
                <span class="auth-field-icon" aria-hidden="true">▣</span>
                <input type="password" name="password" class="form-control" id="loginPassword" placeholder="Password" required>
                <button type="button" class="auth-password-toggle" id="passwordToggle" aria-label="Show password" aria-pressed="false">
                    <svg class="auth-eye-icon auth-eye-off-icon" aria-hidden="true" viewBox="0 0 24 24">
                        <path d="M3 3l18 18"></path>
                        <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>
                        <path d="M9.9 4.4A9.5 9.5 0 0 1 12 4c5 0 8.7 4.1 10 8a13.1 13.1 0 0 1-3 4.6"></path>
                        <path d="M6.5 6.7A13.4 13.4 0 0 0 2 12c1.3 3.9 5 8 10 8a9.7 9.7 0 0 0 4.1-.9"></path>
                    </svg>
                    <svg class="auth-eye-icon auth-eye-on-icon" aria-hidden="true" viewBox="0 0 24 24">
                        <path d="M2 12s3.7-7 10-7 10 7 10 7-3.7 7-10 7S2 12 2 12z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                </button>
            </div>

            <div class="auth-row auth-row-end">
                <a href="forgot_password.php" class="auth-muted-link">Forgot Password?</a>
            </div>

            <button type="submit" name="login" class="auth-submit">
                Login
            </button>
        </form>

        <div class="auth-divider">
            <span>or</span>
        </div>

        <p class="auth-switch">
            <span aria-hidden="true">♙</span>
            Don't have an account?
            <a href="register.php">Sign up</a>
        </p>
    </section>
</main>

<script>
const passwordInput = document.getElementById('loginPassword');
const passwordToggle = document.getElementById('passwordToggle');

passwordToggle.addEventListener('click', function () {
    const isPassword = passwordInput.type === 'password';
    passwordInput.type = isPassword ? 'text' : 'password';
    passwordToggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
    passwordToggle.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
});
</script>

</body>
</html>
