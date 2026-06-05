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
        (SELECT COALESCE(SUM(quantity), 0) FROM products) AS stock_total"
);
$summary = mysqli_fetch_assoc($summaryResult);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="main">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Admin</h2>
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
                <h1><?php echo (int) $summary['stock_total']; ?></h1>
            </div>
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
                        <th>Stock</th>
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
                                <td><?php echo (int) $user['stock_total']; ?></td>
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
</div>

</body>
</html>
