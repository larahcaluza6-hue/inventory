<?php
include 'db.php';
include 'auth.php';

if (isset($_POST['add'])) {
    $userId = (int) $_SESSION['user_id'];
    $product_name = mysqli_real_escape_string($conn, trim($_POST['product_name']));
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $brand = mysqli_real_escape_string($conn, $_POST['brand']);
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
        "SELECT id
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

    if (mysqli_num_rows($checkResult) > 0) {
        header("Location: products.php?duplicate_grams=" . urlencode(format_grams($grams)));
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
        "INSERT INTO products (user_id, product_name, category, brand, quantity, grams, market_quantity, market_grams, price, image, status)
         VALUES ('$userId', '$product_name', '$category', '$brand', '$quantity', '$grams', 0, 0, '$price', '$image', '$status')"
    );
}

header("Location: products.php");
exit();
?>
