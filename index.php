<?php
require_once 'includes/auth.php';
require_once 'db.php';

// Total products
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM products"))['cnt'];

// Total stock value
$total_value = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(quantity * price) as val FROM products"))['val'];
$total_value = $total_value ? number_format($total_value, 2) : '0.00';

// Low stock count (quantity <= min_stock)
$low_stock_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM products WHERE quantity <= min_stock"))['cnt'];

// Total categories
$total_categories = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM categories"))['cnt'];

// Recent products
$recent = mysqli_query($conn, "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC LIMIT 6");

// Low stock items preview
$low_items = mysqli_query($conn, "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.quantity <= p.min_stock ORDER BY p.quantity ASC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | ShopStock</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main">
    <div class="topbar">
        <h1>📊 <span>Dashboard</span></h1>
        <div class="topbar-right">
            <span class="badge-date">📅 <?= date('D, d M Y') ?></span>
            <?php if (is_admin()): ?>
            <a href="add_product.php" class="btn btn-primary">➕ Add Product</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_GET['error']) && $_GET['error'] == 'access_denied'): ?>
    <div class="access-denied-bar">🚫 Access denied. You do not have permission to view that page.</div>
    <?php endif; ?>

    <!-- Stat Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">📦</div>
            <div class="stat-info">
                <h3><?= $total_products ?></h3>
                <p>Total Products</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">💰</div>
            <div class="stat-info">
                <h3>$<?= $total_value ?></h3>
                <p>Total Stock Value</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">⚠️</div>
            <div class="stat-info">
                <h3><?= $low_stock_count ?></h3>
                <p>Low Stock Alerts</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">🗂️</div>
            <div class="stat-info">
                <h3><?= $total_categories ?></h3>
                <p>Categories</p>
            </div>
        </div>
    </div>

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">

        <!-- Recent Products -->
        <div class="card">
            <div class="card-header">
                <h3>📦 Recent Products</h3>
                <a href="products.php" class="btn btn-sm btn-outline">View All</a>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Qty</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($recent) == 0): ?>
                        <tr><td colspan="4">
                            <div class="empty-state">
                                <div class="empty-icon">📭</div>
                                <p>No products yet</p>
                            </div>
                        </td></tr>
                        <?php else: while ($row = mysqli_fetch_assoc($recent)): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                            <td><?= htmlspecialchars($row['cat_name'] ?? '—') ?></td>
                            <td>
                                <?php
                                $qty = $row['quantity'];
                                $min = $row['min_stock'];
                                $pct = $min > 0 ? min(100, round(($qty / ($min * 2)) * 100)) : 100;
                                $cls = $qty == 0 ? 'danger' : ($qty <= $min ? 'low' : 'ok');
                                ?>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <?= $qty ?>
                                    <div class="stock-bar-wrap">
                                        <div class="stock-bar <?= $cls ?>" style="width:<?= $pct ?>%"></div>
                                    </div>
                                </div>
                            </td>
                            <td>$<?= number_format($row['price'], 2) ?></td>
                        </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Low Stock Alerts -->
        <div class="card">
            <div class="card-header">
                <h3>⚠️ Low Stock Alerts</h3>
                <a href="low_stock.php" class="btn btn-sm btn-outline">View All</a>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Min</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($low_items) == 0): ?>
                        <tr><td colspan="4">
                            <div class="empty-state">
                                <div class="empty-icon">✅</div>
                                <p>All stock levels are OK!</p>
                            </div>
                        </td></tr>
                        <?php else: while ($row = mysqli_fetch_assoc($low_items)): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                            <td><?= $row['quantity'] ?></td>
                            <td><?= $row['min_stock'] ?></td>
                            <td>
                                <?php if ($row['quantity'] == 0): ?>
                                    <span class="badge badge-danger">Out of Stock</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Low Stock</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>
