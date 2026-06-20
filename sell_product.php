<?php
include 'db.php';
include 'auth.php';

if (!isset($_POST['sell'])) {
    header("Location: sales.php");
    exit();
}

$userId = (int) $_SESSION['user_id'];
$customerName = trim($_POST['customer_name'] ?? '');
$cashAmountRaw = trim($_POST['cash_amount'] ?? '');
$productIds = $_POST['product_id'] ?? [];
$saleQuantities = $_POST['sale_quantity'] ?? [];
$saleItems = [];

if (!is_array($productIds) || !is_array($saleQuantities) || count($productIds) === 0) {
    header("Location: sales.php?error=" . urlencode("Please enter valid sale details."));
    exit();
}

if ($cashAmountRaw === '' || !is_numeric($cashAmountRaw)) {
    header("Location: sales.php?error=" . urlencode("Please enter the cash paid amount."));
    exit();
}

$cashAmount = (float) $cashAmountRaw;

if ($cashAmount < 0) {
    header("Location: sales.php?error=" . urlencode("Cash paid cannot be negative."));
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
$grandTotal = 0;

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

    $validatedItems[] = [
        'product_id' => $productId,
        'quantity' => $saleQuantity,
        'grams' => $marketGrams,
        'new_market_quantity' => $marketQuantity - $saleQuantity,
        'unit_price' => (float) $product['price'],
        'total_amount' => $saleQuantity * (float) $product['price']
    ];

    $grandTotal += $saleQuantity * (float) $product['price'];
}

if ($cashAmount + 0.00001 < $grandTotal) {
    header("Location: sales.php?error=" . urlencode("Cash paid must be equal to or greater than the grand total."));
    exit();
}

$changeAmount = $cashAmount - $grandTotal;
$receiptNo = 'R' . date('YmdHis') . random_int(100, 999);

foreach ($validatedItems as $item) {
    $status = $item['new_market_quantity'] > 0 ? 'Available' : 'Sold Out';

    $updateStmt = mysqli_prepare(
        $conn,
        "UPDATE products
         SET market_quantity = ?, status = ?
         WHERE id = ? AND user_id = ? AND market_quantity >= ?"
    );
    mysqli_stmt_bind_param($updateStmt, "dsiid", $item['new_market_quantity'], $status, $item['product_id'], $userId, $item['quantity']);
    mysqli_stmt_execute($updateStmt);

    if (mysqli_stmt_affected_rows($updateStmt) === 0) {
        header("Location: sales.php?error=" . urlencode("Stock changed before the sale was saved. Please try again."));
        exit();
    }

    $saleStmt = mysqli_prepare(
        $conn,
        "INSERT INTO product_sales (receipt_no, user_id, product_id, customer_name, quantity, grams, unit_price, total_amount, cash_amount, change_amount)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($saleStmt, "siisdddddd", $receiptNo, $userId, $item['product_id'], $customerName, $item['quantity'], $item['grams'], $item['unit_price'], $item['total_amount'], $cashAmount, $changeAmount);
    mysqli_stmt_execute($saleStmt);

    $transactionStmt = mysqli_prepare(
        $conn,
        "INSERT INTO market_transactions (user_id, product_id, quantity, transaction_type)
         VALUES (?, ?, ?, 'Sell Product')"
    );
    mysqli_stmt_bind_param($transactionStmt, "iid", $userId, $item['product_id'], $item['quantity']);
    mysqli_stmt_execute($transactionStmt);
}

header("Location: receipt.php?receipt=" . urlencode($receiptNo) . "&sold=1");
exit();
