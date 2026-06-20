<?php
include 'db.php';
include 'auth.php';

$userId = (int) $_SESSION['user_id'];
$isAdminUser = is_admin();
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$isPrintView = isset($_GET['export']) && $_GET['export'] === 'print';
$message = '';
$error = '';

if ($isAdminUser) {
    $saleTransactionsResult = mysqli_query(
        $conn,
        "SELECT
            ps.id,
            ps.customer_name,
            ps.quantity,
            ps.grams,
            ps.unit_price,
            ps.total_amount,
            ps.created_at,
            p.product_name,
            p.brand,
            users.fullname,
            users.email
         FROM product_sales ps
         LEFT JOIN products p ON p.id = ps.product_id
         LEFT JOIN users ON users.id = ps.user_id
         ORDER BY ps.created_at DESC, ps.id DESC
         LIMIT 100"
    );
    ?>

    <!DOCTYPE html>
    <html>
    <head>
        <title>Sales</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
    </head>

    <body>

    <?php include 'sidebar.php'; ?>

    <div class="main">
        <div class="inventory-toolbar">
            <h2 class="sell-page-title">Sales</h2>
        </div>

        <div class="admin-panel" id="saleTransactions">
            <div class="admin-panel-header">
                <h3>Sale Transactions</h3>
            </div>

            <div class="products-table-shell">
                <table class="products-table admin-monitor-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Brand</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                            <th>Date</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (mysqli_num_rows($saleTransactionsResult) > 0) { ?>
                            <?php $transactionNumber = 1; ?>
                            <?php while ($transaction = mysqli_fetch_assoc($saleTransactionsResult)) { ?>
                                <tr>
                                    <td class="id-cell"><?php echo $transactionNumber; ?></td>
                                    <td><?php echo htmlspecialchars($transaction['fullname'] ?? 'Unknown User'); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['email'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['customer_name'] !== '' ? $transaction['customer_name'] : 'Walk-in Customer'); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['product_name'] ?? 'Deleted Product'); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['brand'] ?? ''); ?></td>
                                    <td><?php echo format_quantity($transaction['quantity']); ?></td>
                                    <td>PHP <?php echo number_format((float) $transaction['unit_price'], 2); ?></td>
                                    <td>PHP <?php echo number_format((float) $transaction['total_amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['created_at']); ?></td>
                                </tr>
                                <?php $transactionNumber++; ?>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="10" class="empty-products">No sale transactions found.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    </body>
    </html>
    <?php
    exit();
}

if (isset($_GET['sold'])) {
    $message = 'Sale was recorded successfully.';
}

if (isset($_GET['error'])) {
    $error = trim($_GET['error']);
}

$availableProducts = mysqli_query(
    $conn,
    "SELECT id, product_name, brand, market_quantity, market_grams, price
     FROM products
     WHERE user_id = $userId
       AND market_quantity > 0
     ORDER BY product_name ASC"
);

if ($search !== '') {
    $searchTerm = '%' . $search . '%';
    $salesStmt = mysqli_prepare(
        $conn,
        "SELECT ps.*, p.product_name, p.brand
         FROM product_sales ps
         LEFT JOIN products p ON p.id = ps.product_id AND p.user_id = ps.user_id
         WHERE ps.user_id = ?
           AND (
                ps.customer_name LIKE ?
             OR p.product_name LIKE ?
             OR p.brand LIKE ?
           )
         ORDER BY ps.created_at DESC, ps.id DESC"
    );
    mysqli_stmt_bind_param($salesStmt, "isss", $userId, $searchTerm, $searchTerm, $searchTerm);
} else {
    $salesStmt = mysqli_prepare(
        $conn,
        "SELECT ps.*, p.product_name, p.brand
         FROM product_sales ps
         LEFT JOIN products p ON p.id = ps.product_id AND p.user_id = ps.user_id
         WHERE ps.user_id = ?
         ORDER BY ps.created_at DESC, ps.id DESC"
    );
    mysqli_stmt_bind_param($salesStmt, "i", $userId);
}

mysqli_stmt_execute($salesStmt);
$sales = mysqli_stmt_get_result($salesStmt);

$summaryResult = mysqli_query(
    $conn,
    "SELECT
        COUNT(*) AS sale_count,
        COALESCE(SUM(quantity), 0) AS quantity_total,
        COALESCE(SUM(total_amount), 0) AS amount_total
     FROM product_sales
     WHERE user_id = $userId"
);
$summary = mysqli_fetch_assoc($summaryResult);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sales</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
</head>

<body class="<?php echo $isPrintView ? 'printable-page' : ''; ?>">

<?php if (!$isPrintView) { ?>
    <?php include 'sidebar.php'; ?>
<?php } ?>

<div class="main sales-dashboard">
    <?php if ($isPrintView) { ?>
        <div class="print-export-header">
            <div>
                <h1>Sales History</h1>
                <p>Generated on <?php echo date('F j, Y'); ?></p>
            </div>
            <button type="button" class="toolbar-primary-btn print-action-btn" onclick="printExport()">Print</button>
        </div>
    <?php } else { ?>
        <div class="inventory-toolbar">
            <h2 class="sell-page-title">Sales</h2>

            <div class="inventory-toolbar-actions transaction-toolbar-actions">
                <form method="GET" class="inventory-search">
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Search sales"
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                </form>

                <a href="sales.php?<?php echo http_build_query(array_filter(['search' => $search, 'export' => 'print'])); ?>" class="toolbar-btn" target="_blank" rel="noopener" onclick="openPrintExport(this.href); return false;">
                    <span aria-hidden="true">&#8681;</span>
                    Print
                </a>

                <button type="button" class="toolbar-primary-btn" data-bs-toggle="modal" data-bs-target="#sellProductModal">
                    <span aria-hidden="true">+</span>
                    Sell Product
                </button>
            </div>
        </div>
    <?php } ?>

    <?php if (!$isPrintView && $message !== '') { ?>
        <div class="alert alert-success alert-dismissible fade show sale-success-notification" id="saleSuccessNotification" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php } ?>

    <?php if (!$isPrintView && $error !== '') { ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php } ?>

    <?php if (!$isPrintView) { ?>
        <div class="row dashboard-cards sales-summary-cards">
            <div class="col-lg-4 col-sm-6">
                <div class="card-box blue admin-metric-card">
                    <span class="admin-metric-icon admin-metric-sales" aria-hidden="true"></span>
                    <div>
                        <h5>Total Sales</h5>
                        <h1><?php echo (int) $summary['sale_count']; ?></h1>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-sm-6">
                <div class="card-box green admin-metric-card">
                    <span class="admin-metric-icon admin-metric-products" aria-hidden="true"></span>
                    <div>
                        <h5>Items Sold</h5>
                        <h1><?php echo format_quantity($summary['quantity_total']); ?></h1>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-sm-6">
                <div class="card-box blue admin-metric-card">
                    <span class="admin-metric-icon admin-metric-amount" aria-hidden="true"></span>
                    <div>
                        <h5>Total Amount</h5>
                        <h1>PHP <?php echo number_format((float) $summary['amount_total'], 2); ?></h1>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

    <div class="products-table-shell">
        <table class="products-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Brand</th>
                    <th>Quantity</th>
                    <th>Grams</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                    <th>Date</th>
                    <?php if (!$isPrintView) { ?>
                        <th>Receipt</th>
                    <?php } ?>
                </tr>
            </thead>

            <tbody>
                <?php if (mysqli_num_rows($sales) > 0) { ?>
                    <?php $saleNumber = 1; ?>
                    <?php while ($sale = mysqli_fetch_assoc($sales)) { ?>
                        <tr>
                            <td><?php echo $saleNumber; ?></td>
                            <td><?php echo htmlspecialchars($sale['customer_name'] !== '' ? $sale['customer_name'] : 'Walk-in Customer'); ?></td>
                            <td><?php echo htmlspecialchars($sale['product_name'] ?? 'Deleted Product'); ?></td>
                            <td><?php echo htmlspecialchars($sale['brand'] ?? ''); ?></td>
                            <td><?php echo format_quantity($sale['quantity']); ?></td>
                            <td><?php echo format_grams($sale['grams']); ?></td>
                            <td>PHP <?php echo number_format((float) $sale['unit_price'], 2); ?></td>
                            <td>PHP <?php echo number_format((float) $sale['total_amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($sale['created_at']); ?></td>
                            <?php if (!$isPrintView) { ?>
                                <td>
                                    <?php if (($sale['receipt_no'] ?? '') !== '') { ?>
                                        <a
                                            href="receipt.php?receipt=<?php echo urlencode($sale['receipt_no']); ?>"
                                            class="toolbar-btn receipt-table-btn"
                                            target="_blank"
                                            rel="noopener"
                                        >
                                            Print
                                        </a>
                                    <?php } else { ?>
                                        <span class="text-muted">-</span>
                                    <?php } ?>
                                </td>
                            <?php } ?>
                        </tr>
                        <?php $saleNumber++; ?>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="<?php echo $isPrintView ? 9 : 10; ?>" class="empty-products">No sales recorded yet.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!$isPrintView) { ?>
<div class="modal fade" id="sellProductModal" tabindex="-1" aria-labelledby="sellProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form action="sell_product.php" method="POST" id="sellProductForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="sellProductModalLabel">Multiple Purchase</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="sale-items" id="saleItems">
                        <div class="sale-item-row">
                            <select name="product_id[]" class="form-control sale-product-select" required>
                                <option value="">Select Product</option>
                                <?php while ($product = mysqli_fetch_assoc($availableProducts)) { ?>
                                    <?php
                                        $marketQuantity = (float) $product['market_quantity'];
                                        $marketGrams = (float) $product['market_grams'];
                                        $unitGrams = $marketQuantity > 0 ? $marketGrams / $marketQuantity : 0;
                                    ?>
                                    <option
                                        value="<?php echo (int) $product['id']; ?>"
                                        data-price="<?php echo htmlspecialchars($product['price']); ?>"
                                        data-stock="<?php echo htmlspecialchars($product['market_quantity']); ?>"
                                        data-unit-grams="<?php echo htmlspecialchars($unitGrams); ?>"
                                    >
                                        <?php echo htmlspecialchars($product['product_name']); ?>
                                        - <?php echo format_grams($marketGrams); ?>
                                        - PHP <?php echo number_format((float) $product['price'], 2); ?>
                                    </option>
                                <?php } ?>
                            </select>

                            <input
                                type="number"
                                step="1"
                                min="1"
                                name="sale_quantity[]"
                                class="form-control sale-quantity-input"
                                placeholder="Qty"
                                required
                            >

                            <div class="sale-line-total">PHP 0.00</div>

                            <button type="button" class="sale-remove-item" aria-label="Remove item">&times;</button>
                        </div>
                    </div>

                    <button type="button" class="toolbar-btn sale-add-item" id="addSaleItem">
                        <span aria-hidden="true">+</span>
                        Add Item
                    </button>

                    <div class="sale-checkout-summary">
                        <div>
                            <span>Items</span>
                            <strong id="saleItemCount">1</strong>
                        </div>
                        <div>
                            <span>Total Quantity</span>
                            <strong id="saleTotalQuantity">0</strong>
                        </div>
                        <div>
                            <span>Grand Total</span>
                            <strong id="saleGrandTotal">PHP 0.00</strong>
                        </div>
                        <div class="sale-payment-row">
                            <label for="saleCashAmount">Cash Paid</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                name="cash_amount"
                                id="saleCashAmount"
                                class="form-control"
                                placeholder="0.00"
                                required
                            >
                        </div>
                        <div>
                            <span>Change</span>
                            <strong id="saleChangeDue">PHP 0.00</strong>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="sell" class="btn btn-success">Confirm Sale</button>
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

const saleItems = document.getElementById('saleItems');
const addSaleItem = document.getElementById('addSaleItem');
const saleItemCount = document.getElementById('saleItemCount');
const saleTotalQuantity = document.getElementById('saleTotalQuantity');
const saleGrandTotal = document.getElementById('saleGrandTotal');
const sellProductForm = document.getElementById('sellProductForm');
const saleCashAmount = document.getElementById('saleCashAmount');
const saleChangeDue = document.getElementById('saleChangeDue');
const saleSuccessNotification = document.getElementById('saleSuccessNotification');
let currentGrandTotal = 0;

function formatPeso(value) {
    return 'PHP ' + value.toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function updateSaleTotal() {
    let itemCount = 0;
    let totalQuantity = 0;
    let grandTotal = 0;

    saleItems.querySelectorAll('.sale-item-row').forEach(function (row) {
        const select = row.querySelector('.sale-product-select');
        const quantityInput = row.querySelector('.sale-quantity-input');
        const lineTotal = row.querySelector('.sale-line-total');
        const selectedOption = select.options[select.selectedIndex];
        const price = selectedOption ? parseFloat(selectedOption.dataset.price || '0') : 0;
        const stock = selectedOption ? parseFloat(selectedOption.dataset.stock || '0') : 0;
        const quantity = parseFloat(quantityInput.value || '0');
        const total = price * quantity;

        quantityInput.max = stock > 0 ? stock : '';
        lineTotal.textContent = formatPeso(total);

        if (select.value !== '') {
            itemCount++;
        }

        totalQuantity += quantity;
        grandTotal += total;
    });

    saleItemCount.textContent = itemCount.toLocaleString();
    saleTotalQuantity.textContent = totalQuantity.toLocaleString();
    saleGrandTotal.textContent = formatPeso(grandTotal);
    currentGrandTotal = grandTotal;
    updateSaleChange();
}

function updateSaleChange() {
    const cashPaidValue = parseFloat(saleCashAmount ? saleCashAmount.value || '0' : '0');
    const cashPaid = Number.isFinite(cashPaidValue) ? cashPaidValue : 0;
    const changeDue = Math.max(0, cashPaid - currentGrandTotal);

    if (saleCashAmount && cashPaid >= currentGrandTotal) {
        saleCashAmount.setCustomValidity('');
    }

    if (saleChangeDue) {
        saleChangeDue.textContent = formatPeso(changeDue);
    }
}

function bindSaleRow(row) {
    row.querySelector('.sale-product-select').addEventListener('change', updateSaleTotal);
    row.querySelector('.sale-quantity-input').addEventListener('input', updateSaleTotal);
    row.querySelector('.sale-remove-item').addEventListener('click', function () {
        if (saleItems.querySelectorAll('.sale-item-row').length > 1) {
            row.remove();
            updateSaleTotal();
        }
    });
}

function closeSaleSuccessNotification() {
    if (saleSuccessNotification) {
        saleSuccessNotification.classList.remove('show');
        setTimeout(function () {
            saleSuccessNotification.remove();
        }, 180);
    }
}

if (saleSuccessNotification) {
    setTimeout(closeSaleSuccessNotification, 5000);
}

if (saleItems) {
    saleItems.querySelectorAll('.sale-item-row').forEach(bindSaleRow);

    if (saleCashAmount) {
        saleCashAmount.addEventListener('input', updateSaleChange);
    }

    if (sellProductForm) {
        sellProductForm.addEventListener('submit', function (event) {
            const cashPaidValue = parseFloat(saleCashAmount ? saleCashAmount.value || '0' : '0');
            const cashPaid = Number.isFinite(cashPaidValue) ? cashPaidValue : 0;

            if (cashPaid < currentGrandTotal) {
                event.preventDefault();
                saleCashAmount.setCustomValidity('Cash paid must be equal to or greater than the grand total.');
                saleCashAmount.reportValidity();
                return;
            }

            saleCashAmount.setCustomValidity('');
        });
    }

    addSaleItem.addEventListener('click', function () {
        const firstRow = saleItems.querySelector('.sale-item-row');
        const newRow = firstRow.cloneNode(true);

        newRow.querySelector('.sale-product-select').value = '';
        newRow.querySelector('.sale-quantity-input').value = '';
        newRow.querySelector('.sale-line-total').textContent = 'PHP 0.00';
        saleItems.appendChild(newRow);
        bindSaleRow(newRow);
        updateSaleTotal();
    });

    updateSaleTotal();
}
</script>
<?php } ?>
</body>
</html>
