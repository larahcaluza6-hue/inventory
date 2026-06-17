<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$productPages = ['products.php', 'add_product.php', 'edit.php'];
$marketPages = ['orders.php', 'add_market_stock.php'];
$transactionPages = ['transactions.php'];
$isAdminUser = function_exists('is_admin') && is_admin();
?>

<div class="sidebar">
    <h2 class="logo">
        <span class="logo-mark">AS</span>
        <span class="logo-text">ALOHA STORE</span>
    </h2>

    <ul>
        <li>
            <a class="<?php echo $currentPage == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                <span class="nav-mark">D</span>
                <span class="nav-label">Dashboard</span>
            </a>
        </li>
        <li>
            <a class="<?php echo in_array($currentPage, $productPages) ? 'active' : ''; ?>" href="products.php">
                <span class="nav-mark">P</span>
                <span class="nav-label">Products</span>
            </a>
        </li>
        <li>
            <a class="<?php echo in_array($currentPage, $marketPages) ? 'active' : ''; ?>" href="orders.php">
                <span class="nav-mark">M</span>
                <span class="nav-label">Market</span>
            </a>
        </li>
        <li>
            <a class="<?php echo in_array($currentPage, $transactionPages) ? 'active' : ''; ?>" href="transactions.php">
                <span class="nav-mark">T</span>
                <span class="nav-label">Transactions</span>
            </a>
        </li>
        <?php if ($isAdminUser) { ?>
            <li>
                <a class="<?php echo $currentPage == 'admin.php' ? 'active' : ''; ?>" href="admin.php">
                    <span class="nav-mark">A</span>
                    <span class="nav-label">Admin</span>
                </a>
            </li>
        <?php } ?>
        <li>
            <a href="logout.php">
                <span class="nav-mark">L</span>
                <span class="nav-label">Logout</span>
            </a>
        </li>
    </ul>
</div>
