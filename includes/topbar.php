<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../db.php';
$unread = get_unread_notifications($conn);
$settings = get_settings($conn);
$page_title = $page_title ?? 'Dashboard';
?>
<div class="topbar">
  <div class="topbar-left">
    <button class="sidebar-toggle" onclick="document.querySelector('.sidebar').classList.toggle('collapsed')">☰</button>
    <h1 class="page-title"><?= $page_title ?></h1>
  </div>
  <div class="topbar-right">
    <div class="notif-wrap" onclick="toggleNotif()">
      <span class="notif-bell">🔔</span>
      <?php if ($unread > 0): ?>
      <span class="notif-badge"><?= $unread > 99 ? '99+' : $unread ?></span>
      <?php endif; ?>
      <div class="notif-dropdown" id="notifDropdown">
        <?php
        $notifs = mysqli_query($conn,"SELECT * FROM notifications ORDER BY created_at DESC LIMIT 8");
        if (mysqli_num_rows($notifs)==0): ?>
        <div class="notif-empty">No notifications</div>
        <?php else: while($n=mysqli_fetch_assoc($notifs)): ?>
        <div class="notif-item <?= $n['is_read']==0?'unread':'' ?>">
          <span class="notif-icon"><?= $n['type']=='warning'?'⚠️':($n['type']=='danger'?'🔴':($n['type']=='success'?'✅':'ℹ️')) ?></span>
          <div>
            <div class="notif-title"><?= htmlspecialchars($n['title']) ?></div>
            <div class="notif-time"><?= date('d M, H:i',strtotime($n['created_at'])) ?></div>
          </div>
        </div>
        <?php endwhile; endif; ?>
        <a href="notifications.php" class="notif-all">View all notifications</a>
      </div>
    </div>
    <div class="user-menu" onclick="this.classList.toggle('open')">
      <div class="user-avatar-sm"><?= strtoupper(substr($_SESSION['user_name']??'U',0,1)) ?></div>
      <div class="user-meta">
        <div class="user-nm"><?= htmlspecialchars($_SESSION['user_name']??'User') ?></div>
        <div class="user-rl"><?= get_role_label() ?></div>
      </div>
      <span>▾</span>
      <div class="user-dropdown">
        <a href="../logout.php">🚪 Logout</a>
      </div>
    </div>
  </div>
</div>
<script>
function toggleNotif(){
  var d=document.getElementById('notifDropdown');
  d.classList.toggle('show');
  if(d.classList.contains('show')){
    fetch('../mark_notif_read.php');
  }
}
document.addEventListener('click',function(e){
  if(!e.target.closest('.notif-wrap'))document.getElementById('notifDropdown').classList.remove('show');
  if(!e.target.closest('.user-menu'))document.querySelectorAll('.user-menu').forEach(m=>m.classList.remove('open'));
});
</script>
