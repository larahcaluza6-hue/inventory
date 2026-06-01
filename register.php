<?php
include 'db.php';

if(isset($_POST['register'])){

    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    mysqli_query($conn,
    "INSERT INTO users(fullname,email,password)
    VALUES('$fullname','$email','$password')");

    header("Location: login.php");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
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

        <form method="POST" class="auth-form">
            <div class="auth-field">
                <span class="auth-field-icon" aria-hidden="true">♙</span>
                <input type="text" name="fullname" class="form-control" placeholder="Full Name" required>
            </div>

            <div class="auth-field">
                <span class="auth-field-icon" aria-hidden="true">◎</span>
                <input type="email" name="email" class="form-control" placeholder="Email" required>
            </div>

            <div class="auth-field">
                <span class="auth-field-icon" aria-hidden="true">▣</span>
                <input type="password" name="password" class="form-control" placeholder="Password" required>
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

</body>
</html>
