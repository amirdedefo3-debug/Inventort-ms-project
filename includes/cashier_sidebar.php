<?php
if(session_status()===PHP_SESSION_NONE)session_start();
require_once __DIR__.'/../db.php';
$s=get_settings($conn);
$cur=basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <span class="logo-icon">🏪</span>
    <div class="logo-text"><h2><?=htmlspecialchars($s['shop_name'])?></h2><p>Cashier Panel</p></div>
  </div>
  <div class="sidebar-user">
    <div class="user-avatar"><?=strtoupper(substr($_SESSION['user_name']??'C',0,1))?></div>
    <div class="user-info">
      <div class="uname"><?=htmlspecialchars($_SESSION['user_name']??'')?></div>
      <div class="urole role-cashier">🛒 Cashier</div>
    </div>
  </div>
  <nav>
    <div class="nav-label">Main</div>
    <a href="dashboard.php" class="<?=$cur=='dashboard.php'?'active':''?>"><span class="nav-icon">📊</span><span class="nav-text">Dashboard</span></a>
    <div class="nav-label">Point of Sale</div>
    <a href="pos.php" class="<?=$cur=='pos.php'?'active':''?>"><span class="nav-icon">🛒</span><span class="nav-text">New Sale (POS)</span></a>
    <a href="sales_history.php" class="<?=$cur=='sales_history.php'?'active':''?>"><span class="nav-icon">📜</span><span class="nav-text">Sales History</span></a>
  </nav>
  <div class="sidebar-footer">
    <a href="../logout.php"><span class="nav-icon">🚪</span><span class="nav-text">Logout</span></a>
  </div>
</div>
