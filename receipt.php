<?php
include 'db.php';
include 'auth.php';

$userId = (int) $_SESSION['user_id'];
$receiptNo = trim($_GET['receipt'] ?? '');
$showSaleSuccess = isset($_GET['sold']) && $_GET['sold'] === '1';
$isAdminUser = is_admin();

if ($receiptNo === '') {
    header("Location: sales.php?error=" . urlencode("Receipt was not found."));
    exit();
}

if ($isAdminUser) {
    $receiptStmt = mysqli_prepare(
        $conn,
        "SELECT ps.*, p.product_name, p.brand, users.fullname
         FROM product_sales ps
         LEFT JOIN products p ON p.id = ps.product_id
         LEFT JOIN users ON users.id = ps.user_id
         WHERE ps.receipt_no = ?
         ORDER BY ps.id ASC"
    );
    mysqli_stmt_bind_param($receiptStmt, "s", $receiptNo);
} else {
    $receiptStmt = mysqli_prepare(
        $conn,
        "SELECT ps.*, p.product_name, p.brand, users.fullname
         FROM product_sales ps
         LEFT JOIN products p ON p.id = ps.product_id AND p.user_id = ps.user_id
         LEFT JOIN users ON users.id = ps.user_id
         WHERE ps.receipt_no = ? AND ps.user_id = ?
         ORDER BY ps.id ASC"
    );
    mysqli_stmt_bind_param($receiptStmt, "si", $receiptNo, $userId);
}

mysqli_stmt_execute($receiptStmt);
$receiptResult = mysqli_stmt_get_result($receiptStmt);
$receiptItems = [];

while ($item = mysqli_fetch_assoc($receiptResult)) {
    $receiptItems[] = $item;
}

if (count($receiptItems) === 0) {
    header("Location: sales.php?error=" . urlencode("Receipt was not found."));
    exit();
}

$firstItem = $receiptItems[0];
$customerName = $firstItem['customer_name'] !== '' ? $firstItem['customer_name'] : 'Walk-in Customer';
$cashAmount = (float) $firstItem['cash_amount'];
$changeAmount = (float) $firstItem['change_amount'];
$grandTotal = 0;
$totalQuantity = 0;

foreach ($receiptItems as $item) {
    $grandTotal += (float) $item['total_amount'];
    $totalQuantity += (float) $item['quantity'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Receipt <?php echo htmlspecialchars($receiptNo); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
</head>

<body class="printable-page receipt-page">
    <?php if ($showSaleSuccess) { ?>
    <div class="alert alert-success alert-dismissible fade show sale-success-notification" id="saleSuccessNotification" role="alert">
        Sale was recorded successfully.
        <button type="button" class="btn-close" aria-label="Close" onclick="closeSaleSuccessNotification()"></button>
    </div>
    <?php } ?>

    <main class="receipt-shell">
        <div class="receipt-actions print-action-btn">
            <a href="sales.php?sold=1" class="toolbar-btn">Back to Sales</a>
            <button type="button" class="toolbar-primary-btn" onclick="printReceipt()">Print Receipt</button>
        </div>

        <section class="receipt-paper" aria-label="Sales receipt">
            <header class="receipt-header">
                <h1>HANNAH STORE</h1>
                <p>Sales Receipt</p>
            </header>

            <dl class="receipt-meta">
                <div>
                    <dt>Receipt</dt>
                    <dd><?php echo htmlspecialchars($receiptNo); ?></dd>
                </div>
                <div>
                    <dt>Date</dt>
                    <dd><?php echo htmlspecialchars($firstItem['created_at']); ?></dd>
                </div>
                <div>
                    <dt>Cashier</dt>
                    <dd><?php echo htmlspecialchars($firstItem['fullname'] ?? 'User'); ?></dd>
                </div>
                <div>
                    <dt>Customer</dt>
                    <dd><?php echo htmlspecialchars($customerName); ?></dd>
                </div>
            </dl>

            <table class="receipt-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($receiptItems as $item) { ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($item['product_name'] ?? 'Deleted Product'); ?></strong>
                                <?php if (($item['brand'] ?? '') !== '') { ?>
                                    <span><?php echo htmlspecialchars($item['brand']); ?></span>
                                <?php } ?>
                            </td>
                            <td><?php echo format_quantity($item['quantity']); ?></td>
                            <td>PHP <?php echo number_format((float) $item['unit_price'], 2); ?></td>
                            <td>PHP <?php echo number_format((float) $item['total_amount'], 2); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <dl class="receipt-totals">
                <div>
                    <dt>Total Qty</dt>
                    <dd><?php echo format_quantity($totalQuantity); ?></dd>
                </div>
                <div>
                    <dt>Grand Total</dt>
                    <dd>PHP <?php echo number_format($grandTotal, 2); ?></dd>
                </div>
                <div>
                    <dt>Cash Paid</dt>
                    <dd>PHP <?php echo number_format($cashAmount, 2); ?></dd>
                </div>
                <div>
                    <dt>Change</dt>
                    <dd>PHP <?php echo number_format($changeAmount, 2); ?></dd>
                </div>
            </dl>

            <p class="receipt-thanks">Thank you for your purchase.</p>
        </section>
    </main>

    <script>
    let receiptPrintStarted = false;
    const saleSuccessNotification = document.getElementById('saleSuccessNotification');

    function printReceipt() {
        if (receiptPrintStarted) {
            return;
        }

        receiptPrintStarted = true;
        window.print();
    }

    function closeSaleSuccessNotification() {
        if (saleSuccessNotification) {
            saleSuccessNotification.classList.remove('show');
            setTimeout(function () {
                saleSuccessNotification.remove();
            }, 180);
        }
    }

    if (saleSuccessNotification) {
        setTimeout(closeSaleSuccessNotification, 5000);
    }

    window.addEventListener('load', function () {
        printReceipt();
    });
    </script>
</body>
</html>
