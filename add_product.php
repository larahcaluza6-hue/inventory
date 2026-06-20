<?php
include 'db.php';
include 'auth.php';

if (isset($_POST['add'])) {
    $userId = (int) $_SESSION['user_id'];
    $product_name = trim($_POST['product_name']);
    $category = trim($_POST['category']);
    $brand = trim($_POST['brand']);
    $quantity = (float) $_POST['quantity'];
    $grams = (float) $_POST['grams'];
    $price = (float) $_POST['price'];
    $status = $quantity > 0 ? "Available" : "Sold Out";

    if ($quantity < 1) {
        header("Location: products.php?error=" . urlencode("Quantity must be at least 1."));
        exit();
    }

    if ($grams <= 0) {
        header("Location: products.php?error=" . urlencode("Grams must be greater than 0."));
        exit();
    }

    $check = mysqli_prepare(
        $conn,
        "SELECT id, quantity
         FROM products
         WHERE user_id = ?
           AND product_name = ?
           AND category = ?
           AND brand = ?
           AND grams = ?
         LIMIT 1"
    );
    mysqli_stmt_bind_param($check, "isssd", $userId, $product_name, $category, $brand, $grams);
    mysqli_stmt_execute($check);
    $checkResult = mysqli_stmt_get_result($check);

    if ($existingProduct = mysqli_fetch_assoc($checkResult)) {
        $newQuantity = (float) $existingProduct['quantity'] + $quantity;
        $status = $newQuantity > 0 ? "Available" : "Sold Out";

        $update = mysqli_prepare(
            $conn,
            "UPDATE products
             SET quantity = ?, price = ?, status = ?
             WHERE id = ? AND user_id = ?"
        );
        mysqli_stmt_bind_param($update, "ddsii", $newQuantity, $price, $status, $existingProduct['id'], $userId);
        mysqli_stmt_execute($update);

        header("Location: products.php?combined=1");
        exit();
    }

    $image = "";
    $uploadDir = __DIR__ . "/assets/uploads/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $image = uniqid("product_", true) . "." . $extension;
        $tmp = $_FILES['image']['tmp_name'];
        move_uploaded_file($tmp, $uploadDir . $image);
    }

    $insert = mysqli_prepare(
        $conn,
        "INSERT INTO products (user_id, product_name, category, brand, quantity, grams, market_quantity, market_grams, price, image, status)
         VALUES (?, ?, ?, ?, ?, ?, 0, 0, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($insert, "isssdddss", $userId, $product_name, $category, $brand, $quantity, $grams, $price, $image, $status);
    mysqli_stmt_execute($insert);
}

header("Location: products.php");
exit();
?>
