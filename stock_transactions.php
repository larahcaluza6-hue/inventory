<?php
include 'db.php';
include 'admin_auth.php';

$stockTransactionsResult = mysqli_query(
    $conn,
    "SELECT
        mt.id,
        mt.quantity,
        mt.transaction_type,
        mt.created_at,
        p.product_name,
        users.fullname
     FROM market_transactions mt
     LEFT JOIN products p ON p.id = mt.product_id
     LEFT JOIN users ON users.id = mt.user_id
     ORDER BY mt.created_at DESC, mt.id DESC
     LIMIT 100"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Stock Transactions</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="main">
    <div class="recent-stock-panel stock-transactions-page">
        <div class="recent-stock-header">
            <h3>Recent Stock Transactions</h3>
            <a href="dashboard.php" class="recent-stock-view">Back</a>
        </div>

        <div class="recent-stock-table-wrap">
            <table class="recent-stock-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Reference</th>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>User</th>
                        <th aria-label="Actions"></th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (mysqli_num_rows($stockTransactionsResult) > 0) { ?>
                        <?php while ($stockTransaction = mysqli_fetch_assoc($stockTransactionsResult)) { ?>
                            <?php
                                $transactionType = $stockTransaction['transaction_type'];
                                $typeClass = stripos($transactionType, 'stock') !== false ? 'stock-in' : 'transfer';
                                $referencePrefix = stripos($transactionType, 'stock') !== false ? 'SI' : 'TR';
                                $reference = $referencePrefix . '-' . date('Y', strtotime($stockTransaction['created_at'])) . '-' . str_pad((string) $stockTransaction['id'], 4, '0', STR_PAD_LEFT);
                            ?>
                            <tr>
                                <td><?php echo date('M d, Y h:i A', strtotime($stockTransaction['created_at'])); ?></td>
                                <td>
                                    <span class="recent-type-pill <?php echo $typeClass; ?>">
                                        <?php echo htmlspecialchars($transactionType); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="recent-reference">
                                        <?php echo htmlspecialchars($reference); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($stockTransaction['product_name'] ?? 'Deleted Product'); ?></td>
                                <td><?php echo format_quantity($stockTransaction['quantity']); ?></td>
                                <td>
                                    <span class="recent-status-pill">Completed</span>
                                </td>
                                <td><?php echo htmlspecialchars($stockTransaction['fullname'] ?? 'Unknown User'); ?></td>
                                <td class="recent-actions">...</td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="8" class="empty-products">No recent stock transactions found.</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
