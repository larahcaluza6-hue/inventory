<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$productPages = ['products.php', 'add_product.php', 'edit.php'];
$marketPages = ['orders.php', 'add_market_stock.php'];
$transactionPages = ['transactions.php'];
$isAdminUser = function_exists('is_admin') && is_admin();
?>

<div class="sidebar">
    <h2 class="logo">ALOHA STORE</h2>

    <ul>
        <li>
            <a class="<?php echo $currentPage == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">Dashboard</a>
        </li>
        <li>
            <a class="<?php echo in_array($currentPage, $productPages) ? 'active' : ''; ?>" href="products.php">Products</a>
        </li>
        <li>
            <a class="<?php echo in_array($currentPage, $marketPages) ? 'active' : ''; ?>" href="orders.php">Market</a>
        </li>
        <li>
            <a class="<?php echo in_array($currentPage, $transactionPages) ? 'active' : ''; ?>" href="transactions.php">Transactions</a>
        </li>
        <?php if ($isAdminUser) { ?>
            <li>
                <a class="<?php echo $currentPage == 'admin.php' ? 'active' : ''; ?>" href="admin.php">Admin</a>
            </li>
        <?php } ?>
        <li>
            <a href="logout.php">Logout</a>
        </li>
    </ul>
</div>
