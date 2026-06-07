<?php
require_once 'db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) { header("Location: products.php"); exit; }

// Fetch product
$result = mysqli_query($conn, "SELECT * FROM products WHERE id = $id");
if (mysqli_num_rows($result) == 0) { header("Location: products.php"); exit; }
$product = mysqli_fetch_assoc($result);

$errors = [];

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

    // Check duplicate (exclude self)
    $name_esc = mysqli_real_escape_string($conn, $name);
    $check = mysqli_query($conn, "SELECT id FROM products WHERE name = '$name_esc' AND id != $id");
    if (mysqli_num_rows($check) > 0) $errors[] = "Another product with this name already exists.";

    if (empty($errors)) {
        $desc_esc = mysqli_real_escape_string($conn, $description);
        $cat_val  = $category_id > 0 ? $category_id : "NULL";

        $sql = "UPDATE products SET
                    name = '$name_esc',
                    category_id = $cat_val,
                    quantity = $quantity,
                    price = $price,
                    min_stock = $min_stock,
                    description = '$desc_esc'
                WHERE id = $id";

        if (mysqli_query($conn, $sql)) {
            header("Location: products.php?msg=updated");
            exit;
        } else {
            $errors[] = "Database error: " . mysqli_error($conn);
        }
    }

    // Keep entered values on error
    $product['name']        = $name;
    $product['category_id'] = $category_id;
    $product['quantity']    = $quantity;
    $product['price']       = $price;
    $product['min_stock']   = $min_stock;
    $product['description'] = $description;
}

$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product | ShopStock</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main">
    <div class="topbar">
        <h1>✏️ <span>Edit Product</span></h1>
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
                <h3>📦 Edit: <?= htmlspecialchars($product['name']) ?></h3>
                <small style="color:#aaa;">ID #<?= $id ?> · Last updated: <?= date('d M Y', strtotime($product['updated_at'])) ?></small>
            </div>
            <div class="card-body">
                <form method="POST" id="editForm">

                    <div class="form-group">
                        <label>Product Name *</label>
                        <input type="text" name="name" id="name" placeholder="Product name"
                               value="<?= htmlspecialchars($product['name']) ?>" maxlength="150" required>
                    </div>

                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id">
                            <option value="0">— Select Category —</option>
                            <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($product['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Quantity *</label>
                            <input type="number" name="quantity" id="quantity"
                                   value="<?= htmlspecialchars($product['quantity']) ?>" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Price (per unit) *</label>
                            <input type="number" name="price" id="price"
                                   value="<?= htmlspecialchars($product['price']) ?>" min="0" step="0.01" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Minimum Stock Level</label>
                        <input type="number" name="min_stock"
                               value="<?= htmlspecialchars($product['min_stock']) ?>" min="0">
                        <small style="color:#aaa;">Alert triggers when quantity is at or below this value.</small>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                    </div>

                    <!-- Live stock value preview -->
                    <div style="background:#f8f9fa; border-radius:8px; padding:14px; margin-bottom:20px;">
                        <strong style="font-size:13px; color:#888;">STOCK VALUE PREVIEW</strong>
                        <div style="margin-top:8px; display:flex; gap:20px; flex-wrap:wrap; font-size:14px;">
                            <span>🔢 Qty: <strong id="prev_qty"><?= $product['quantity'] ?></strong></span>
                            <span>💲 Price: <strong id="prev_price">$<?= number_format($product['price'], 2) ?></strong></span>
                            <span>💰 Total Value: <strong id="prev_value">$<?= number_format($product['quantity'] * $product['price'], 2) ?></strong></span>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-warning">💾 Save Changes</button>
                        <a href="products.php" class="btn btn-outline">Cancel</a>
                        <button type="button" onclick="confirmDelete()" class="btn btn-danger" style="margin-left:auto;">🗑️ Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <h3>🗑️ Delete Product</h3>
        <p>Are you sure you want to delete <strong><?= htmlspecialchars($product['name']) ?></strong>? This cannot be undone.</p>
        <div class="modal-actions">
            <button onclick="document.getElementById('deleteModal').classList.remove('active')" class="btn btn-outline">Cancel</button>
            <a href="delete_product.php?id=<?= $id ?>" class="btn btn-danger">Delete</a>
        </div>
    </div>
</div>

<script>
const qtyInput   = document.getElementById('quantity');
const priceInput = document.getElementById('price');

function updatePreview() {
    const qty   = parseFloat(qtyInput.value) || 0;
    const price = parseFloat(priceInput.value) || 0;
    document.getElementById('prev_qty').textContent   = qty;
    document.getElementById('prev_price').textContent = '$' + price.toFixed(2);
    document.getElementById('prev_value').textContent = '$' + (qty * price).toFixed(2);
}

[qtyInput, priceInput].forEach(el => el.addEventListener('input', updatePreview));

function confirmDelete() {
    document.getElementById('deleteModal').classList.add('active');
}

document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('active');
});

document.getElementById('editForm').addEventListener('submit', function(e) {
    const name = document.getElementById('name').value.trim();
    if (!name) {
        e.preventDefault();
        document.getElementById('name').style.borderColor = '#e74c3c';
        document.getElementById('name').focus();
    }
});
</script>

</body>
</html>
