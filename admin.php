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

$currentYear = (int) date('Y');
$currentMonth = (int) date('n');
$currentDay = (int) date('j');
$stockOverviewLabels = [];
$stockOverviewData = [];

for ($day = 1; $day <= $currentDay; $day++) {
    $stockOverviewLabels[$day] = date('M j', mktime(0, 0, 0, $currentMonth, $day, $currentYear));
    $stockOverviewData[$day] = 0;
}

$stockOverviewResult = mysqli_query(
    $conn,
    "SELECT DAY(created_at) AS transaction_day, COALESCE(SUM(quantity), 0) AS total
     FROM market_transactions
     WHERE YEAR(created_at) = $currentYear
       AND MONTH(created_at) = $currentMonth
     GROUP BY DAY(created_at)"
);

while ($stockOverviewRow = mysqli_fetch_assoc($stockOverviewResult)) {
    $transactionDay = (int) $stockOverviewRow['transaction_day'];

    if (isset($stockOverviewData[$transactionDay])) {
        $stockOverviewData[$transactionDay] = (float) $stockOverviewRow['total'];
    }
}

$categoryColors = ['#2f80ed', '#27ae60', '#f2b01e', '#8b5cf6', '#ef4444', '#06b6d4'];
$categoryLabels = [];
$categoryValues = [];
$categoryLegend = [];
$categoryResult = mysqli_query(
    $conn,
    "SELECT category, COALESCE(SUM((quantity + market_quantity) * price), 0) AS stock_value
     FROM products
     GROUP BY category
     HAVING stock_value > 0
     ORDER BY stock_value DESC
     LIMIT 6"
);

$categoryTotalValue = 0;

while ($categoryRow = mysqli_fetch_assoc($categoryResult)) {
    $categoryLabels[] = $categoryRow['category'];
    $categoryValues[] = (float) $categoryRow['stock_value'];
    $categoryTotalValue += (float) $categoryRow['stock_value'];
}

foreach ($categoryLabels as $index => $categoryLabel) {
    $categoryValue = $categoryValues[$index];
    $categoryLegend[] = [
        'label' => $categoryLabel,
        'value' => $categoryValue,
        'percentage' => $categoryTotalValue > 0 ? round(($categoryValue / $categoryTotalValue) * 100) : 0,
        'color' => $categoryColors[$index % count($categoryColors)]
    ];
}

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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="main admin-dashboard">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Dashboard</h2>
            <p class="admin-subtitle">Manage store users and review inventory ownership.</p>
        </div>
    </div>

    <div class="row dashboard-cards">
        <div class="col-lg-3 col-sm-6">
            <div class="card-box blue admin-metric-card">
                <span class="admin-metric-icon admin-metric-users" aria-hidden="true"></span>
                <div>
                    <h5>Total Users</h5>
                    <h1><?php echo (int) $summary['users_total']; ?></h1>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6">
            <div class="card-box green admin-metric-card">
                <span class="admin-metric-icon admin-metric-admins" aria-hidden="true"></span>
                <div>
                    <h5>Admins</h5>
                    <h1><?php echo (int) $summary['admins_total']; ?></h1>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6">
            <div class="card-box blue admin-metric-card">
                <span class="admin-metric-icon admin-metric-products" aria-hidden="true"></span>
                <div>
                    <h5>All Products</h5>
                    <h1><?php echo (int) $summary['products_total']; ?></h1>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6">
            <div class="card-box green admin-metric-card">
                <span class="admin-metric-icon admin-metric-stock" aria-hidden="true"></span>
                <div>
                    <h5>All Stock</h5>
                    <h1><?php echo format_quantity($summary['stock_total']); ?></h1>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6">
            <a href="stock_transactions.php" class="dashboard-card-link">
                <div class="card-box blue admin-metric-card">
                    <span class="admin-metric-icon admin-metric-transactions" aria-hidden="true"></span>
                    <div>
                        <h5>Stock Transactions</h5>
                        <h1><?php echo (int) $summary['transactions_total']; ?></h1>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-lg-3 col-sm-6">
            <a href="sales.php" class="dashboard-card-link">
                <div class="card-box green admin-metric-card">
                    <span class="admin-metric-icon admin-metric-sales" aria-hidden="true"></span>
                    <div>
                        <h5>Total Sales</h5>
                        <h1><?php echo (int) $summary['sales_total']; ?></h1>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-lg-3 col-sm-6">
            <a href="sales.php" class="dashboard-card-link">
                <div class="card-box blue admin-metric-card">
                    <span class="admin-metric-icon admin-metric-amount" aria-hidden="true"></span>
                    <div>
                        <h5>Total Amount</h5>
                        <h1>PHP <?php echo number_format((float) $summary['sales_amount_total'], 2); ?></h1>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-lg-3 col-sm-6">
            <a href="admin_log.php#loginHistory" class="dashboard-card-link">
                <div class="card-box green admin-metric-card">
                    <span class="admin-metric-icon admin-metric-logins" aria-hidden="true"></span>
                    <div>
                        <h5>Login Logs</h5>
                        <h1><?php echo (int) $summary['logins_total']; ?></h1>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-3 admin-analytics-row">
        <div class="col-xl-6">
            <section class="admin-chart-card" aria-label="Stock overview line chart">
                <div class="admin-chart-header">
                    <h3>Stock Overview</h3>
                    <span>This Month</span>
                </div>

                <div class="admin-line-chart-wrap">
                    <canvas id="adminStockOverviewChart"></canvas>
                </div>
            </section>
        </div>

        <div class="col-xl-6">
            <section class="admin-chart-card" aria-label="Top categories by stock value chart">
                <div class="admin-chart-header">
                    <h3>Top Categories by Stock Value</h3>
                </div>

                <div class="admin-category-chart-layout">
                    <div class="admin-doughnut-wrap">
                        <canvas id="adminCategoryChart"></canvas>
                    </div>

                    <div class="admin-category-legend">
                        <?php if (count($categoryLegend) > 0) { ?>
                            <?php foreach ($categoryLegend as $categoryItem) { ?>
                                <div class="admin-category-legend-item">
                                    <span class="admin-category-dot" style="background: <?php echo htmlspecialchars($categoryItem['color']); ?>"></span>
                                    <span><?php echo htmlspecialchars($categoryItem['label']); ?></span>
                                    <strong>
                                        PHP <?php echo number_format($categoryItem['value'], 2); ?>
                                        (<?php echo (int) $categoryItem['percentage']; ?>%)
                                    </strong>
                                </div>
                            <?php } ?>
                        <?php } else { ?>
                            <p class="admin-chart-empty">No stock value yet.</p>
                        <?php } ?>
                    </div>
                </div>
            </section>
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

