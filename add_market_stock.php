<?php
include 'db.php';
include 'auth.php';

$error = '';
$userId = (int) $_SESSION['user_id'];
$availableProducts = mysqli_query(
    $conn,
    "SELECT id, product_name, quantity FROM products WHERE user_id = $userId AND quantity > 0 ORDER BY product_name ASC"
);

if (isset($_POST['store'])) {
    $productId = (int) $_POST['product_id'];
    $storeQuantity = (float) $_POST['store_quantity'];

    if ($storeQuantity <= 0) {
        $error = 'Please enter a valid quantity.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT product_name, quantity, market_quantity FROM products WHERE id = ? AND user_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "ii", $productId, $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $product = mysqli_fetch_assoc($result);

        if (!$product) {
            $error = 'Product was not found.';
        } elseif ((float) $product['quantity'] < $storeQuantity) {
            $error = 'Not enough product stock available.';
        } else {
            $newQuantity = (float) $product['quantity'] - $storeQuantity;
            $newMarketQuantity = (float) $product['market_quantity'] + $storeQuantity;
            $status = $newQuantity > 0 ? 'Available' : 'Sold Out';

            $update = mysqli_prepare(
                $conn,
                "UPDATE products SET quantity = ?, market_quantity = ?, status = ? WHERE id = ? AND user_id = ?"
            );
            mysqli_stmt_bind_param($update, "ddsii", $newQuantity, $newMarketQuantity, $status, $productId, $userId);
            mysqli_stmt_execute($update);

            $transaction = mysqli_prepare(
                $conn,
                "INSERT INTO market_transactions (user_id, product_id, quantity, transaction_type) VALUES (?, ?, ?, 'Add Market Stock')"
            );
            mysqli_stmt_bind_param($transaction, "iid", $userId, $productId, $storeQuantity);
            mysqli_stmt_execute($transaction);

            header("Location: orders.php?added=1");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Market Stock</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="main">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Add Market Stock</h2>
        <a href="orders.php" class="btn btn-secondary">Back</a>
    </div>

    <?php if ($error !== '') { ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php } ?>

    <form method="POST" class="form-page mt-0">
        <select name="product_id" class="form-control mb-3" required>
            <option value="">Select Product</option>
            <?php while ($availableProduct = mysqli_fetch_assoc($availableProducts)) { ?>
                <option value="<?php echo (int) $availableProduct['id']; ?>">
                    <?php echo htmlspecialchars($availableProduct['product_name']); ?>
                </option>
            <?php } ?>
        </select>

        <input
            type="number"
            step="0.01"
            name="store_quantity"
            class="form-control mb-3"
            min="0.01"
            placeholder="Quantity (grams)"
            required
        >

        <button type="submit" name="store" class="btn btn-success">
            Add Stock
        </button>
    </form>
</div>

</body>
</html>
