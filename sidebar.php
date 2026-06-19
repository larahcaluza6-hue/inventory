<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$productPages = ['products.php', 'add_product.php', 'edit.php'];
$marketPages = ['orders.php', 'add_market_stock.php'];
$transactionPages = ['transactions.php'];
$isAdminUser = function_exists('is_admin') && is_admin();
$profileName = $_SESSION['fullname'] ?? 'User';
$profileRole = $isAdminUser ? 'Administrator' : 'User';
$profileInitial = strtoupper(substr(trim($profileName), 0, 1));

if ($profileInitial === '') {
    $profileInitial = 'U';
}
?>

<div class="sidebar">
    <h2 class="logo">
        <span class="logo-mark">AS</span>
        <span class="logo-text">HANNAH STORE</span>
    </h2>

    <ul>
        <li>
            <a class="<?php echo (!$isAdminUser && $currentPage == 'dashboard.php') || ($isAdminUser && $currentPage == 'admin.php') ? 'active' : ''; ?>" href="<?php echo $isAdminUser ? 'admin.php' : 'dashboard.php'; ?>">
                <span class="nav-mark">D</span>
                <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <rect x="3" y="3" width="7" height="8"></rect>
                    <rect x="14" y="3" width="7" height="5"></rect>
                    <rect x="14" y="12" width="7" height="9"></rect>
                    <rect x="3" y="15" width="7" height="6"></rect>
                </svg>
                <span class="nav-label">Dashboard</span>
            </a>
        </li>
        <li>
            <a class="<?php echo in_array($currentPage, $productPages) ? 'active' : ''; ?>" href="products.php">
                <span class="nav-mark">P</span>
                <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M6 8h12l-1 13H7L6 8Z"></path>
                    <path d="M9 8V6a3 3 0 0 1 6 0v2"></path>
                    <path d="M9 13h6"></path>
                </svg>
                <span class="nav-label">Products</span>
            </a>
        </li>
        <li>
            <a class="<?php echo in_array($currentPage, $marketPages) ? 'active' : ''; ?>" href="orders.php">
                <span class="nav-mark">M</span>
                <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4 10h16l-2 10H6L4 10Z"></path>
                    <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                    <path d="M9 14h.01"></path>
                    <path d="M15 14h.01"></path>
                </svg>
                <span class="nav-label">Market</span>
            </a>
        </li>
        <li>
            <a class="<?php echo in_array($currentPage, $transactionPages) ? 'active' : ''; ?>" href="transactions.php">
                <span class="nav-mark">T</span>
                <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M7 7h11l-3-3"></path>
                    <path d="M17 17H6l3 3"></path>
                    <path d="M18 7v5"></path>
                    <path d="M6 12v5"></path>
                </svg>
                <span class="nav-label">Transactions</span>
            </a>
        </li>
    </ul>
</div>

<header class="app-topbar" aria-label="User profile">
    <details class="topbar-profile">
        <summary class="profile-summary">
            <span class="profile-avatar" aria-hidden="true">
                <?php echo htmlspecialchars($profileInitial); ?>
            </span>

            <span class="profile-copy">
                <strong><?php echo htmlspecialchars($profileName); ?></strong>
                <span><?php echo htmlspecialchars($profileRole); ?></span>
            </span>

            <span class="profile-chevron" aria-hidden="true">⌄</span>
        </summary>

        <div class="profile-menu">
            <a class="profile-logout" href="logout.php">Logout</a>
        </div>
    </details>
</header>
