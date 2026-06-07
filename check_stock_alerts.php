<?php
/**
 * Stock Alert Checker — called internally to generate notifications for low/out-of-stock items.
 * Include this file after db.php where needed, or call it as a standalone page.
 */
require_once __DIR__ . '/db.php';

$low_items = mysqli_query($conn, "SELECT * FROM products WHERE quantity <= reorder_level");
while ($p = mysqli_fetch_assoc($low_items)) {
    $title   = $p['quantity'] == 0 ? "Out of Stock: {$p['name']}" : "Low Stock: {$p['name']}";
    $message = "Product '{$p['name']}' has only {$p['quantity']} unit(s) left. Reorder level: {$p['reorder_level']}.";
    $type    = $p['quantity'] == 0 ? 'danger' : 'warning';

    // Avoid duplicate notifications (check last 1 hour)
    $name_esc = mysqli_real_escape_string($conn, $p['name']);
    $exists = mysqli_query($conn, "SELECT id FROM notifications WHERE title LIKE '%{$name_esc}%' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    if (mysqli_num_rows($exists) == 0) {
        $t = mysqli_real_escape_string($conn, $title);
        $m = mysqli_real_escape_string($conn, $message);
        mysqli_query($conn, "INSERT INTO notifications (title, message, type) VALUES ('$t','$m','$type')");
    }
}

if (isset($_GET['standalone'])) {
    echo json_encode(['status' => 'ok', 'checked' => mysqli_num_rows($low_items)]);
}
?>
