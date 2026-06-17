<?php
include 'db.php';
include 'auth.php';

if (isset($_POST['add'])) {
    $userId = (int) $_SESSION['user_id'];
    $raw_product_name = trim($_POST['product_name']);
    $product_name = mysqli_real_escape_string($conn, $raw_product_name);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $brand = mysqli_real_escape_string($conn, $_POST['brand']);
    $quantity = (float) $_POST['quantity'];
    $price = (float) $_POST['price'];
    $status = $quantity > 0 ? "Available" : "Sold Out";

    $check = mysqli_query(
        $conn,
        "SELECT id FROM products WHERE user_id = $userId AND LOWER(product_name) = LOWER('$product_name') LIMIT 1"
    );

    if (mysqli_num_rows($check) > 0) {
        header("Location: products.php?duplicate=" . urlencode($raw_product_name));
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

    mysqli_query(
        $conn,
        "INSERT INTO products (user_id, product_name, category, brand, quantity, market_quantity, price, image, status)
         VALUES ('$userId', '$product_name', '$category', '$brand', '$quantity', 0, '$price', '$image', '$status')"
    );
}

header("Location: products.php");
exit();
?>
