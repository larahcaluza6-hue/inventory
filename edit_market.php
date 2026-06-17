<?php
include 'db.php';
include 'auth.php';

if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$id = (int) $_GET['id'];
$userId = (int) $_SESSION['user_id'];

$stmt = mysqli_prepare($conn, "SELECT id FROM products WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $id, $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    header("Location: orders.php");
    exit();
}

if (isset($_POST['update'])) {
    $productName = $_POST['product_name'];
    $category = $_POST['category'];
    $brand = $_POST['brand'];
    $marketQuantity = (float) $_POST['market_quantity'];
    $price = (float) $_POST['price'];

    if ($marketQuantity < 0) {
        $marketQuantity = 0;
    }

    $uploadDir = __DIR__ . "/assets/uploads/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $image = uniqid("product_", true) . "." . $extension;
        $tmp = $_FILES['image']['tmp_name'];
        move_uploaded_file($tmp, $uploadDir . $image);

        $update = mysqli_prepare(
            $conn,
            "UPDATE products
             SET product_name = ?, category = ?, brand = ?, market_quantity = ?, price = ?, image = ?
             WHERE id = ? AND user_id = ?"
        );
        mysqli_stmt_bind_param($update, "sssddsii", $productName, $category, $brand, $marketQuantity, $price, $image, $id, $userId);
    } else {
        $update = mysqli_prepare(
            $conn,
            "UPDATE products
             SET product_name = ?, category = ?, brand = ?, market_quantity = ?, price = ?
             WHERE id = ? AND user_id = ?"
        );
        mysqli_stmt_bind_param($update, "sssddii", $productName, $category, $brand, $marketQuantity, $price, $id, $userId);
    }

    mysqli_stmt_execute($update);

    header("Location: orders.php?updated=1");
    exit();
}

header("Location: orders.php");
exit();
?>
