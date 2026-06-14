<?php
include 'db.php';
include 'auth.php';

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $userId = (int) $_SESSION['user_id'];

    if (is_admin()) {
        $stmt = mysqli_prepare($conn, "DELETE FROM products WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM products WHERE id = ? AND user_id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $id, $userId);
    }

    mysqli_stmt_execute($stmt);
}

header("Location: products.php");
exit();
?>
