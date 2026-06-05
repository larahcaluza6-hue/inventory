<?php
include 'db.php';
include 'auth.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$stockFilter = isset($_GET['stock']) ? $_GET['stock'] : '';

if ($stockFilter === 'low') {
    $result = mysqli_query(
        $conn,
        "SELECT * FROM products
         WHERE quantity > 0 AND quantity < 10
         ORDER BY quantity ASC, id ASC"
    );
} elseif ($search !== '') {
    $safeSearch = mysqli_real_escape_string($conn, $search);
    $result = mysqli_query(
        $conn,
        "SELECT * FROM products
         WHERE product_name LIKE '%$safeSearch%'
            OR category LIKE '%$safeSearch%'
            OR brand LIKE '%$safeSearch%'
            OR status LIKE '%$safeSearch%'
            OR quantity LIKE '%$safeSearch%'
            OR price LIKE '%$safeSearch%'
         ORDER BY id ASC"
    );
} else {
    $result = mysqli_query($conn, "SELECT * FROM products ORDER BY id ASC");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Products</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="main">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Products</h2>

        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
            Add Product Stock         
        </button>
    </div>

    <?php if (isset($_GET['duplicate'])) { ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            Product <strong><?php echo htmlspecialchars($_GET['duplicate']); ?></strong> already exists.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php } ?>

    <?php if ($stockFilter === 'low') { ?>
        <div class="alert alert-info d-flex justify-content-between align-items-center" role="alert">
            <span>Showing products with stock less than 10.</span>
            <a href="products.php" class="btn btn-sm btn-secondary">Back</a>
        </div>
    <?php } ?>

    <form method="GET" class="search-form <?php echo $search !== '' ? 'show-search' : ''; ?>" id="productSearchForm">
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

    <div class="products-table-shell">
    <table class="products-table product-list-table">

        <thead>
            <tr>
                <th>Product</th>
                <th>Brand</th>
                <th>Category</th>
                <th>Stock</th>
                <th>Price</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>

        <tbody>

        <?php if (mysqli_num_rows($result) > 0) { ?>
            <?php $displayId = 1; ?>
            <?php while($row = mysqli_fetch_assoc($result)) { ?>
            <?php
                $quantity = (int) $row['quantity'];
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

                        <div class="product-title"><?php echo htmlspecialchars($row['product_name']); ?></div>
                    </div>
                </td>

                <td class="product-brand-cell"><?php echo htmlspecialchars($row['brand']); ?></td>

                <td>
                    <span class="soft-pill category-pill category-<?php echo htmlspecialchars($categoryKey); ?>">
                        <?php echo htmlspecialchars($row['category']); ?>
                    </span>
                </td>

                <td>
                    <div class="stock-count stock-<?php echo $statusClass; ?>"><?php echo $quantity; ?></div>
                    <div class="stock-label"><?php echo $stockText; ?></div>
                </td>

                <td class="price-cell">PHP <?php echo number_format((float) $row['price'], 2); ?></td>

                <td>
                    <span class="soft-pill status-pill status-<?php echo $statusClass; ?>">
                        <?php echo $stockText; ?>
                    </span>
                </td>

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
                                            <dt>Brand</dt>
                                            <dd><?php echo htmlspecialchars($row['brand']); ?></dd>
                                        </div>
                                        <div>
                                            <dt>Category</dt>
                                            <dd><?php echo htmlspecialchars($row['category']); ?></dd>
                                        </div>
                                        <div>
                                            <dt>Stock</dt>
                                            <dd><?php echo $quantity; ?> - <?php echo $stockText; ?></dd>
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

                                        <input
                                            type="number"
                                            name="quantity"
                                            class="form-control mb-3"
                                            placeholder="Quantity"
                                            value="<?php echo htmlspecialchars($row['quantity']); ?>"
                                            required
                                        >

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

                    <input type="number" name="quantity" class="form-control mb-3" placeholder="Quantity" required>

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const searchForm = document.getElementById('productSearchForm');
const searchToggle = document.getElementById('searchToggle');
const searchInput = document.getElementById('productSearchInput');

searchToggle.addEventListener('click', function (event) {
    if (!searchForm.classList.contains('show-search')) {
        event.preventDefault();
        searchForm.classList.add('show-search');
        searchInput.focus();
    }
});
</script>
</body>
</html>
