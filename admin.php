<?php
include 'db.php';
include 'admin_auth.php';

$usersResult = mysqli_query(
    $conn,
    "SELECT
        users.id,
        users.fullname,
        users.email,
        users.role,
        users.created_at,
        COUNT(products.id) AS product_total,
        COALESCE(SUM(products.quantity), 0) AS stock_total
     FROM users
     LEFT JOIN products ON products.user_id = users.id
     GROUP BY users.id
     ORDER BY users.id ASC"
);

$summaryResult = mysqli_query(
    $conn,
    "SELECT
        (SELECT COUNT(*) FROM users) AS users_total,
        (SELECT COUNT(*) FROM users WHERE role = 'admin') AS admins_total,
        (SELECT COUNT(*) FROM products) AS products_total,
        (SELECT COALESCE(SUM(quantity), 0) FROM products) AS stock_total,
        (SELECT COUNT(*) FROM market_transactions) AS transactions_total,
        (SELECT COUNT(*) FROM product_sales) AS sales_total,
        (SELECT COALESCE(SUM(total_amount), 0) FROM product_sales) AS sales_amount_total,
        (SELECT COUNT(*) FROM login_history) AS logins_total"
);
$summary = mysqli_fetch_assoc($summaryResult);

$loginHistoryResult = mysqli_query(
    $conn,
    "SELECT *
     FROM login_history
     ORDER BY created_at DESC, id DESC
     LIMIT 100"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="main">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Dashboard</h2>
            <p class="admin-subtitle">Manage store users and review inventory ownership.</p>
        </div>
    </div>

    <div class="row dashboard-cards">
        <div class="col-lg-3 col-sm-6">
            <div class="card-box blue">
                <h5>Total Users</h5>
                <h1><?php echo (int) $summary['users_total']; ?></h1>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6">
            <div class="card-box green">
                <h5>Admins</h5>
                <h1><?php echo (int) $summary['admins_total']; ?></h1>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6">
            <div class="card-box blue">
                <h5>All Products</h5>
                <h1><?php echo (int) $summary['products_total']; ?></h1>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6">
            <div class="card-box green">
                <h5>All Stock</h5>
                <h1><?php echo format_quantity($summary['stock_total']); ?></h1>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6">
            <a href="stock_transactions.php" class="dashboard-card-link">
                <div class="card-box blue">
                    <h5>Stock Transactions</h5>
                    <h1><?php echo (int) $summary['transactions_total']; ?></h1>
                </div>
            </a>
        </div>

        <div class="col-lg-3 col-sm-6">
            <a href="sales.php" class="dashboard-card-link">
                <div class="card-box green">
                    <h5>Total Sales</h5>
                    <h1><?php echo (int) $summary['sales_total']; ?></h1>
                </div>
            </a>
        </div>

        <div class="col-lg-3 col-sm-6">
            <a href="sales.php" class="dashboard-card-link">
                <div class="card-box blue">
                    <h5>Total Amount</h5>
                    <h1>PHP <?php echo number_format((float) $summary['sales_amount_total'], 2); ?></h1>
                </div>
            </a>
        </div>

        <div class="col-lg-3 col-sm-6">
            <a href="admin_log.php#loginHistory" class="dashboard-card-link">
                <div class="card-box green">
                    <h5>Login Logs</h5>
                    <h1><?php echo (int) $summary['logins_total']; ?></h1>
                </div>
            </a>
        </div>
    </div>

    <div class="admin-panel">
        <div class="admin-panel-header">
            <h3>Users</h3>
        </div>

        <div class="products-table-shell">
            <table class="products-table admin-users-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Products</th>
                        <th>Quantity</th>
                        <th>Joined</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (mysqli_num_rows($usersResult) > 0) { ?>
                        <?php while ($user = mysqli_fetch_assoc($usersResult)) { ?>
                            <tr>
                                <td>
                                    <div class="admin-user-cell">
                                        <span class="admin-avatar">
                                            <?php echo strtoupper(substr($user['fullname'], 0, 1)); ?>
                                        </span>
                                        <span><?php echo htmlspecialchars($user['fullname']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="soft-pill <?php echo $user['role'] === 'admin' ? 'status-success' : 'category-pill'; ?>">
                                        <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                                    </span>
                                </td>
                                <td><?php echo (int) $user['product_total']; ?></td>
                                <td><?php echo format_quantity($user['stock_total']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="6" class="empty-products">No users found.</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="admin-panel">
        <div class="admin-panel-header">
            <h3>Login and Logout History</h3>
        </div>

        <div class="products-table-shell">
            <table class="products-table admin-monitor-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Action</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (mysqli_num_rows($loginHistoryResult) > 0) { ?>
                        <?php $historyNumber = 1; ?>
                        <?php while ($history = mysqli_fetch_assoc($loginHistoryResult)) { ?>
                            <tr>
                                <td class="id-cell"><?php echo $historyNumber; ?></td>
                                <td><?php echo htmlspecialchars($history['fullname']); ?></td>
                                <td><?php echo htmlspecialchars($history['email']); ?></td>
                                <td>
                                    <span class="soft-pill <?php echo $history['role'] === 'admin' ? 'status-success' : 'category-pill'; ?>">
                                        <?php echo htmlspecialchars(ucfirst($history['role'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="soft-pill <?php echo $history['action'] === 'Login' ? 'status-success' : 'status-warning'; ?>">
                                        <?php echo htmlspecialchars($history['action']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($history['created_at']); ?></td>
                            </tr>
                            <?php $historyNumber++; ?>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="6" class="empty-products">No login history found.</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
