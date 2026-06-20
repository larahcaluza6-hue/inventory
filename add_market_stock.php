<?php
include 'db.php';
include 'auth.php';

$error = '';
$userId = (int) $_SESSION['user_id'];
$availableProducts = mysqli_query(
    $conn,
    "SELECT id, product_name, category, brand, quantity, grams
     FROM products
     WHERE user_id = $userId AND quantity > 0
     ORDER BY product_name ASC, brand ASC, category ASC, grams ASC"
);
$productGroups = [];

while ($availableProduct = mysqli_fetch_assoc($availableProducts)) {
    $groupKey = strtolower(trim($availableProduct['product_name']));

    if (!isset($productGroups[$groupKey])) {
        $productGroups[$groupKey] = [
            'label' => $availableProduct['product_name'],
            'sizes' => []
        ];
    }

    $productGroups[$groupKey]['sizes'][] = [
        'id' => (int) $availableProduct['id'],
        'grams' => (float) $availableProduct['grams'],
        'quantity' => (float) $availableProduct['quantity'],
        'brand' => $availableProduct['brand'],
        'category' => $availableProduct['category']
    ];
}

if (isset($_POST['store'])) {
    $productId = (int) $_POST['product_id'];
    $storeQuantity = (float) $_POST['store_quantity'];
    $storeGrams = (float) $_POST['store_grams'];

    if ($storeQuantity <= 0) {
        $error = 'Please enter a valid quantity.';
    } elseif ($storeGrams < 0) {
        $error = 'Please enter valid grams.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT product_name, quantity, grams, market_quantity, market_grams FROM products WHERE id = ? AND user_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "ii", $productId, $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $product = mysqli_fetch_assoc($result);

        if (!$product) {
            $error = 'Product was not found.';
        } elseif ((float) $product['quantity'] < $storeQuantity) {
            $error = 'Not enough product stock available.';
        } elseif (abs((float) $product['grams'] - $storeGrams) > 0.00001) {
            $error = 'No grams in product.';
        } else {
            $newQuantity = (float) $product['quantity'] - $storeQuantity;
            $newMarketQuantity = (float) $product['market_quantity'] + $storeQuantity;
            $newMarketGrams = (float) $product['market_grams'] + $storeGrams;
            $status = $newQuantity > 0 ? 'Available' : 'Sold Out';

            $update = mysqli_prepare(
                $conn,
                "UPDATE products SET quantity = ?, market_quantity = ?, market_grams = ?, status = ? WHERE id = ? AND user_id = ?"
            );
            mysqli_stmt_bind_param($update, "dddsii", $newQuantity, $newMarketQuantity, $newMarketGrams, $status, $productId, $userId);
            mysqli_stmt_execute($update);

            $transaction = mysqli_prepare(
                $conn,
                "INSERT INTO market_transactions (user_id, product_id, quantity, transaction_type) VALUES (?, ?, ?, 'Add Market Stock')"
            );
            mysqli_stmt_bind_param($transaction, "iid", $userId, $productId, $storeQuantity);
            mysqli_stmt_execute($transaction);

            header("Location: orders.php?added=1");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Market Stock</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime('style.css'); ?>">
</head>

<body>

<?php include 'sidebar.php'; ?>

<div class="main">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Add Market Stock</h2>
        <a href="orders.php" class="btn btn-secondary">Back</a>
    </div>

    <?php if ($error !== '') { ?>
        <div class="alert alert-danger market-error-notification" id="marketErrorNotification" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" aria-label="Close" onclick="closeMarketErrorNotification()"></button>
        </div>
    <?php } ?>

    <form method="POST" class="form-page mt-0" id="addMarketStockForm">
        <select class="form-control mb-3" id="marketProductSelect" required>
            <option value="">Select Product</option>
            <?php foreach ($productGroups as $groupKey => $productGroup) { ?>
                <option
                    value="<?php echo htmlspecialchars($groupKey); ?>"
                    data-sizes="<?php echo htmlspecialchars(json_encode($productGroup['sizes']), ENT_QUOTES); ?>"
                >
                    <?php echo htmlspecialchars($productGroup['label']); ?>
                </option>
            <?php } ?>
        </select>

        <select name="product_id" class="form-control mb-3" id="marketGramsSelect" required disabled>
            <option value="">Select Grams</option>
        </select>

        <div class="input-group mb-3">
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

        <input type="hidden" name="store_grams" id="marketGramsInput" required>
        <div class="market-grams-suggestion" id="marketGramsSuggestion">
            Select a product, then choose grams.
        </div>

        <button type="submit" name="store" class="btn btn-success">
            Add Stock
        </button>
    </form>
</div>

<script>
const addMarketStockForm = document.getElementById('addMarketStockForm');
const marketProductSelect = document.getElementById('marketProductSelect');
const marketGramsSelect = document.getElementById('marketGramsSelect');
const marketGramsInput = document.getElementById('marketGramsInput');
const marketGramsSuggestion = document.getElementById('marketGramsSuggestion');

function formatSuggestionGrams(value) {
    const grams = parseFloat(value || '0');

    return Number.isFinite(grams) ? grams.toLocaleString(undefined, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    }) + 'g' : '0g';
}

function updateMarketGramsSuggestion() {
    const selectedOption = marketProductSelect.options[marketProductSelect.selectedIndex];
    const sizes = selectedOption && selectedOption.dataset.sizes ? JSON.parse(selectedOption.dataset.sizes) : [];

    marketGramsSelect.innerHTML = '<option value="">Select Grams</option>';
    marketGramsSelect.disabled = sizes.length === 0;
    marketGramsInput.value = '';

    if (sizes.length === 0) {
        marketGramsSuggestion.textContent = 'Select a product, then choose grams.';
        return;
    }

    sizes.forEach(function (size) {
        const option = document.createElement('option');
        const details = [size.brand, size.category].filter(Boolean).join(' - ');
        option.value = size.id;
        option.dataset.grams = size.grams;
        option.textContent = formatSuggestionGrams(size.grams)
            + (details ? ' - ' + details : '')
            + ' - ' + parseFloat(size.quantity || '0').toLocaleString()
            + ' available';
        marketGramsSelect.appendChild(option);
    });

    if (sizes.length === 1) {
        marketGramsSelect.selectedIndex = 1;
        updateSelectedGrams();
        return;
    }

    marketGramsSuggestion.textContent = 'Choose one of the available gram sizes.';
}

function updateSelectedGrams() {
    const selectedOption = marketGramsSelect.options[marketGramsSelect.selectedIndex];
    const productGrams = selectedOption ? selectedOption.dataset.grams || '' : '';

    marketGramsInput.value = productGrams;
    marketGramsSuggestion.textContent = productGrams
        ? 'Selected grams: ' + formatSuggestionGrams(productGrams)
        : 'Choose one of the available gram sizes.';
}

function showMarketErrorNotification(message) {
    let notification = document.getElementById('marketErrorNotification');

    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'marketErrorNotification';
        notification.className = 'alert alert-danger market-error-notification';
        notification.setAttribute('role', 'alert');
        document.body.appendChild(notification);
    }

    notification.innerHTML = message + '<button type="button" class="btn-close" aria-label="Close" onclick="closeMarketErrorNotification()"></button>';
    notification.classList.add('show');
}

function closeMarketErrorNotification() {
    const notification = document.getElementById('marketErrorNotification');

    if (notification) {
        notification.classList.remove('show');
        setTimeout(function () {
            notification.remove();
        }, 180);
    }
}

if (addMarketStockForm) {
    updateMarketGramsSuggestion();
    marketProductSelect.addEventListener('change', updateMarketGramsSuggestion);
    marketGramsSelect.addEventListener('change', updateSelectedGrams);

    addMarketStockForm.addEventListener('submit', function (event) {
        const selectedOption = marketGramsSelect.options[marketGramsSelect.selectedIndex];
        const productGrams = selectedOption ? parseFloat(selectedOption.dataset.grams || '0') : 0;
        const enteredGrams = parseFloat(marketGramsInput.value || '0');

        if (!marketGramsSelect.value || Math.abs(productGrams - enteredGrams) > 0.00001) {
            event.preventDefault();
            showMarketErrorNotification('Please choose product grams.');
        }
    });
}
</script>

</body>
</html>
