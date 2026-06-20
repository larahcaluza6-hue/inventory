<?php
include 'db.php';
include 'auth.php';

$userId = (int) $_SESSION['user_id'];

$product_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE user_id = $userId");
$product_data = mysqli_fetch_assoc($product_query);

$available_query = mysqli_query($conn, "SELECT COALESCE(SUM(quantity), 0) as total FROM products WHERE user_id = $userId AND quantity > 0");
$available_data = mysqli_fetch_assoc($available_query);

$market_stock_query = mysqli_query($conn, "SELECT COALESCE(SUM(market_quantity), 0) as total FROM products WHERE user_id = $userId AND market_quantity > 0");
$market_stock_data = mysqli_fetch_assoc($market_stock_query);

$sales_amount_query = mysqli_query($conn, "SELECT COALESCE(SUM(total_amount), 0) as total FROM product_sales WHERE user_id = $userId");
$sales_amount_data = mysqli_fetch_assoc($sales_amount_query);

$sold_out_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE user_id = $userId AND quantity <= 0");
$sold_out_data = mysqli_fetch_assoc($sold_out_query);

$low_stock_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE user_id = $userId AND market_quantity > 0 AND market_quantity < 10");
$low_stock_data = mysqli_fetch_assoc($low_stock_query);

$currentYear = (int) date('Y');
$currentMonth = (int) date('n');
$currentDay = (int) date('j');
$salesChartLabels = [];
$salesChartData = [];

for ($day = 1; $day <= $currentDay; $day++) {
    $salesChartLabels[$day] = date('M j', mktime(0, 0, 0, $currentMonth, $day, $currentYear));
    $salesChartData[$day] = 0;
}

$sales_chart_query = mysqli_query(
    $conn,
    "SELECT DAY(created_at) AS sale_day, COALESCE(SUM(total_amount), 0) AS total
     FROM product_sales
     WHERE user_id = $userId
       AND YEAR(created_at) = $currentYear
       AND MONTH(created_at) = $currentMonth
     GROUP BY DAY(created_at)"
);

while ($sales_chart_row = mysqli_fetch_assoc($sales_chart_query)) {
    $saleDay = (int) $sales_chart_row['sale_day'];

    if (isset($salesChartData[$saleDay])) {
        $salesChartData[$saleDay] = (float) $sales_chart_row['total'];
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Inventory Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="main user-dashboard">
    <h2 class="mb-4">Dashboard</h2>

    <div class="row dashboard-cards">
        <div class="col-lg-3 col-sm-6">
            <div class="card-box blue admin-metric-card">
                <span class="admin-metric-icon admin-metric-products" aria-hidden="true"></span>
                <div>
                    <h5>Total Products</h5>
                    <h1><?php echo (int) $product_data['total']; ?></h1>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6">
            <div class="card-box green admin-metric-card">
                <span class="admin-metric-icon admin-metric-stock" aria-hidden="true"></span>
                <div>
                    <h5>Product Stock</h5>
                    <h1><?php echo format_quantity($available_data['total']); ?></h1>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6">
            <div class="card-box blue admin-metric-card">
                <span class="admin-metric-icon admin-metric-transactions" aria-hidden="true"></span>
                <div>
                    <h5>Market Stock</h5>
                    <h1><?php echo format_quantity($market_stock_data['total']); ?></h1>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6">
            <div class="card-box red admin-metric-card">
                <span class="admin-metric-icon admin-metric-soldout" aria-hidden="true"></span>
                <div>
                    <h5>Sold Out</h5>
                    <h1><?php echo (int) $sold_out_data['total']; ?></h1>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6">
            <a href="orders.php?stock=low" class="dashboard-card-link">
                <div class="card-box blue admin-metric-card">
                    <span class="admin-metric-icon admin-metric-amount" aria-hidden="true"></span>
                    <div>
                        <h5>Low Stock</h5>
                        <h1><?php echo (int) $low_stock_data['total']; ?></h1>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-lg-3 col-sm-6">
            <a href="sales.php" class="dashboard-card-link">
                <div class="card-box green admin-metric-card">
                    <span class="admin-metric-icon admin-metric-sales" aria-hidden="true"></span>
                    <div>
                        <h5>Total Sales Amount</h5>
                        <h1>PHP <?php echo number_format((float) $sales_amount_data['total'], 2); ?></h1>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-3 admin-analytics-row">
        <div class="col-12">
            <section class="admin-chart-card" aria-label="Daily sales amount line chart">
                <div class="admin-chart-header">
                    <h3>Sales Overview</h3>
                    <span>This Month</span>
                </div>

                <div class="admin-line-chart-wrap">
                    <canvas id="dashboardSalesChart"></canvas>
                </div>
            </section>
        </div>
    </div>
</div>

<script>
const dashboardSalesLabels = <?php echo json_encode(array_values($salesChartLabels)); ?>;
const dashboardSalesData = <?php echo json_encode(array_values($salesChartData)); ?>;

new Chart(document.getElementById('dashboardSalesChart'), {
    type: 'line',
    data: {
        labels: dashboardSalesLabels,
        datasets: [{
            data: dashboardSalesData,
            borderColor: '#27ae60',
            backgroundColor: 'rgba(39, 174, 96, .12)',
            borderWidth: 3,
            pointRadius: 0,
            pointHoverRadius: 5,
            pointBackgroundColor: '#27ae60',
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
                        return 'Sales: PHP ' + Number(context.parsed.y).toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
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
                    maxTicksLimit: 7
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
                        return 'PHP ' + Number(value).toLocaleString();
                    }
                }
            }
        }
    }
});
</script>

</body>
</html>
