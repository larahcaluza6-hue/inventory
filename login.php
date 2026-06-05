<?php
include 'db.php';

$modalToOpen = '';

function record_login_history($conn, $user, $role) {
    $historyFullname = mysqli_real_escape_string($conn, $user['fullname']);
    $historyEmail = mysqli_real_escape_string($conn, $user['email']);
    $historyRole = mysqli_real_escape_string($conn, $role);

    mysqli_query(
        $conn,
        "INSERT INTO login_history(user_id, fullname, email, role, action)
         VALUES(" . (int) $user['id'] . ", '$historyFullname', '$historyEmail', '$historyRole', 'Login')"
    );
}

if (isset($_POST['register'])) {
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $existingUserQuery = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");

    if ($existingUserQuery && mysqli_num_rows($existingUserQuery) > 0) {
        $register_error = "Email is already registered.";
        $modalToOpen = 'registerModal';
    } else {
        $userCountQuery = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
        $userCount = mysqli_fetch_assoc($userCountQuery);
        $role = (int) $userCount['total'] === 0 ? 'admin' : 'user';

        mysqli_query(
            $conn,
            "INSERT INTO users(fullname,email,password,role)
             VALUES('$fullname','$email','$password','$role')"
        );

        $register_success = $role === 'admin'
            ? "Admin account created. You can now use Admin login."
            : "Account created. You can now log in.";
        $modalToOpen = 'loginModal';
    }
}

if (isset($_POST['login']) || isset($_POST['admin_login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $isAdminLogin = isset($_POST['admin_login']);
    $modalToOpen = $isAdminLogin ? 'adminModal' : 'loginModal';

    $query = mysqli_query(
        $conn,
        "SELECT * FROM users WHERE email='$email'"
    );

    $user = mysqli_fetch_assoc($query);

    if ($user && password_verify($password, $user['password'])) {
        $userRole = $user['role'] ?? 'user';
        $adminQuery = mysqli_query($conn, "SELECT id FROM users WHERE role='admin' ORDER BY id ASC LIMIT 1");
        $adminUser = mysqli_fetch_assoc($adminQuery);
        $onlyAdminId = $adminUser ? (int) $adminUser['id'] : 0;
        $canUseAdmin = $userRole === 'admin' && (int) $user['id'] === $onlyAdminId;

        if ($isAdminLogin && !$canUseAdmin) {
            $admin_error = "Only the main administrator account can access admin login.";
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role'] = $canUseAdmin ? 'admin' : 'user';

            record_login_history($conn, $user, $_SESSION['role']);

            header("Location: " . ($isAdminLogin ? "admin.php" : "dashboard.php"));
            exit();
        }
    } else {
        if ($isAdminLogin) {
            $admin_error = "Invalid administrator email or password.";
        } else {
            $error = "Invalid Email or Password";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=6">
</head>

<body class="auth-page">

<nav class="auth-topbar" aria-label="Account actions">
    <button type="button" data-bs-toggle="modal" data-bs-target="#loginModal">Login</button>
    <button type="button" data-bs-toggle="modal" data-bs-target="#registerModal">Register</button>
    <button type="button" data-bs-toggle="modal" data-bs-target="#adminModal">Admin</button>
</nav>

<div class="modal fade auth-modal" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="loginModalLabel">Login</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <?php if (isset($register_success)) { ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($register_success); ?>
                    </div>
                <?php } ?>

                <?php if (isset($error)) { ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php } ?>

                <form method="POST" class="auth-form">
                    <div class="auth-field">
                        <span class="auth-field-icon" aria-hidden="true">@</span>
                        <input type="email" name="email" class="form-control" placeholder="Email" required>
                    </div>

                    <div class="auth-field">
                        <span class="auth-field-icon" aria-hidden="true">*</span>
                        <input type="password" name="password" class="form-control login-password" placeholder="Password" required>
                    </div>

                    <div class="auth-row auth-row-end">
                        <a href="forgot_password.php" class="auth-muted-link">Forgot Password?</a>
                    </div>

                    <button type="submit" name="login" class="auth-submit">Login</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade auth-modal" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="registerModalLabel">Register</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <?php if (isset($register_error)) { ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($register_error); ?>
                    </div>
                <?php } ?>

                <form method="POST" class="auth-form">
                    <div class="auth-field">
                        <span class="auth-field-icon" aria-hidden="true">+</span>
                        <input type="text" name="fullname" class="form-control" placeholder="Full Name" required>
                    </div>

                    <div class="auth-field">
                        <span class="auth-field-icon" aria-hidden="true">@</span>
                        <input type="email" name="email" class="form-control" placeholder="Email" required>
                    </div>

                    <div class="auth-field">
                        <span class="auth-field-icon" aria-hidden="true">*</span>
                        <input type="password" name="password" class="form-control login-password" placeholder="Password" required>
                    </div>

                    <button type="submit" name="register" class="auth-submit">Register</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade auth-modal" id="adminModal" tabindex="-1" aria-labelledby="adminModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="adminModalLabel">Admin</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <?php if (isset($admin_error)) { ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($admin_error); ?>
                    </div>
                <?php } ?>

                <form method="POST" class="auth-form">
                    <div class="auth-field">
                        <span class="auth-field-icon" aria-hidden="true">@</span>
                        <input type="email" name="email" class="form-control" placeholder="Admin Email" required>
                    </div>

                    <div class="auth-field">
                        <span class="auth-field-icon" aria-hidden="true">*</span>
                        <input type="password" name="password" class="form-control login-password" placeholder="Admin Password" required>
                    </div>

                    <button type="submit" name="admin_login" class="auth-submit">Administrator Login</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.login-password').forEach(function (passwordInput) {
    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'auth-password-toggle';
    toggle.setAttribute('aria-label', 'Show password');
    toggle.setAttribute('aria-pressed', 'false');
    toggle.innerHTML = '<svg class="auth-eye-icon auth-eye-off-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M3 3l18 18"></path><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path><path d="M9.9 4.4A9.5 9.5 0 0 1 12 4c5 0 8.7 4.1 10 8a13.1 13.1 0 0 1-3 4.6"></path><path d="M6.5 6.7A13.4 13.4 0 0 0 2 12c1.3 3.9 5 8 10 8a9.7 9.7 0 0 0 4.1-.9"></path></svg><svg class="auth-eye-icon auth-eye-on-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M2 12s3.7-7 10-7 10 7 10 7-3.7 7-10 7S2 12 2 12z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
    passwordInput.parentElement.appendChild(toggle);

    toggle.addEventListener('click', function () {
        const isPassword = passwordInput.type === 'password';
        passwordInput.type = isPassword ? 'text' : 'password';
        toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        toggle.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($modalToOpen !== '') { ?>
<script>
new bootstrap.Modal(document.getElementById('<?php echo $modalToOpen; ?>')).show();
</script>
<?php } ?>

</body>
</html>
