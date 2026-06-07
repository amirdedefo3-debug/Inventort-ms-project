<?php
require_once '../includes/auth.php';
require_once '../db.php';
if(!is_cashier()&&!is_admin()){header("Location: ../login.php?error=access_denied");exit;}
$page_title = '🔔 Notifications';
$s = get_settings($conn);
mysqli_query($conn, "UPDATE notifications SET is_read=1");
$notifs = mysqli_query($conn, "SELECT * FROM notifications ORDER BY created_at DESC");
?>
<!DOCTYPE html><html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?=$page_title?> | <?=$s['shop_name']?></title>
<link rel="stylesheet" href="../css/dashboard.css"></head>
<body>
<?php include '../includes/cashier_sidebar.php'; ?>
<div class="main-area">
<?php include '../includes/topbar.php'; ?>
<div class="content">
  <div class="card">
    <div class="card-header"><h3>🔔 Notifications (<?=mysqli_num_rows($notifs)?>)</h3></div>
    <div class="table-responsive"><table>
      <thead><tr><th>Type</th><th>Title</th><th>Message</th><th>Date</th></tr></thead>
      <tbody>
      <?php if(!$notifs||mysqli_num_rows($notifs)==0): ?>
      <tr><td colspan="4"><div class="empty-state"><div class="ei">🔔</div><h3>No notifications</h3></div></td></tr>
      <?php else:
      $icons=['warning'=>'⚠️','danger'=>'🔴','success'=>'✅','info'=>'ℹ️'];
      $badge=['warning'=>'b-warning','danger'=>'b-danger','success'=>'b-success','info'=>'b-info'];
      while($r=mysqli_fetch_assoc($notifs)):$ic=$icons[$r['type']]??'ℹ️';$bc=$badge[$r['type']]??'b-info';?>
      <tr>
        <td><span class="badge <?=$bc?>"><?=$ic?> <?=ucfirst($r['type'])?></span></td>
        <td><strong><?=htmlspecialchars($r['title'])?></strong></td>
        <td style="font-size:13px;color:#666"><?=htmlspecialchars($r['message']??'')?></td>
        <td style="font-size:12px;color:#aaa"><?=date('d M Y, H:i',strtotime($r['created_at']))?></td>
      </tr>
      <?php endwhile; endif; ?>
      </tbody>
    </table></div>
  </div>
</div></div>
</body></html>
