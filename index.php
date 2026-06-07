<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$role = $_SESSION['user_role'] ?? 'cashier';
if ($role === 'admin')   { header("Location: admin/dashboard.php");   exit; }
if ($role === 'manager') { header("Location: manager/dashboard.php"); exit; }
header("Location: cashier/dashboard.php");
exit;
?>
