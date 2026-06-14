<?php
include 'db.php';
include 'auth.php';

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $userId = (int) $_SESSION['user_id'];

    $stmt = mysqli_prepare($conn, "DELETE FROM products WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $id, $userId);
    mysqli_stmt_execute($stmt);
}

header("Location: orders.php?deleted=1");
exit();
?>
