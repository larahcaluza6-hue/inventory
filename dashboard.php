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

$low_stock_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE user_id = $userId AND quantity > 0 AND quantity < 10");
$low_stock_data = mysqli_fetch_assoc($low_stock_query);

$currentYear = (int) date('Y');
$currentMonth = (int) date('n');
$salesLabels = [];
$salesData = [];

for ($month = 1; $month <= $currentMonth; $month++) {
    $salesLabels[$month] = date('M', mktime(0, 0, 0, $month, 1));
    $salesData[$month] = 0;
}

$sales_query = mysqli_query(
    $conn,
    "SELECT MONTH(created_at) as sale_month, COALESCE(SUM(total_amount), 0) as total
     FROM product_sales
     WHERE user_id = $userId
       AND YEAR(created_at) = $currentYear
     GROUP BY MONTH(created_at)"
);

while ($sales_row = mysqli_fetch_assoc($sales_query)) {
    $saleMonth = (int) $sales_row['sale_month'];

    if (isset($salesData[$saleMonth])) {
        $salesData[$saleMonth] = (float) $sales_row['total'];
    }
}

$salesChartLabels = array_values($salesLabels);
$salesChartData = array_values($salesData);
$totalSales = array_sum($salesChartData);
$salesChartPercentages = array_map(
    function ($total) use ($totalSales) {
        return $totalSales > 0 ? round(($total / $totalSales) * 100, 1) : 0;
    },
    $salesChartData
);
$highestSales = max($salesChartData);
$salesChartColors = array_map(
    function ($total) use ($highestSales) {
        return $highestSales > 0 && $total === $highestSales ? '#82b3ed' : '#334cf5';
    },
    $salesChartData
);
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

<div class="main">
    <h2 class="mb-4">Dashboard</h2>

    <div class="row dashboard-cards">
        <div class="col-lg-3 col-sm-6">
            <div class="card-box blue">
                <h5>Total Products</h5>
                <h1><?php echo (int) $product_data['total']; ?></h1>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6">
            <div class="card-box green">
                <h5>Product Stock (g)</h5>
                <h1><?php echo format_grams($available_data['total']); ?></h1>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6">
            <div class="card-box blue">
                <h5>Market Stock (g)</h5>
                <h1><?php echo format_grams($market_stock_data['total']); ?></h1>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6">
            <div class="card-box red">
                <h5>Sold Out</h5>
                <h1><?php echo (int) $sold_out_data['total']; ?></h1>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6">
            <a href="products.php?stock=low" class="dashboard-card-link">
                <div class="card-box blue">
                    <h5>Low Stock</h5>
                    <h1><?php echo (int) $low_stock_data['total']; ?></h1>
                </div>
            </a>
        </div>

        <div class="col-lg-3 col-sm-6">
            <a href="sales.php" class="dashboard-card-link">
                <div class="card-box green">
                    <h5>Total Sales Amount</h5>
                    <h1>PHP <?php echo number_format((float) $sales_amount_data['total'], 2); ?></h1>
                </div>
            </a>
        </div>
    </div>

    <section class="sales-chart-section" aria-label="Sales by month chart">
        <div class="sales-chart-title">
            <span>Sales</span>
            <span>By Month</span>
        </div>

        <div class="sales-chart-wrap">
            <canvas id="salesByMonthChart"></canvas>
        </div>
    </section>
</div>

<script>
const salesChartLabels = <?php echo json_encode($salesChartLabels); ?>;
const salesChartData = <?php echo json_encode($salesChartData); ?>;
const salesChartPercentages = <?php echo json_encode($salesChartPercentages); ?>;
const salesChartColors = <?php echo json_encode($salesChartColors); ?>;

new Chart(document.getElementById('salesByMonthChart'), {
    type: 'bar',
    data: {
        labels: salesChartLabels,
        datasets: [{
            data: salesChartData,
            backgroundColor: salesChartColors,
            borderWidth: 0,
            borderRadius: 8,
            barPercentage: 0.64,
            categoryPercentage: 0.76
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
                        return 'Sales: PHP ' + context.parsed.y.toFixed(2) + ' (' + salesChartPercentages[context.dataIndex] + '%)';
                    }
                }
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                },
                border: {
                    color: '#aab5c5',
                    width: 4
                },
                ticks: {
                    color: '#09091a',
                    font: {
                        size: 24
                    }
                }
            },
            y: {
                beginAtZero: true,
                grid: {
                    color: '#b7bdc9',
                    lineWidth: 1.4
                },
                border: {
                    color: '#aab5c5',
                    width: 4
                },
                ticks: {
                    display: false,
                    precision: 2
                }
            }
        }
    },
    plugins: [{
        id: 'percentageLabels',
        afterDatasetsDraw: function (chart) {
            const ctx = chart.ctx;
            const dataset = chart.data.datasets[0];
            const meta = chart.getDatasetMeta(0);

            ctx.save();
            ctx.fillStyle = '#09091a';
            ctx.font = '600 15px Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'bottom';

            meta.data.forEach(function (bar, index) {
                if (dataset.data[index] <= 0) {
                    return;
                }

                ctx.fillText(salesChartPercentages[index] + '%', bar.x, bar.y - 8);
            });

            ctx.restore();
        }
    }]
});
</script>

</body>
</html>
