<?php
require_once 'includes/auth.php';
require_once 'db.php';

// Handle search & filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$cat_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Allowed sort columns
$allowed_sort = ['name', 'quantity', 'price', 'created_at'];
if (!in_array($sort, $allowed_sort)) $sort = 'created_at';
$order = strtoupper($order) == 'ASC' ? 'ASC' : 'DESC';

// Pagination
$per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

// Build WHERE
$where = "WHERE 1=1";
if ($search) $where .= " AND (p.name LIKE '%$search%' OR p.description LIKE '%$search%')";
if ($cat_filter) $where .= " AND p.category_id = $cat_filter";

// Count
$count_result = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM products p LEFT JOIN categories c ON p.category_id = c.id $where"));
$total = $count_result['cnt'];
$total_pages = ceil($total / $per_page);

// Fetch products
$products = mysqli_query($conn, "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id $where ORDER BY p.$sort $order LIMIT $per_page OFFSET $offset");

// All categories for filter
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");

// Success/error message
$msg = '';
$msg_type = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'deleted')  { $msg = '🗑️ Product deleted successfully.'; $msg_type = 'danger'; }
    if ($_GET['msg'] == 'added')    { $msg = '✅ Product added successfully.'; $msg_type = 'success'; }
    if ($_GET['msg'] == 'updated')  { $msg = '✏️ Product updated successfully.'; $msg_type = 'success'; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | ShopStock</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main">
    <div class="topbar">
        <h1>📦 <span>Products</span></h1>
        <div class="topbar-right">
            <?php if (is_admin()): ?>
            <a href="add_product.php" class="btn btn-primary">➕ Add Product</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($msg): ?>
    <div class="alert alert-<?= $msg_type ?>" id="flash-msg">
        <?= $msg ?>
        <button onclick="document.getElementById('flash-msg').remove()" style="margin-left:auto; background:none; border:none; cursor:pointer; font-size:16px; color:inherit;">✕</button>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h3>All Products (<?= $total ?>)</h3>
            <!-- Search & Filter -->
            <form method="GET" class="search-bar">
                <input type="text" name="search" placeholder="🔍 Search products..." value="<?= htmlspecialchars($search) ?>">
                <select name="category">
                    <option value="">All Categories</option>
                    <?php mysqli_data_seek($categories, 0); while ($cat = mysqli_fetch_assoc($categories)): ?>
                    <option value="<?= $cat['id'] ?>" <?= $cat_filter == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
                <?php if ($search || $cat_filter): ?>
                <a href="products.php" class="btn btn-outline btn-sm">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>
                            <a href="?search=<?= urlencode($search) ?>&category=<?= $cat_filter ?>&sort=name&order=<?= ($sort=='name' && $order=='ASC') ? 'DESC' : 'ASC' ?>" style="color:inherit;">
                                Product Name <?= $sort=='name' ? ($order=='ASC' ? '▲' : '▼') : '' ?>
                            </a>
                        </th>
                        <th>Category</th>
                        <th>
                            <a href="?search=<?= urlencode($search) ?>&category=<?= $cat_filter ?>&sort=quantity&order=<?= ($sort=='quantity' && $order=='ASC') ? 'DESC' : 'ASC' ?>" style="color:inherit;">
                                Qty <?= $sort=='quantity' ? ($order=='ASC' ? '▲' : '▼') : '' ?>
                            </a>
                        </th>
                        <th>
                            <a href="?search=<?= urlencode($search) ?>&category=<?= $cat_filter ?>&sort=price&order=<?= ($sort=='price' && $order=='ASC') ? 'DESC' : 'ASC' ?>" style="color:inherit;">
                                Price <?= $sort=='price' ? ($order=='ASC' ? '▲' : '▼') : '' ?>
                            </a>
                        </th>
                        <th>Stock Value</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($products) == 0): ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <div class="empty-icon">📭</div>
                                <h3>No products found</h3>
                                <p><?= $search ? "Try a different search term." : "Start by adding your first product." ?></p>
                            </div>
                        </td>
                    </tr>
                    <?php else: $i = $offset + 1; while ($row = mysqli_fetch_assoc($products)): ?>
                    <tr>
                        <td style="color:#aaa;"><?= $i++ ?></td>
                        <td>
                            <strong><?= htmlspecialchars($row['name']) ?></strong>
                            <?php if ($row['description']): ?>
                            <br><small style="color:#aaa;"><?= htmlspecialchars(substr($row['description'], 0, 40)) ?><?= strlen($row['description']) > 40 ? '...' : '' ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['cat_name']): ?>
                            <span class="badge badge-info"><?= htmlspecialchars($row['cat_name']) ?></span>
                            <?php else: ?><span style="color:#ccc;">—</span><?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $qty = $row['quantity'];
                            $min = $row['min_stock'];
                            $pct = $min > 0 ? min(100, round(($qty / ($min * 2)) * 100)) : 100;
                            $cls = $qty == 0 ? 'danger' : ($qty <= $min ? 'low' : 'ok');
                            ?>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <strong><?= $qty ?></strong>
                                <div class="stock-bar-wrap">
                                    <div class="stock-bar <?= $cls ?>" style="width:<?= $pct ?>%"></div>
                                </div>
                            </div>
                        </td>
                        <td>$<?= number_format($row['price'], 2) ?></td>
                        <td>$<?= number_format($row['quantity'] * $row['price'], 2) ?></td>
                        <td>
                            <?php if ($qty == 0): ?>
                                <span class="badge badge-danger">Out of Stock</span>
                            <?php elseif ($qty <= $min): ?>
                                <span class="badge badge-warning">Low Stock</span>
                            <?php else: ?>
                                <span class="badge badge-success">In Stock</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display:flex; gap:6px;">
                                <?php if (is_admin()): ?>
                                <a href="edit_product.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning" title="Edit">✏️</a>
                                <button onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['name'])) ?>')" class="btn btn-sm btn-danger" title="Delete">🗑️</button>
                                <?php else: ?>
                                <span style="color:#ccc; font-size:12px;">View only</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <?php if ($p == $page): ?>
                    <span class="current"><?= $p ?></span>
                <?php else: ?>
                    <a href="?search=<?= urlencode($search) ?>&category=<?= $cat_filter ?>&sort=<?= $sort ?>&order=<?= $order ?>&page=<?= $p ?>"><?= $p ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <h3>🗑️ Delete Product</h3>
        <p id="deleteMsg">Are you sure you want to delete this product? This action cannot be undone.</p>
        <div class="modal-actions">
            <button onclick="closeModal()" class="btn btn-outline">Cancel</button>
            <a href="#" id="deleteBtn" class="btn btn-danger">Delete</a>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('deleteMsg').textContent = 'Are you sure you want to delete "' + name + '"? This cannot be undone.';
    document.getElementById('deleteBtn').href = 'delete_product.php?id=' + id;
    document.getElementById('deleteModal').classList.add('active');
}
function closeModal() {
    document.getElementById('deleteModal').classList.remove('active');
}
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// Auto-dismiss flash message
setTimeout(() => {
    const msg = document.getElementById('flash-msg');
    if (msg) msg.remove();
}, 4000);
</script>

</body>
</html>
