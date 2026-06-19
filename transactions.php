<?php
include 'db.php';
include 'auth.php';

$userId = (int) $_SESSION['user_id'];
$isPrintView = isset($_GET['export']) && $_GET['export'] === 'print';

$transactions = mysqli_query(
    $conn,
    "SELECT mt.*, p.product_name
     FROM market_transactions mt
     LEFT JOIN products p ON p.id = mt.product_id AND p.user_id = mt.user_id
     WHERE mt.user_id = $userId
     ORDER BY mt.created_at DESC, mt.id DESC"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Transactions</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
</head>

<body class="<?php echo $isPrintView ? 'printable-page' : ''; ?>">

<?php if (!$isPrintView) { ?>
    <?php include 'sidebar.php'; ?>
<?php } ?>

<div class="main">
    <?php if ($isPrintView) { ?>
        <div class="print-export-header">
            <div>
                <h1>Transactions</h1>
                <p>Generated on <?php echo date('F j, Y'); ?></p>
            </div>
            <button type="button" class="toolbar-primary-btn print-action-btn" onclick="printExport()">Print</button>
        </div>
    <?php } else { ?>
        <div class="inventory-toolbar">
            <h2 class="transaction-page-title">Transactions</h2>

            <div class="inventory-toolbar-actions transaction-toolbar-actions">
                <a href="transactions.php?export=print" class="toolbar-btn" target="_blank" rel="noopener" onclick="openPrintExport(this.href); return false;">
                    <span aria-hidden="true">⇩</span>
                    Print
                </a>
            </div>
        </div>
    <?php } ?>

    <div class="table-responsive">
    <table class="table table-bordered text-center align-middle market-table">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Product Name</th>
                <th>Quantity</th>
                <th>Transaction</th>
                <th>Date</th>
            </tr>
        </thead>

        <tbody>
        <?php if (mysqli_num_rows($transactions) > 0) { ?>
            <?php $transactionId = 1; ?>
            <?php while ($transactionRow = mysqli_fetch_assoc($transactions)) { ?>
                <tr>
                    <td><?php echo $transactionId; ?></td>
                    <td><?php echo htmlspecialchars($transactionRow['product_name'] ?? 'Deleted Product'); ?></td>
                    <td><?php echo format_quantity($transactionRow['quantity']); ?></td>
                    <td><?php echo htmlspecialchars($transactionRow['transaction_type']); ?></td>
                    <td><?php echo htmlspecialchars($transactionRow['created_at']); ?></td>
                </tr>
                <?php $transactionId++; ?>
            <?php } ?>
        <?php } else { ?>
            <tr>
                <td colspan="5" class="py-4">No transactions yet.</td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
    </div>
</div>

<?php if ($isPrintView) { ?>
<script>
let printStarted = false;

function printExport() {
    if (printStarted) {
        return;
    }

    printStarted = true;
    window.print();
}

window.addEventListener('load', function () {
    printExport();
});

window.addEventListener('afterprint', function () {
    window.close();
});
</script>
<?php } else { ?>
<script>
function openPrintExport(url) {
    window.open(url, 'printExport', 'width=1100,height=800');
}
</script>
<?php } ?>
</body>
</html>
