<?php
include 'db.php';
include 'auth.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$stockFilter = isset($_GET['stock']) ? $_GET['stock'] : '';
$userId = (int) $_SESSION['user_id'];
$isAdminUser = is_admin();
$isPrintView = isset($_GET['export']) && $_GET['export'] === 'print';

if ($stockFilter === 'low') {
    $query = "SELECT products.*, users.fullname AS owner_name
              FROM products
              LEFT JOIN users ON users.id = products.user_id
              WHERE quantity > 0
                AND quantity < 10";

    if (!$isAdminUser) {
        $query .= " AND products.user_id = ?";
    }

    $query .= " ORDER BY quantity ASC, products.id ASC";
    $stmt = mysqli_prepare($conn, $query);

    if (!$isAdminUser) {
        mysqli_stmt_bind_param($stmt, "i", $userId);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} elseif ($search !== '') {
    $searchTerm = '%' . $search . '%';
    $query = "SELECT products.*, users.fullname AS owner_name
              FROM products
              LEFT JOIN users ON users.id = products.user_id
              WHERE (
                    product_name LIKE ?
                 OR category LIKE ?
                 OR brand LIKE ?
                 OR status LIKE ?
                 OR quantity LIKE ?
                 OR grams LIKE ?
                 OR price LIKE ?
              )";

    if (!$isAdminUser) {
        $query .= " AND products.user_id = ?";
    }

    $query .= " ORDER BY products.id ASC";
    $stmt = mysqli_prepare($conn, $query);

    if ($isAdminUser) {
        mysqli_stmt_bind_param($stmt, "sssssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    } else {
        mysqli_stmt_bind_param($stmt, "sssssssi", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $userId);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    if ($isAdminUser) {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT products.*, users.fullname AS owner_name
             FROM products
             LEFT JOIN users ON users.id = products.user_id
             ORDER BY products.id ASC"
        );
    } else {
        $stmt = mysqli_prepare(
            $conn,
            "SELECT products.*, users.fullname AS owner_name
             FROM products
             LEFT JOIN users ON users.id = products.user_id
             WHERE products.user_id = ?
             ORDER BY products.id ASC"
        );
        mysqli_stmt_bind_param($stmt, "i", $userId);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Products</title>
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
                <h1>Product Inventory</h1>
                <p>Generated on <?php echo date('F j, Y'); ?></p>
            </div>
            <button type="button" class="toolbar-primary-btn print-action-btn" onclick="printExport()">Print</button>
        </div>
    <?php } ?>

    <?php if (!$isPrintView) { ?>
    <div class="inventory-toolbar">
        <h2 class="inventory-page-title">Product</h2>

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
                    placeholder="Search product"
                    value="<?php echo htmlspecialchars($search); ?>"
                >
                <?php if ($stockFilter !== '') { ?>
                    <input type="hidden" name="stock" value="<?php echo htmlspecialchars($stockFilter); ?>">
                <?php } ?>
                <?php if ($search !== '') { ?>
                    <a
                        href="products.php?<?php echo http_build_query(array_filter(['stock' => $stockFilter])); ?>"
                        class="search-clear-btn"
                        aria-label="Clear search"
                        title="Clear search"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M18 6 6 18"></path>
                            <path d="m6 6 12 12"></path>
                        </svg>
                    </a>
                <?php } ?>
            </form>

            <a href="products.php?<?php echo http_build_query(array_filter(['search' => $search, 'stock' => $stockFilter, 'export' => 'print'])); ?>" class="toolbar-btn" target="_blank" rel="noopener" onclick="openPrintExport(this.href); return false;">
                <span aria-hidden="true">⇩</span>
                Print
            </a>

            <button type="button" class="toolbar-primary-btn" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <span aria-hidden="true">+</span>
                Add New Product
            </button>
        </div>
    </div>
    <?php } ?>

    <?php if (!$isPrintView && isset($_GET['duplicate'])) { ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            Product <strong><?php echo htmlspecialchars($_GET['duplicate']); ?></strong> already exists.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php } ?>

    <?php if (!$isPrintView && isset($_GET['duplicate_grams'])) { ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            This product with <strong><?php echo htmlspecialchars($_GET['duplicate_grams']); ?></strong> grams already exists.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php } ?>

    <?php if (!$isPrintView && isset($_GET['combined'])) { ?>
        <div class="alert alert-success alert-dismissible fade show sale-success-notification" id="productSuccessNotification" role="alert">
            Same product found. Quantity was added to the existing product.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php } ?>

    <?php if (!$isPrintView && isset($_GET['error'])) { ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php } ?>

    <?php if (!$isPrintView && $stockFilter === 'low') { ?>
        <div class="alert alert-info d-flex justify-content-between align-items-center" role="alert">
            <span>Showing products with stock less than 10.</span>
            <a href="products.php" class="btn btn-sm btn-secondary">Back</a>
        </div>
    <?php } ?>

    <?php if (!$isPrintView) { ?>
    <form method="GET" class="search-form <?php echo $search !== '' ? 'show-search' : ''; ?> legacy-search-form" id="productSearchForm">
        <input
            type="text"
            name="search"
            class="form-control search-input"
            id="productSearchInput"
            placeholder="Search product"
            value="<?php echo htmlspecialchars($search); ?>"
        >
        <button type="submit" class="btn btn-primary search-icon-btn" id="searchToggle" aria-label="Search">
            &#128269;
        </button>
        <?php if ($search !== '') { ?>
            <a href="products.php" class="btn btn-secondary">
                Back
            </a>
        <?php } ?>
    </form>
    <?php } ?>

    <div class="products-table-shell">
    <table class="products-table">

        <thead>
            <tr>
                <th>Image</th>
                <th>Product Name</th>
                <th>Brand</th>
                <th>Quantity</th>
                <th>Grams</th>
                <th>Price</th>
                <th>Category</th>
                <?php if ($isAdminUser) { ?>
                    <th>Owner</th>
                <?php } ?>
                <th>Status</th>
                <?php if (!$isPrintView) { ?>
                    <th class="text-end">Actions</th>
                <?php } ?>
            </tr>
        </thead>

        <tbody>

        <?php if (mysqli_num_rows($result) > 0) { ?>
            <?php $displayId = 1; ?>
            <?php while($row = mysqli_fetch_assoc($result)) { ?>
            <?php
                $quantity = (float) $row['quantity'];
                $grams = (float) $row['grams'];
                $stockText = 'In Stock';
                $statusClass = 'success';

                if ($quantity <= 0) {
                    $stockText = 'Out of Stock';
                    $statusClass = 'danger';
                } elseif ($quantity < 10) {
                    $stockText = 'Low Stock';
                    $statusClass = 'warning';
                }

                $categoryKey = preg_replace('/[^a-z0-9]+/', '', strtolower($row['category']));
                $imageFile = "assets/uploads/" . $row['image'];
                $hasImage = !empty($row['image']) && file_exists(__DIR__ . "/" . $imageFile);
            ?>

            <tr>
                <td>
                    <?php if ($hasImage) { ?>
                        <img
                            src="<?php echo htmlspecialchars($imageFile); ?>"
                            class="product-thumb table-image-only"
                            alt="<?php echo htmlspecialchars($row['product_name']); ?>"
                        >
                    <?php } else { ?>
                        <div class="product-thumb product-thumb-empty table-image-only">No Image</div>
                    <?php } ?>
                </td>

                <td>
                    <div class="product-title"><?php echo htmlspecialchars($row['product_name']); ?></div>
                </td>

                <td><?php echo htmlspecialchars($row['brand']); ?></td>

                <td>
                    <div class="stock-count stock-<?php echo $statusClass; ?>"><?php echo format_quantity($quantity); ?></div>
                </td>

                <td>
                    <div class="stock-count"><?php echo format_grams($grams); ?></div>
                </td>

                <td class="price-cell">PHP <?php echo number_format((float) $row['price'], 2); ?></td>

                <td>
                    <span class="soft-pill category-pill category-<?php echo htmlspecialchars($categoryKey); ?>">
                        <?php echo htmlspecialchars($row['category']); ?>
                    </span>
                </td>

                <?php if ($isAdminUser) { ?>
                    <td><?php echo htmlspecialchars($row['owner_name'] ?? 'Unknown User'); ?></td>
                <?php } ?>

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
                        data-bs-target="#editProductModal<?php echo (int) $row['id']; ?>"
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
                        data-bs-target="#viewProductModal<?php echo (int) $row['id']; ?>"
                        aria-label="View <?php echo htmlspecialchars($row['product_name']); ?>"
                    >
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>

                    <a
                        href="delete.php?id=<?php echo $row['id']; ?>"
                        class="product-action-btn delete-action"
                        onclick="return confirm('Are you sure you want to delete this product?');"
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

                    <div class="modal fade text-start" id="viewProductModal<?php echo (int) $row['id']; ?>" tabindex="-1" aria-labelledby="viewProductModalLabel<?php echo (int) $row['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="viewProductModalLabel<?php echo (int) $row['id']; ?>">Product Details</h5>
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
                                            <dd><?php echo format_grams($grams); ?></dd>
                                        </div>
                                        <div>
                                            <dt>Quantity</dt>
                                            <dd><?php echo format_quantity($quantity); ?></dd>
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

                    <div class="modal fade text-start" id="editProductModal<?php echo (int) $row['id']; ?>" tabindex="-1" aria-labelledby="editProductModalLabel<?php echo (int) $row['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form action="edit.php?id=<?php echo (int) $row['id']; ?>" method="POST" enctype="multipart/form-data">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editProductModalLabel<?php echo (int) $row['id']; ?>">Edit Product</h5>
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
                                                min="1"
                                                name="quantity"
                                                class="form-control"
                                                placeholder="Quantity"
                                                value="<?php echo htmlspecialchars($row['quantity']); ?>"
                                                required
                                            >
                                        </div>

                                        <div class="input-group mb-3">
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0.01"
                                                name="grams"
                                                class="form-control"
                                                placeholder="Grams"
                                                value="<?php echo htmlspecialchars($row['grams']); ?>"
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
                </td>
                <?php } ?>
            </tr>

        <?php $displayId++; ?>
        <?php } ?>
        <?php } else { ?>
            <tr>
                <td colspan="<?php echo $isAdminUser ? ($isPrintView ? 9 : 10) : ($isPrintView ? 8 : 9); ?>" class="empty-products">No products found.</td>
            </tr>
        <?php } ?>

        </tbody>

    </table>
    </div>

</div>

<?php if (!$isPrintView) { ?>
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="add_product.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">Add Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <input type="text" name="product_name" class="form-control mb-3" placeholder="Product Name" required>

                    <input type="text" name="category" class="form-control mb-3" placeholder="Category" required>

                    <input type="text" name="brand" class="form-control mb-3" placeholder="Brand" required>

                    <div class="input-group mb-3">
                        <input type="number" step="1" min="1" name="quantity" class="form-control" placeholder="Quantity" required>
                    </div>

                    <div class="input-group mb-3">
                        <input type="number" step="0.01" min="0.01" name="grams" class="form-control" placeholder="Grams" required>
                    </div>

                    <input type="number" step="0.01" name="price" class="form-control mb-3" placeholder="Price" required>

                    <input type="file" name="image" class="form-control" accept="image/*" required>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add" class="btn btn-success">Add Product</button>
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
const searchForm = document.getElementById('productSearchForm');
const searchToggle = document.getElementById('searchToggle');
const searchInput = document.getElementById('productSearchInput');

function openPrintExport(url) {
    window.open(url, 'printExport', 'width=1100,height=800');
}

searchToggle.addEventListener('click', function (event) {
    if (!searchForm.classList.contains('show-search')) {
        event.preventDefault();
        searchForm.classList.add('show-search');
        searchInput.focus();
    }
});
</script>
<?php } ?>
</body>
</html>
