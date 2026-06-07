<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// db.php is already included by the page that includes topbar
$unread   = function_exists('get_unread_notifications') ? get_unread_notifications($conn) : 0;
$settings = function_exists('get_settings') ? get_settings($conn) : ['shop_name'=>'ShopStock'];
$page_title = $page_title ?? 'Dashboard';

// Determine correct relative base path (admin/, manager/, cashier/ → ../)
$role = $_SESSION['user_role'] ?? 'cashier';
$notif_url  = 'notifications.php';
$logout_url = '../logout.php';
?>
<div class="topbar">
  <div class="topbar-left">
    <button class="sidebar-toggle" onclick="document.getElementById('sidebar').classList.toggle('collapsed')">☰</button>
    <h1 class="page-title"><?= $page_title ?></h1>
  </div>
  <div class="topbar-right">

    <!-- Notification Bell -->
    <div class="notif-wrap" id="notifWrap">
      <span class="notif-bell" onclick="toggleNotif(event)">🔔</span>
      <?php if ($unread > 0): ?>
      <span class="notif-badge"><?= $unread > 99 ? '99+' : $unread ?></span>
      <?php endif; ?>
      <div class="notif-dropdown" id="notifDropdown">
        <?php
        $notifs = mysqli_query($conn, "SELECT * FROM notifications ORDER BY created_at DESC LIMIT 8");
        if (!$notifs || mysqli_num_rows($notifs) == 0): ?>
        <div class="notif-empty">🔔 No notifications</div>
        <?php else:
        $icons = ['warning'=>'⚠️','danger'=>'🔴','success'=>'✅','info'=>'ℹ️'];
        while ($n = mysqli_fetch_assoc($notifs)): ?>
        <div class="notif-item <?= $n['is_read']==0 ? 'unread' : '' ?>">
          <span class="notif-icon"><?= $icons[$n['type']] ?? 'ℹ️' ?></span>
          <div>
            <div class="notif-title"><?= htmlspecialchars($n['title']) ?></div>
            <div class="notif-time"><?= date('d M, H:i', strtotime($n['created_at'])) ?></div>
          </div>
        </div>
        <?php endwhile; endif; ?>
        <a href="<?= $notif_url ?>" class="notif-all">View all notifications →</a>
      </div>
    </div>

    <!-- User Menu -->
    <div class="user-menu" id="userMenu">
      <div class="user-avatar-sm"><?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?></div>
      <div class="user-meta">
        <div class="user-nm"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></div>
        <div class="user-rl"><?= get_role_label() ?></div>
      </div>
      <span>▾</span>
      <div class="user-dropdown">
        <a href="<?= $logout_url ?>">🚪 Logout</a>
      </div>
    </div>

  </div>
</div>

<script>
function toggleNotif(e) {
    e.stopPropagation();
    var d = document.getElementById('notifDropdown');
    d.classList.toggle('show');
    if (d.classList.contains('show')) {
        fetch('../mark_notif_read.php').catch(function(){});
    }
}
document.getElementById('userMenu').addEventListener('click', function(e) {
    e.stopPropagation();
    this.classList.toggle('open');
});
document.addEventListener('click', function() {
    document.getElementById('notifDropdown').classList.remove('show');
    document.getElementById('userMenu').classList.remove('open');
});
</script>