<script>
const adminStockOverviewLabels = <?php echo json_encode(array_values($stockOverviewLabels)); ?>;
const adminStockOverviewData = <?php echo json_encode(array_values($stockOverviewData)); ?>;
const adminCategoryLabels = <?php echo json_encode($categoryLabels); ?>;
const adminCategoryValues = <?php echo json_encode($categoryValues); ?>;
const adminCategoryColors = <?php echo json_encode(array_slice($categoryColors, 0, max(1, count($categoryLabels)))); ?>;

new Chart(document.getElementById('adminStockOverviewChart'), {
    type: 'line',
    data: {
        labels: adminStockOverviewLabels,
        datasets: [{
            data: adminStockOverviewData,
            borderColor: '#2f80ed',
            backgroundColor: 'rgba(47, 128, 237, .12)',
            borderWidth: 3,
            pointRadius: 0,
            pointHoverRadius: 5,
            pointBackgroundColor: '#2f80ed',
            tension: .36,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function (context) {
                        return 'Stock: ' + Number(context.parsed.y).toLocaleString();
                    }
                }
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: '#64748b',
                    maxTicksLimit: 6
                }
            },
            y: {
                beginAtZero: true,
                grid: {
                    color: '#e8edf5'
                },
                ticks: {
                    color: '#64748b',
                    callback: function (value) {
                        return Number(value).toLocaleString();
                    }
                }
            }
        }
    }
});

new Chart(document.getElementById('adminCategoryChart'), {
    type: 'doughnut',
    data: {
        labels: adminCategoryLabels.length ? adminCategoryLabels : ['No Value'],
        datasets: [{
            data: adminCategoryValues.length ? adminCategoryValues : [1],
            backgroundColor: adminCategoryLabels.length ? adminCategoryColors : ['#e5e7eb'],
            borderColor: '#ffffff',
            borderWidth: 4,
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '62%',
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function (context) {
                        if (!adminCategoryValues.length) {
                            return 'No stock value yet';
                        }

                        return context.label + ': PHP ' + Number(context.parsed).toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    }
                }
            }
        }
    }
});
</script>

</body>
</html>
