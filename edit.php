<?php
include 'db.php';
include 'auth.php';

if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$id = (int) $_GET['id'];
$userId = (int) $_SESSION['user_id'];

$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $id, $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    header("Location: products.php");
    exit();
}

if (isset($_POST['update'])) {
    $product_name = $_POST['product_name'];
    $category = $_POST['category'];
    $brand = $_POST['brand'];
    $quantity = (int) $_POST['quantity'];
    $price = (float) $_POST['price'];
    $status = $quantity > 0 ? "Available" : "Sold Out";

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
             SET product_name = ?, category = ?, brand = ?, quantity = ?, price = ?, image = ?, status = ?
             WHERE id = ? AND user_id = ?"
        );
        mysqli_stmt_bind_param($update, "sssidssii", $product_name, $category, $brand, $quantity, $price, $image, $status, $id, $userId);
    } else {
        $update = mysqli_prepare(
            $conn,
            "UPDATE products
             SET product_name = ?, category = ?, brand = ?, quantity = ?, price = ?, status = ?
             WHERE id = ? AND user_id = ?"
        );
        mysqli_stmt_bind_param($update, "sssidsii", $product_name, $category, $brand, $quantity, $price, $status, $id, $userId);
    }

    mysqli_stmt_execute($update);

    header("Location: products.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Product</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="main">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Edit Product</h2>
        <a href="products.php" class="btn btn-secondary">Back</a>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <input
            type="text"
            name="product_name"
            class="form-control mb-3"
            placeholder="Product Name"
            value="<?php echo htmlspecialchars($product['product_name']); ?>"
            required
        >

        <input
            type="text"
            name="category"
            class="form-control mb-3"
            placeholder="Category"
            value="<?php echo htmlspecialchars($product['category']); ?>"
            required
        >

        <input
            type="text"
            name="brand"
            class="form-control mb-3"
            placeholder="Brand"
            value="<?php echo htmlspecialchars($product['brand']); ?>"
            required
        >

        <input
            type="number"
            name="quantity"
            class="form-control mb-3"
            placeholder="Quantity"
            value="<?php echo htmlspecialchars($product['quantity']); ?>"
            required
        >

        <input
            type="number"
            step="0.01"
            name="price"
            class="form-control mb-3"
            placeholder="Price"
            value="<?php echo htmlspecialchars($product['price']); ?>"
            required
        >

        <label class="form-label">Product Image</label>
        <input type="file" name="image" class="form-control mb-3" accept="image/*">

        <button type="submit" name="update" class="btn btn-success">
            Update Product
        </button>
    </form>
</div>

</body>
</html>
