<?php

include 'db.php';

if (isset($_SESSION['user_id'])) {
    $historyFullname = mysqli_real_escape_string($conn, $_SESSION['fullname'] ?? '');
    $historyRole = mysqli_real_escape_string($conn, $_SESSION['role'] ?? 'user');
    $historyEmail = '';
    $userId = (int) $_SESSION['user_id'];

    $userQuery = mysqli_query($conn, "SELECT email FROM users WHERE id = $userId");
    $user = mysqli_fetch_assoc($userQuery);

    if ($user) {
        $historyEmail = mysqli_real_escape_string($conn, $user['email']);
    }

    mysqli_query(
        $conn,
        "INSERT INTO login_history(user_id, fullname, email, role, action)
         VALUES($userId, '$historyFullname', '$historyEmail', '$historyRole', 'Logout')"
    );
}

session_destroy();

header("Location: login.php");
exit();

?>
