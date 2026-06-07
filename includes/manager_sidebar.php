<?php
if(session_status()===PHP_SESSION_NONE)session_start();
require_once __DIR__.'/../db.php';
$s=get_settings($conn);
$cur=basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <span class="logo-icon">🏪</span>
    <div class="logo-text"><h2><?=htmlspecialchars($s['shop_name'])?></h2><p>Manager Panel</p></div>
  </div>
  <div class="sidebar-user">
    <div class="user-avatar"><?=strtoupper(substr($_SESSION['user_name']??'M',0,1))?></div>
    <div class="user-info">
      <div class="uname"><?=htmlspecialchars($_SESSION['user_name']??'')?></div>
      <div class="urole role-manager">📦 Inv. Manager</div>
    </div>
  </div>
  <nav>
    <div class="nav-label">Main</div>
    <a href="dashboard.php" class="<?=$cur=='dashboard.php'?'active':''?>"><span class="nav-icon">📊</span><span class="nav-text">Dashboard</span></a>
    <div class="nav-label">Products</div>
    <a href="products.php" class="<?=$cur=='products.php'?'active':''?>"><span class="nav-icon">📦</span><span class="nav-text">Products</span></a>
    <a href="add_product.php" class="<?=$cur=='add_product.php'?'active':''?>"><span class="nav-icon">➕</span><span class="nav-text">Add Product</span></a>
    <div class="nav-label">Stock</div>
    <a href="stock_in.php" class="<?=$cur=='stock_in.php'?'active':''?>"><span class="nav-icon">📥</span><span class="nav-text">Stock In</span></a>
    <a href="stock_out.php" class="<?=$cur=='stock_out.php'?'active':''?>"><span class="nav-icon">📤</span><span class="nav-text">Stock Out</span></a>
    <a href="stock_adjustment.php" class="<?=$cur=='stock_adjustment.php'?'active':''?>"><span class="nav-icon">🔧</span><span class="nav-text">Adjustment</span></a>
    <a href="stock_history.php" class="<?=$cur=='stock_history.php'?'active':''?>"><span class="nav-icon">📜</span><span class="nav-text">Stock History</span></a>
    <div class="nav-label">Suppliers & Orders</div>
    <a href="suppliers.php" class="<?=$cur=='suppliers.php'?'active':''?>"><span class="nav-icon">🚚</span><span class="nav-text">Suppliers</span></a>
    <a href="purchase_orders.php" class="<?=$cur=='purchase_orders.php'?'active':''?>"><span class="nav-icon">🧾</span><span class="nav-text">Purchase Orders</span></a>
  </nav>
  <div class="sidebar-footer">
    <a href="../logout.php"><span class="nav-icon">🚪</span><span class="nav-text">Logout</span></a>
  </div>
</div>
