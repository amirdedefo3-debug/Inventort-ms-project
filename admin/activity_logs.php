<?php
require_once '../includes/auth.php';
require_admin();
require_once '../db.php';
$page_title='📋 Activity Logs';
$s=get_settings($conn);

$search=isset($_GET['q'])?mysqli_real_escape_string($conn,$_GET['q']):'';
$date_f=isset($_GET['date'])?mysqli_real_escape_string($conn,$_GET['date']):'';
$where="WHERE 1=1";
if($search)$where.=" AND (user_name LIKE '%$search%' OR action LIKE '%$search%' OR details LIKE '%$search%')";
if($date_f)$where.=" AND DATE(created_at)='$date_f'";

$per=20;$page=max(1,intval($_GET['page']??1));$offset=($page-1)*$per;
$total=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM activity_logs $where"))['c'];
$total_pages=ceil($total/$per);
$logs=mysqli_query($conn,"SELECT * FROM activity_logs $where ORDER BY created_at DESC LIMIT $per OFFSET $offset");

if(isset($_GET['export'])){
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="activity_logs_'.date('Ymd').'.csv"');
    $out=fopen('php://output','w');
    fputcsv($out,['ID','User','Action','Details','IP','Date']);
    $all=mysqli_query($conn,"SELECT * FROM activity_logs $where ORDER BY created_at DESC");
    while($r=mysqli_fetch_assoc($all)) fputcsv($out,[$r['id'],$r['user_name'],$r['action'],$r['details'],$r['ip_address'],date('d M Y H:i',strtotime($r['created_at']))]);
    fclose($out); exit;
}
?>
<!DOCTYPE html><html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?=$page_title?> | <?=$s['shop_name']?></title><link rel="stylesheet" href="../css/dashboard.css"></head>
<body>
<?php include '../includes/admin_sidebar.php'; ?>
<div class="main-area">
<?php include '../includes/topbar.php'; ?>
<div class="content">
<div class="card">
  <div class="card-header">
    <h3>📋 Activity Logs (<?=$total?>)</h3>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
      <form method="GET" class="search-bar">
        <input type="text" name="q" placeholder="🔍 Search action/user..." value="<?=htmlspecialchars($_GET['q']??'')?>">
        <input type="date" name="date" value="<?=htmlspecialchars($_GET['date']??'')?>">
        <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        <?php if($search||$date_f):?><a href="activity_logs.php" class="btn btn-outline btn-sm">Clear</a><?php endif;?>
      </form>
      <a href="?export=1&q=<?=urlencode($search)?>&date=<?=urlencode($date_f)?>" class="btn btn-success btn-sm">⬇️ Export CSV</a>
    </div>
  </div>
  <div class="table-responsive"><table>
    <thead><tr><th>#</th><th>User</th><th>Action</th><th>Details</th><th>IP</th><th>Date & Time</th></tr></thead>
    <tbody>
    <?php if(!$logs||mysqli_num_rows($logs)==0):?>
    <tr><td colspan="6"><div class="empty-state"><div class="ei">📋</div><p>No activity logs yet</p></div></td></tr>
    <?php else: $i=$offset+1; while($r=mysqli_fetch_assoc($logs)):?>
    <tr>
      <td style="color:#aaa"><?=$i++?></td>
      <td><strong><?=htmlspecialchars($r['user_name']??'—')?></strong></td>
      <td><?=htmlspecialchars($r['action'])?></td>
      <td style="font-size:12px;color:#888"><?=htmlspecialchars(substr($r['details']??'',0,60))?><?=strlen($r['details']??'')>60?'...':''?></td>
      <td style="font-size:12px;color:#aaa"><?=htmlspecialchars($r['ip_address']??'—')?></td>
      <td style="font-size:12px;color:#aaa"><?=date('d M Y, H:i',strtotime($r['created_at']))?></td>
    </tr>
    <?php endwhile; endif;?>
    </tbody>
  </table></div>
  <?php if($total_pages>1):?>
  <div class="pagination">
    <?php for($p=1;$p<=$total_pages;$p++):?>
      <?=$p==$page?"<span class='cur'>$p</span>":"<a href='?q=".urlencode($search)."&date=$date_f&page=$p'>$p</a>"?>
    <?php endfor;?>
  </div>
  <?php endif;?>
</div>
</div></div>
</body></html>
