<?php
/**
 * Stock Alert Checker — safely upgrades products table and generates notifications.
 */
if (!isset($conn)) require_once __DIR__ . '/db.php';

// Disable strict exceptions for ALTER statements
mysqli_report(MYSQLI_REPORT_OFF);

// ── Get current columns ───────────────────────────────────────────
$prod_cols = [];
$pc = mysqli_query($conn, "SHOW COLUMNS FROM products");
if ($pc) while ($c = mysqli_fetch_assoc($pc)) $prod_cols[] = $c['Field'];

// ── Rename min_stock → reorder_level ─────────────────────────────
if (in_array('min_stock', $prod_cols) && !in_array('reorder_level', $prod_cols)) {
    mysqli_query($conn, "ALTER TABLE products CHANGE `min_stock` `reorder_level` INT NOT NULL DEFAULT 5");
    $prod_cols[] = 'reorder_level';
}

// ── Add reorder_level if still missing ───────────────────────────
if (!in_array('reorder_level', $prod_cols)) {
    mysqli_query($conn, "ALTER TABLE products ADD COLUMN `reorder_level` INT NOT NULL DEFAULT 5");
    $prod_cols[] = 'reorder_level';
}

// ── Add other missing columns one by one ─────────────────────────
if (!in_array('barcode', $prod_cols)) {
    mysqli_query($conn, "ALTER TABLE products ADD COLUMN `barcode` VARCHAR(100) DEFAULT NULL");
}

if (!in_array('supplier_id', $prod_cols)) {
    mysqli_query($conn, "ALTER TABLE products ADD COLUMN `supplier_id` INT DEFAULT NULL");
}

if (!in_array('purchase_price', $prod_cols)) {
    mysqli_query($conn, "ALTER TABLE products ADD COLUMN `purchase_price` DECIMAL(10,2) DEFAULT 0.00");
    if (in_array('price', $prod_cols)) {
        mysqli_query($conn, "UPDATE products SET purchase_price = price * 0.6");
    }
}

if (!in_array('selling_price', $prod_cols)) {
    mysqli_query($conn, "ALTER TABLE products ADD COLUMN `selling_price` DECIMAL(10,2) DEFAULT 0.00");
    if (in_array('price', $prod_cols)) {
        mysqli_query($conn, "UPDATE products SET selling_price = price");
    }
}

// ── Ensure notifications table exists ────────────────────────────
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    message TEXT,
    type VARCHAR(50) DEFAULT 'info',
    is_read TINYINT DEFAULT 0,
    user_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// ── Re-enable strict mode ─────────────────────────────────────────
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ── Generate low/out-of-stock notifications ───────────────────────
$low_items = mysqli_query($conn, "SELECT * FROM products WHERE quantity <= reorder_level");
if ($low_items) {
    while ($p = mysqli_fetch_assoc($low_items)) {
        $title   = $p['quantity'] == 0
            ? "Out of Stock: {$p['name']}"
            : "Low Stock: {$p['name']}";
        $message = "'{$p['name']}' has {$p['quantity']} unit(s) left. Reorder level: {$p['reorder_level']}.";
        $type    = $p['quantity'] == 0 ? 'danger' : 'warning';

        // Avoid duplicate notifications within last 1 hour
        $name_esc = mysqli_real_escape_string($conn, $p['name']);
        $exists = mysqli_query($conn,
            "SELECT id FROM notifications
             WHERE title LIKE '%$name_esc%'
             AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
        );
        if ($exists && mysqli_num_rows($exists) == 0) {
            $t = mysqli_real_escape_string($conn, $title);
            $m = mysqli_real_escape_string($conn, $message);
            mysqli_query($conn,
                "INSERT INTO notifications (title, message, type) VALUES ('$t','$m','$type')"
            );
        }
    }
}

if (isset($_GET['standalone'])) {
    echo json_encode(['status' => 'ok']);
}
?>
