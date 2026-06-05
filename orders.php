<?php
include 'db.php';
include 'auth.php';

$message = isset($_GET['added']) ? 'Market stock was added successfully.' : '';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$userId = (int) $_SESSION['user_id'];
$availableProducts = mysqli_query(
    $conn,
    "SELECT id, product_name, quantity
     FROM products
     WHERE user_id = $userId
       AND quantity > 0
     ORDER BY product_name ASC"
);

if ($search !== '') {
    $safeSearch = mysqli_real_escape_string($conn, $search);
    $products = mysqli_query(
        $conn,
        "SELECT * FROM products
         WHERE user_id = $userId
           AND (
                product_name LIKE '%$safeSearch%'
             OR category LIKE '%$safeSearch%'
             OR brand LIKE '%$safeSearch%'
           )
         ORDER BY product_name ASC"
    );
} else {
    $products = mysqli_query($conn, "SELECT * FROM products WHERE user_id = $userId ORDER BY product_name ASC");
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Market</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="main">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Market</h2>

        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMarketStockModal">
            Add To Market
        </button>
    </div>

    <?php if ($message !== '') { ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php } ?>

    <form method="GET" class="search-form show-search market-search">
        <input
            type="text"
            name="search"
            class="form-control search-input"
            placeholder="Search market"
            value="<?php echo htmlspecialchars($search); ?>"
        >

        <button type="submit" class="btn btn-primary search-icon-btn" aria-label="Search">
            &#128269;
        </button>

        <?php if ($search !== '') { ?>
            <a href="orders.php" class="btn btn-secondary">Back</a>
        <?php } ?>
    </form>

    <div class="products-table-shell">
    <table class="products-table market-products-table compact-market-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Product Stock</th>
                <th>Market</th>
                <th>Price</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>

        <tbody>
        <?php if (mysqli_num_rows($products) > 0) { ?>
            <?php $displayId = 1; ?>
            <?php while ($row = mysqli_fetch_assoc($products)) { ?>
                <?php
                $quantity = (int) $row['quantity'];
                $marketQuantity = (int) $row['market_quantity'];
                $stockText = 'In Stock';
                $statusClass = 'success';

                if ($quantity === 0) {
                    $stockText = 'Out of Stock';
                    $statusClass = 'danger';
                } elseif ($quantity < 10) {
                    $stockText = 'Low Stock';
                    $statusClass = 'warning';
                }

                $categoryKey = preg_replace('/[^a-z0-9]+/', '-', strtolower($row['category']));
                $imageFile = "assets/uploads/" . $row['image'];
                $hasImage = !empty($row['image']) && file_exists(__DIR__ . "/" . $imageFile);
                ?>

                <tr>
                    <td>
                        <div class="product-cell">
                            <?php if ($hasImage) { ?>
                                <img
                                    src="<?php echo htmlspecialchars($imageFile); ?>"
                                    class="product-thumb"
                                    alt="<?php echo htmlspecialchars($row['product_name']); ?>"
                                >
                            <?php } else { ?>
                                <div class="product-thumb product-thumb-empty">No Image</div>
                            <?php } ?>

                            <div>
                                <div class="product-title"><?php echo htmlspecialchars($row['product_name']); ?></div>
                                <div class="product-brand"><?php echo htmlspecialchars($row['brand']); ?></div>
                            </div>
                        </div>
                    </td>

                    <td>
                        <span class="soft-pill category-pill category-<?php echo htmlspecialchars($categoryKey); ?>">
                            <?php echo htmlspecialchars($row['category']); ?>
                        </span>
                    </td>

                    <td>
                        <div class="stock-count stock-<?php echo $statusClass; ?>"><?php echo $quantity; ?></div>
                        <div class="stock-label"><?php echo $stockText; ?></div>
                    </td>

                    <td class="stock-count stock-success"><?php echo $marketQuantity; ?></td>

                    <td class="price-cell">PHP <?php echo number_format((float) $row['price'], 2); ?></td>

                    <td>
                        <span class="soft-pill status-pill status-<?php echo $statusClass; ?>">
                            <?php echo $stockText; ?>
                        </span>
                    </td>

                    <td class="actions-cell">
                        <button
                            type="button"
                            class="product-action-btn view-action"
                            data-bs-toggle="modal"
                            data-bs-target="#viewMarketProductModal<?php echo (int) $row['id']; ?>"
                            aria-label="View <?php echo htmlspecialchars($row['product_name']); ?>"
                        >
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>

                        <div class="modal fade text-start" id="viewMarketProductModal<?php echo (int) $row['id']; ?>" tabindex="-1" aria-labelledby="viewMarketProductModalLabel<?php echo (int) $row['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="viewMarketProductModalLabel<?php echo (int) $row['id']; ?>">Market Product Details</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="product-detail-head">
                                            <?php if ($hasImage) { ?>
                                                <img src="<?php echo htmlspecialchars($imageFile); ?>" class="product-detail-image" alt="<?php echo htmlspecialchars($row['product_name']); ?>">
                                            <?php } else { ?>
                                                <div class="product-detail-image product-thumb-empty">No Image</div>
                                            <?php } ?>
                                            <div>
                                                <h4><?php echo htmlspecialchars($row['product_name']); ?></h4>
                                                <p><?php echo htmlspecialchars($row['brand']); ?></p>
                                            </div>
                                        </div>

                                        <dl class="product-detail-list">
                                            <div>
                                                <dt>Category</dt>
                                                <dd><?php echo htmlspecialchars($row['category']); ?></dd>
                                            </div>
                                            <div>
                                                <dt>Product Stock</dt>
                                                <dd><?php echo $quantity; ?> - <?php echo $stockText; ?></dd>
                                            </div>
                                            <div>
                                                <dt>Market Stock</dt>
                                                <dd><?php echo $marketQuantity; ?></dd>
                                            </div>
                                            <div>
                                                <dt>Price</dt>
                                                <dd>PHP <?php echo number_format((float) $row['price'], 2); ?></dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php $displayId++; ?>
            <?php } ?>
        <?php } else { ?>
            <tr>
                <td colspan="7" class="empty-products">No products found.</td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
    </div>

</div>

<div class="modal fade" id="addMarketStockModal" tabindex="-1" aria-labelledby="addMarketStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="add_market_stock.php" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="addMarketStockModalLabel">Add To Market</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <select name="product_id" class="form-control mb-3" required>
                        <option value="">Select Product</option>
                        <?php while ($availableProduct = mysqli_fetch_assoc($availableProducts)) { ?>
                            <option value="<?php echo (int) $availableProduct['id']; ?>">
                                <?php echo htmlspecialchars($availableProduct['product_name']); ?>
                                (Stock: <?php echo (int) $availableProduct['quantity']; ?>)
                            </option>
                        <?php } ?>
                    </select>

                    <input
                        type="number"
                        name="store_quantity"
                        class="form-control"
                        min="1"
                        placeholder="Quantity"
                        required
                    >
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="store" class="btn btn-success">Add Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
