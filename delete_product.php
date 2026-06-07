<?php
require_once 'includes/auth.php';
require_admin(); // Admin only
require_once 'db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    $check = mysqli_query($conn, "SELECT id FROM products WHERE id = $id");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "DELETE FROM products WHERE id = $id");
    }
}

header("Location: products.php?msg=deleted");
exit;
?>
