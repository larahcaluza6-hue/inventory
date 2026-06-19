<?php
include 'db.php';
include 'admin_auth.php';

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
    <title>Admin Log</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="main">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Admin Log</h2>
            <p class="admin-subtitle">Review account activity.</p>
        </div>
    </div>

    <div class="admin-panel" id="loginHistory">
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
