<?php
$current = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <div class="sidebar-logo">
        <span>🏪</span>
        <h2>ShopStock</h2>
        <p>Inventory Manager</p>
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
        <a href="add_product.php" class="<?= $current == 'add_product.php' ? 'active' : '' ?>">
            <span class="icon">➕</span>
            <span>Add Product</span>
        </a>
        <a href="categories.php" class="<?= $current == 'categories.php' ? 'active' : '' ?>">
            <span class="icon">🗂️</span>
            <span>Categories</span>
        </a>
        <div class="nav-label">Reports</div>
        <a href="low_stock.php" class="<?= $current == 'low_stock.php' ? 'active' : '' ?>">
            <span class="icon">⚠️</span>
            <span>Low Stock</span>
        </a>
    </nav>
</div>
