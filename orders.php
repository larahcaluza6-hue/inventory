<?php
include 'db.php';
include 'auth.php';

$message = '';

if (isset($_GET['added'])) {
    $message = 'Market stock was added successfully.';
} elseif (isset($_GET['updated'])) {
    $message = 'Market product was updated successfully.';
} elseif (isset($_GET['deleted'])) {
    $message = 'Market product was deleted successfully.';
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$userId = (int) $_SESSION['user_id'];
$isPrintView = isset($_GET['export']) && $_GET['export'] === 'print';
$availableProducts = mysqli_query(
    $conn,
    "SELECT id, product_name, quantity, grams
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
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
</head>

<body class="<?php echo $isPrintView ? 'printable-page' : ''; ?>">

<?php if (!$isPrintView) { ?>
    <?php include 'sidebar.php'; ?>
<?php } ?>

<div class="main">
    <?php if ($isPrintView) { ?>
        <div class="print-export-header">
            <div>
                <h1>Market Inventory</h1>
                <p>Generated on <?php echo date('F j, Y'); ?></p>
            </div>
            <button type="button" class="toolbar-primary-btn print-action-btn" onclick="printExport()">Print</button>
        </div>
    <?php } ?>

    <?php if (!$isPrintView) { ?>
    <div class="inventory-toolbar">
        <h2 class="market-page-title">Market</h2>

        <label class="showing-control toolbar-showing-control">
            <span>Showing</span>
            <select class="form-select" aria-label="Showing amount">
                <option selected>10</option>
                <option>25</option>
                <option>50</option>
            </select>
        </label>

        <div class="inventory-toolbar-actions">
            <form method="GET" class="inventory-search">
                <input
                    type="text"
                    name="search"
                    class="form-control"
                    placeholder="Search market"
                    value="<?php echo htmlspecialchars($search); ?>"
                >
            </form>

            <button type="button" class="toolbar-btn">
                <span aria-hidden="true">▽</span>
                Filter
            </button>

            <a href="orders.php?<?php echo http_build_query(array_filter(['search' => $search, 'export' => 'print'])); ?>" class="toolbar-btn" target="_blank" rel="noopener" onclick="openPrintExport(this.href); return false;">
                <span aria-hidden="true">⇩</span>
                Print
            </a>

            <button type="button" class="toolbar-primary-btn" data-bs-toggle="modal" data-bs-target="#addMarketStockModal">
                <span aria-hidden="true">+</span>
                Add To Market
            </button>
        </div>
    </div>
    <?php } ?>

    <?php if (!$isPrintView && $message !== '') { ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php } ?>

    <?php if (!$isPrintView) { ?>
    <form method="GET" class="search-form show-search market-search legacy-search-form">
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
    <?php } ?>

    <div class="products-table-shell">
    <table class="products-table market-products-table compact-market-table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Product Name</th>
                <th>Brand</th>
                <th>Category</th>
                <th>Quantity</th>
                <th>Grams</th>
                <th>Price</th>
                <th>Status</th>
                <?php if (!$isPrintView) { ?>
                    <th class="text-end">Actions</th>
                <?php } ?>
            </tr>
        </thead>

        <tbody>
        <?php if (mysqli_num_rows($products) > 0) { ?>
            <?php $displayId = 1; ?>
            <?php while ($row = mysqli_fetch_assoc($products)) { ?>
                <?php
                $marketQuantity = (float) $row['market_quantity'];
                $marketGrams = (float) $row['market_grams'];
                $stockText = 'In Stock';
                $statusClass = 'success';

                if ($marketQuantity <= 0) {
                    $stockText = 'Out of Stock';
                    $statusClass = 'danger';
                } elseif ($marketQuantity < 10) {
                    $stockText = 'Low Stock';
                    $statusClass = 'warning';
                }

                $categoryKey = preg_replace('/[^a-z0-9]+/', '-', strtolower($row['category']));
                $imageFile = "assets/uploads/" . $row['image'];
                $hasImage = !empty($row['image']) && file_exists(__DIR__ . "/" . $imageFile);
                ?>

                <tr>
                    <td>
                        <?php if ($hasImage) { ?>
                            <img
                                src="<?php echo htmlspecialchars($imageFile); ?>"
                                class="product-thumb"
                                alt="<?php echo htmlspecialchars($row['product_name']); ?>"
                            >
                        <?php } else { ?>
                            <div class="product-thumb product-thumb-empty">No Image</div>
                        <?php } ?>
                    </td>

                    <td>
                        <div class="product-title"><?php echo htmlspecialchars($row['product_name']); ?></div>
                    </td>

                    <td><?php echo htmlspecialchars($row['brand']); ?></td>

                    <td>
                        <span class="soft-pill category-pill category-<?php echo htmlspecialchars($categoryKey); ?>">
                            <?php echo htmlspecialchars($row['category']); ?>
                        </span>
                    </td>

                    <td class="stock-count stock-<?php echo $statusClass; ?>"><?php echo format_quantity($marketQuantity); ?></td>

                    <td class="stock-count"><?php echo format_grams($marketGrams); ?></td>

                    <td class="price-cell">PHP <?php echo number_format((float) $row['price'], 2); ?></td>

                    <td>
                        <span class="soft-pill status-pill status-<?php echo $statusClass; ?>">
                            <?php echo $stockText; ?>
                        </span>
                    </td>

                    <?php if (!$isPrintView) { ?>
                    <td class="actions-cell">
                        <button
                            type="button"
                            class="product-action-btn edit-action"
                            data-bs-toggle="modal"
                            data-bs-target="#editMarketProductModal<?php echo (int) $row['id']; ?>"
                            aria-label="Edit <?php echo htmlspecialchars($row['product_name']); ?>"
                        >
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 20h9"></path>
                                <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                            </svg>
                        </button>

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

                        <a
                            href="delete_market.php?id=<?php echo (int) $row['id']; ?>"
                            class="product-action-btn delete-action"
                            onclick="return confirm('Are you sure you want to delete this market product?');"
                            aria-label="Delete <?php echo htmlspecialchars($row['product_name']); ?>"
                        >
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M3 6h18"></path>
                                <path d="M8 6V4h8v2"></path>
                                <path d="M19 6l-1 14H6L5 6"></path>
                                <path d="M10 11v6"></path>
                                <path d="M14 11v6"></path>
                            </svg>
                        </a>

                        <div class="modal fade text-start" id="editMarketProductModal<?php echo (int) $row['id']; ?>" tabindex="-1" aria-labelledby="editMarketProductModalLabel<?php echo (int) $row['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form action="edit_market.php?id=<?php echo (int) $row['id']; ?>" method="POST" enctype="multipart/form-data">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editMarketProductModalLabel<?php echo (int) $row['id']; ?>">Edit Market Product</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <input
                                                type="text"
                                                name="product_name"
                                                class="form-control mb-3"
                                                placeholder="Product Name"
                                                value="<?php echo htmlspecialchars($row['product_name']); ?>"
                                                required
                                            >

                                            <input
                                                type="text"
                                                name="category"
                                                class="form-control mb-3"
                                                placeholder="Category"
                                                value="<?php echo htmlspecialchars($row['category']); ?>"
                                                required
                                            >

                                            <input
                                                type="text"
                                                name="brand"
                                                class="form-control mb-3"
                                                placeholder="Brand"
                                                value="<?php echo htmlspecialchars($row['brand']); ?>"
                                                required
                                            >

                                            <div class="input-group mb-3">
                                                <input
                                                    type="number"
                                                    step="1"
                                                    name="market_quantity"
                                                    class="form-control"
                                                    min="0"
                                                    placeholder="Market Stock"
                                                    value="<?php echo htmlspecialchars($row['market_quantity']); ?>"
                                                    required
                                                >
                                            </div>

                                            <div class="input-group mb-3">
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    name="market_grams"
                                                    class="form-control"
                                                    min="0"
                                                    placeholder="Market Grams"
                                                    value="<?php echo htmlspecialchars($row['market_grams']); ?>"
                                                    required
                                                >
                                            </div>

                                            <input
                                                type="number"
                                                step="0.01"
                                                name="price"
                                                class="form-control mb-3"
                                                placeholder="Price"
                                                value="<?php echo htmlspecialchars($row['price']); ?>"
                                                required
                                            >

                                            <label class="form-label">Product Image</label>
                                            <input type="file" name="image" class="form-control" accept="image/*">
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" name="update" class="btn btn-success">Update Product</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

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
                                                <dt>Grams</dt>
                                                <dd><?php echo format_grams($marketGrams); ?></dd>
                                            </div>
                                            <div>
                                                <dt>Quantity</dt>
                                                <dd><?php echo format_quantity($marketQuantity); ?></dd>
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
                    <?php } ?>
                </tr>
                <?php $displayId++; ?>
            <?php } ?>
        <?php } else { ?>
            <tr>
                <td colspan="<?php echo $isPrintView ? 8 : 9; ?>" class="empty-products">No products found.</td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
    </div>

</div>

<?php if (!$isPrintView) { ?>
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
                            </option>
                        <?php } ?>
                    </select>

                    <div class="input-group">
                        <input
                            type="number"
                            step="1"
                            name="store_quantity"
                            class="form-control"
                            min="1"
                            placeholder="Quantity"
                            required
                        >
                    </div>

                    <div class="input-group mt-3">
                        <input
                            type="number"
                            step="0.01"
                            name="store_grams"
                            class="form-control"
                            min="0"
                            placeholder="Grams"
                            required
                        >
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="store" class="btn btn-success">Add Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php } ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($isPrintView) { ?>
<script>
let printStarted = false;

function printExport() {
    if (printStarted) {
        return;
    }

    printStarted = true;
    window.print();
}

window.addEventListener('load', function () {
    printExport();
});

window.addEventListener('afterprint', function () {
    window.close();
});
</script>
<?php } else { ?>
<script>
function openPrintExport(url) {
    window.open(url, 'printExport', 'width=1100,height=800');
}
</script>
<?php } ?>
</body>
</html>
