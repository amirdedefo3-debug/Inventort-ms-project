<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$current = basename($_SERVER['PHP_SELF']);
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
?>
<div class="sidebar">
    <div class="sidebar-logo">
        <span>🏪</span>
        <h2>ShopStock</h2>
        <p>Inventory Manager</p>
    </div>

    <!-- User Info -->
    <div class="sidebar-user">
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?></div>
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></div>
            <div class="user-role <?= $is_admin ? 'role-admin' : 'role-staff' ?>">
                <?= $is_admin ? '🔑 Admin' : '👤 Staff' ?>
            </div>
        </div>
    </div>

    <nav>
        <div class="nav-label">Main Menu</div>
        <a href="index.php" class="<?= $current == 'index.php' ? 'active' : '' ?>">
            <span class="icon">📊</span>
            <span>Dashboard</span>
        </a>
        <a href="products.php" class="<?= $current == 'products.php' ? 'active' : '' ?>">
            <span class="icon">📦</span>
            <span>Products</span>
        </a>

        <?php if ($is_admin): ?>
        <a href="add_product.php" class="<?= $current == 'add_product.php' ? 'active' : '' ?>">
            <span class="icon">➕</span>
            <span>Add Product</span>
        </a>
        <a href="categories.php" class="<?= $current == 'categories.php' ? 'active' : '' ?>">
            <span class="icon">🗂️</span>
            <span>Categories</span>
        </a>
        <?php endif; ?>

        <div class="nav-label">Reports</div>
        <a href="low_stock.php" class="<?= $current == 'low_stock.php' ? 'active' : '' ?>">
            <span class="icon">⚠️</span>
            <span>Low Stock</span>
        </a>

        <?php if ($is_admin): ?>
        <div class="nav-label">Admin</div>
        <a href="manage_users.php" class="<?= $current == 'manage_users.php' ? 'active' : '' ?>">
            <span class="icon">👥</span>
            <span>Manage Users</span>
        </a>
        <?php endif; ?>

        <div class="nav-label">Account</div>
        <a href="logout.php" style="color:#ff6b6b;">
            <span class="icon">🚪</span>
            <span>Logout</span>
        </a>
    </nav>
</div>
