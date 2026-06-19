<?php
include 'db.php';
include 'auth.php';

if (!isset($_POST['sell'])) {
    header("Location: sales.php");
    exit();
}

$userId = (int) $_SESSION['user_id'];
$customerName = trim($_POST['customer_name'] ?? '');
$productIds = $_POST['product_id'] ?? [];
$saleQuantities = $_POST['sale_quantity'] ?? [];
$saleItems = [];

if (!is_array($productIds) || !is_array($saleQuantities) || count($productIds) === 0) {
    header("Location: sales.php?error=" . urlencode("Please enter valid sale details."));
    exit();
}

foreach ($productIds as $index => $rawProductId) {
    $productId = (int) $rawProductId;
    $saleQuantity = (float) ($saleQuantities[$index] ?? 0);

    if ($productId <= 0 || $saleQuantity <= 0) {
        continue;
    }

    if (!isset($saleItems[$productId])) {
        $saleItems[$productId] = 0;
    }

    $saleItems[$productId] += $saleQuantity;
}

if (count($saleItems) === 0) {
    header("Location: sales.php?error=" . urlencode("Please add at least one product to the sale."));
    exit();
}

$validatedItems = [];

foreach ($saleItems as $productId => $saleQuantity) {
    $productStmt = mysqli_prepare(
        $conn,
        "SELECT product_name, market_quantity, market_grams, price
         FROM products
         WHERE id = ? AND user_id = ?
         LIMIT 1"
    );
    mysqli_stmt_bind_param($productStmt, "ii", $productId, $userId);
    mysqli_stmt_execute($productStmt);
    $productResult = mysqli_stmt_get_result($productStmt);
    $product = mysqli_fetch_assoc($productResult);

    if (!$product) {
        header("Location: sales.php?error=" . urlencode("One selected product was not found."));
        exit();
    }

    $marketQuantity = (float) $product['market_quantity'];
    $marketGrams = (float) $product['market_grams'];

    if ($marketQuantity < $saleQuantity) {
        header("Location: sales.php?error=" . urlencode($product['product_name'] . " does not have enough market stock."));
        exit();
    }

    $unitGrams = $marketQuantity > 0 ? $marketGrams / $marketQuantity : 0;
    $saleGrams = min($marketGrams, $saleQuantity * $unitGrams);

    $validatedItems[] = [
        'product_id' => $productId,
        'quantity' => $saleQuantity,
        'grams' => $saleGrams,
        'new_market_quantity' => $marketQuantity - $saleQuantity,
        'new_market_grams' => max(0, $marketGrams - $saleGrams),
        'unit_price' => (float) $product['price'],
        'total_amount' => $saleQuantity * (float) $product['price']
    ];
}

foreach ($validatedItems as $item) {
    $status = $item['new_market_quantity'] > 0 ? 'Available' : 'Sold Out';

    $updateStmt = mysqli_prepare(
        $conn,
        "UPDATE products
         SET market_quantity = ?, market_grams = ?, status = ?
         WHERE id = ? AND user_id = ? AND market_quantity >= ?"
    );
    mysqli_stmt_bind_param($updateStmt, "ddsiid", $item['new_market_quantity'], $item['new_market_grams'], $status, $item['product_id'], $userId, $item['quantity']);
    mysqli_stmt_execute($updateStmt);

    if (mysqli_stmt_affected_rows($updateStmt) === 0) {
        header("Location: sales.php?error=" . urlencode("Stock changed before the sale was saved. Please try again."));
        exit();
    }

    $saleStmt = mysqli_prepare(
        $conn,
        "INSERT INTO product_sales (user_id, product_id, customer_name, quantity, grams, unit_price, total_amount)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($saleStmt, "iisdddd", $userId, $item['product_id'], $customerName, $item['quantity'], $item['grams'], $item['unit_price'], $item['total_amount']);
    mysqli_stmt_execute($saleStmt);

    $transactionStmt = mysqli_prepare(
        $conn,
        "INSERT INTO market_transactions (user_id, product_id, quantity, transaction_type)
         VALUES (?, ?, ?, 'Sell Product')"
    );
    mysqli_stmt_bind_param($transactionStmt, "iid", $userId, $item['product_id'], $item['quantity']);
    mysqli_stmt_execute($transactionStmt);
}

header("Location: sales.php?sold=1");
exit();
