<?php
if(session_status()===PHP_SESSION_NONE)session_start();
require_once __DIR__.'/../db.php';
$s=get_settings($conn);
$cur=basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <span class="logo-icon">🏪</span>
    <div class="logo-text"><h2><?=htmlspecialchars($s['shop_name'])?></h2><p>Admin Panel</p></div>
  </div>
  <div class="sidebar-user">
    <div class="user-avatar"><?=strtoupper(substr($_SESSION['user_name']??'A',0,1))?></div>
    <div class="user-info">
      <div class="uname"><?=htmlspecialchars($_SESSION['user_name']??'')?></div>
      <div class="urole role-admin">🔑 Administrator</div>
    </div>
  </div>
  <nav>
    <div class="nav-label">Main</div>
    <a href="dashboard.php" class="<?=$cur=='dashboard.php'?'active':''?>"><span class="nav-icon">📊</span><span class="nav-text">Dashboard</span></a>
    <div class="nav-label">Inventory</div>
    <a href="products.php" class="<?=$cur=='products.php'||$cur=='add_product.php'||$cur=='edit_product.php'?'active':''?>"><span class="nav-icon">📦</span><span class="nav-text">Products</span></a>
    <a href="categories.php" class="<?=$cur=='categories.php'?'active':''?>"><span class="nav-icon">🗂️</span><span class="nav-text">Categories</span></a>
    <a href="suppliers.php" class="<?=$cur=='suppliers.php'?'active':''?>"><span class="nav-icon">🚚</span><span class="nav-text">Suppliers</span></a>
    <a href="stock_monitor.php" class="<?=$cur=='stock_monitor.php'?'active':''?>"><span class="nav-icon">📉</span><span class="nav-text">Stock Monitor</span></a>
    <div class="nav-label">Sales</div>
    <a href="sales.php" class="<?=$cur=='sales.php'?'active':''?>"><span class="nav-icon">🛒</span><span class="nav-text">Sales</span></a>
    <a href="reports.php" class="<?=$cur=='reports.php'?'active':''?>"><span class="nav-icon">📄</span><span class="nav-text">Reports</span></a>
    <div class="nav-label">Admin</div>
    <a href="users.php" class="<?=$cur=='users.php'?'active':''?>"><span class="nav-icon">👥</span><span class="nav-text">Users</span></a>
    <a href="activity_logs.php" class="<?=$cur=='activity_logs.php'?'active':''?>"><span class="nav-icon">📋</span><span class="nav-text">Activity Logs</span></a>
    <a href="settings.php" class="<?=$cur=='settings.php'?'active':''?>"><span class="nav-icon">⚙️</span><span class="nav-text">Settings</span></a>
  </nav>
  <div class="sidebar-footer">
    <a href="../logout.php"><span class="nav-icon">🚪</span><span class="nav-text">Logout</span></a>
  </div>
</div>
