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

?>

<!DOCTYPE html>
<html>
<head>
    <title>Inventory Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
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
</div>

</body>
</html>
