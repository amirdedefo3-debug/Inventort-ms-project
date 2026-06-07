<?php
require_once 'includes/auth.php';
require_once 'db.php';

// Fetch all low stock / out of stock items
$low_items = mysqli_query($conn, "
    SELECT p.*, c.name as cat_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.quantity <= p.min_stock
    ORDER BY p.quantity ASC, p.name ASC
");

$total_low = mysqli_num_rows($low_items);

// Out of stock count
$out_of_stock = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM products WHERE quantity = 0"))['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Low Stock | ShopStock</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main">
    <div class="topbar">
        <h1>⚠️ <span>Low Stock Alerts</span></h1>
        <div class="topbar-right">
            <a href="add_product.php" class="btn btn-primary">➕ Add Product</a>
        </div>
    </div>

    <!-- Summary cards -->
    <div class="stats-grid" style="margin-bottom:24px;">
        <div class="stat-card">
            <div class="stat-icon orange">⚠️</div>
            <div class="stat-info">
                <h3><?= $total_low ?></h3>
                <p>Low Stock Items</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">❌</div>
            <div class="stat-info">
                <h3><?= $out_of_stock ?></h3>
                <p>Out of Stock</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>⚠️ Items Needing Restock</h3>
            <span style="font-size:13px; color:#aaa;">Items where quantity ≤ minimum stock level</span>
        </div>

        <?php if ($total_low == 0): ?>
        <div class="empty-state" style="padding:60px;">
            <div class="empty-icon">✅</div>
            <h3>All Stock Levels are OK!</h3>
            <p>No products are running low right now.</p>
        </div>
        <?php else: ?>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Current Qty</th>
                        <th>Min Level</th>
                        <th>Shortage</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; while ($row = mysqli_fetch_assoc($low_items)): ?>
                    <?php
                        $qty      = $row['quantity'];
                        $min      = $row['min_stock'];
                        $shortage = max(0, $min - $qty + $min); // suggested restock amount
                    ?>
                    <tr style="<?= $qty == 0 ? 'background:#fff8f8;' : '' ?>">
                        <td style="color:#aaa;"><?= $i++ ?></td>
                        <td>
                            <strong><?= htmlspecialchars($row['name']) ?></strong>
                            <?php if ($row['description']): ?>
                            <br><small style="color:#aaa;"><?= htmlspecialchars(substr($row['description'], 0, 40)) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['cat_name']): ?>
                            <span class="badge badge-info"><?= htmlspecialchars($row['cat_name']) ?></span>
                            <?php else: ?><span style="color:#ccc;">—</span><?php endif; ?>
                        </td>
                        <td>
                            <strong style="color:<?= $qty == 0 ? '#e74c3c' : '#e67e22' ?>; font-size:18px;"><?= $qty ?></strong>
                        </td>
                        <td><?= $min ?></td>
                        <td>
                            <?php if ($qty == 0): ?>
                                <span style="color:#e74c3c; font-weight:600;">Need <?= $min ?> units</span>
                            <?php else: ?>
                                <span style="color:#e67e22; font-weight:600;">+<?= ($min - $qty) ?> needed</span>
                            <?php endif; ?>
                        </td>
                        <td>$<?= number_format($row['price'], 2) ?></td>
                        <td>
                            <?php if ($qty == 0): ?>
                                <span class="badge badge-danger">Out of Stock</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Low Stock</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit_product.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning" title="Restock / Edit">✏️ Restock</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
