<?php
require_once 'db.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $quantity    = intval($_POST['quantity'] ?? 0);
    $price       = floatval($_POST['price'] ?? 0);
    $min_stock   = intval($_POST['min_stock'] ?? 5);
    $description = trim($_POST['description'] ?? '');

    // Validation
    if (empty($name))        $errors[] = "Product name is required.";
    if (strlen($name) > 150) $errors[] = "Product name must be under 150 characters.";
    if ($quantity < 0)       $errors[] = "Quantity cannot be negative.";
    if ($price < 0)          $errors[] = "Price cannot be negative.";
    if ($min_stock < 0)      $errors[] = "Minimum stock cannot be negative.";

    // Check duplicate
    $check = mysqli_query($conn, "SELECT id FROM products WHERE name = '" . mysqli_real_escape_string($conn, $name) . "'");
    if (mysqli_num_rows($check) > 0) $errors[] = "A product with this name already exists.";

    if (empty($errors)) {
        $name_esc = mysqli_real_escape_string($conn, $name);
        $desc_esc = mysqli_real_escape_string($conn, $description);
        $cat_val  = $category_id > 0 ? $category_id : "NULL";

        $sql = "INSERT INTO products (name, category_id, quantity, price, min_stock, description)
                VALUES ('$name_esc', $cat_val, $quantity, $price, $min_stock, '$desc_esc')";

        if (mysqli_query($conn, $sql)) {
            header("Location: products.php?msg=added");
            exit;
        } else {
            $errors[] = "Database error: " . mysqli_error($conn);
        }
    }
}

$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product | ShopStock</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main">
    <div class="topbar">
        <h1>➕ <span>Add Product</span></h1>
        <div class="topbar-right">
            <a href="products.php" class="btn btn-outline">← Back to Products</a>
        </div>
    </div>

    <div style="max-width: 680px;">
        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <div>
                <strong>Please fix the following:</strong>
                <ul style="margin-top:8px; padding-left:18px;">
                    <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3>📦 New Product Details</h3>
            </div>
            <div class="card-body">
                <form method="POST" id="addForm" novalidate>

                    <div class="form-group">
                        <label>Product Name *</label>
                        <input type="text" name="name" id="name" placeholder="e.g. USB Cable, Rice 5kg..."
                               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" maxlength="150" required>
                        <small id="nameError" style="color:#e74c3c; display:none;"></small>
                    </div>

                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id">
                            <option value="0">— Select Category —</option>
                            <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                            <option value="<?= $cat['id'] ?>" <?= (($_POST['category_id'] ?? 0) == $cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Quantity *</label>
                            <input type="number" name="quantity" id="quantity" placeholder="0"
                                   value="<?= htmlspecialchars($_POST['quantity'] ?? '0') ?>" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Price (per unit) *</label>
                            <input type="number" name="price" id="price" placeholder="0.00"
                                   value="<?= htmlspecialchars($_POST['price'] ?? '0') ?>" min="0" step="0.01" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Minimum Stock Level</label>
                        <input type="number" name="min_stock" placeholder="5"
                               value="<?= htmlspecialchars($_POST['min_stock'] ?? '5') ?>" min="0">
                        <small style="color:#aaa;">Alert will trigger when quantity falls at or below this value.</small>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" placeholder="Optional product description..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <!-- Live preview -->
                    <div id="livePreview" style="background:#f8f9fa; border-radius:8px; padding:14px; margin-bottom:20px; display:none;">
                        <strong style="font-size:13px; color:#888;">PREVIEW</strong>
                        <div style="margin-top:8px; display:flex; gap:20px; flex-wrap:wrap;">
                            <span>📦 <strong id="prev_name">—</strong></span>
                            <span>🔢 Qty: <strong id="prev_qty">0</strong></span>
                            <span>💲 Price: <strong id="prev_price">$0.00</strong></span>
                            <span>💰 Value: <strong id="prev_value">$0.00</strong></span>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">➕ Add Product</button>
                        <a href="products.php" class="btn btn-outline">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Live preview
const nameInput  = document.getElementById('name');
const qtyInput   = document.getElementById('quantity');
const priceInput = document.getElementById('price');
const preview    = document.getElementById('livePreview');

function updatePreview() {
    const name  = nameInput.value.trim();
    const qty   = parseFloat(qtyInput.value) || 0;
    const price = parseFloat(priceInput.value) || 0;

    if (name || qty || price) {
        preview.style.display = 'block';
        document.getElementById('prev_name').textContent  = name || '—';
        document.getElementById('prev_qty').textContent   = qty;
        document.getElementById('prev_price').textContent = '$' + price.toFixed(2);
        document.getElementById('prev_value').textContent = '$' + (qty * price).toFixed(2);
    } else {
        preview.style.display = 'none';
    }
}

[nameInput, qtyInput, priceInput].forEach(el => el.addEventListener('input', updatePreview));

// Client-side validation
document.getElementById('addForm').addEventListener('submit', function(e) {
    const name = nameInput.value.trim();
    if (!name) {
        e.preventDefault();
        nameInput.style.borderColor = '#e74c3c';
        document.getElementById('nameError').style.display = 'block';
        document.getElementById('nameError').textContent = 'Product name is required.';
        nameInput.focus();
    }
});

nameInput.addEventListener('input', function() {
    this.style.borderColor = '';
    document.getElementById('nameError').style.display = 'none';
});
</script>

</body>
</html>
