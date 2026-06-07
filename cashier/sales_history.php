<?php
require_once '../includes/auth.php';
require_once '../db.php';
if(!is_cashier()&&!is_admin()){header("Location: ../login.php?error=access_denied");exit;}
$page_title='📜 Sales History';
$s=get_settings($conn);
$uid=$_SESSION['user_id'];

$search=isset($_GET['q'])?mysqli_real_escape_string($conn,$_GET['q']):'';
$date_f=isset($_GET['date'])?mysqli_real_escape_string($conn,$_GET['date']):'';
$where="WHERE s.user_id=$uid";
if($search)$where.=" AND s.invoice_number LIKE '%$search%'";
if($date_f)$where.=" AND DATE(s.created_at)='$date_f'";

$per=15;$page=max(1,intval($_GET['page']??1));$offset=($page-1)*$per;
$total=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM sales s $where"))['c'];
$total_pages=ceil($total/$per);
$summary=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) cnt,COALESCE(SUM(total),0) rev FROM sales s $where"));
$sales=mysqli_query($conn,"SELECT * FROM sales s $where ORDER BY s.created_at DESC LIMIT $per OFFSET $offset");
$pm=['cash'=>'💵 Cash','card'=>'💳 Card','mobile'=>'📱 Mobile'];
?>
<!DOCTYPE html><html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?=$page_title?> | <?=$s['shop_name']?></title><link rel="stylesheet" href="../css/dashboard.css"></head>
<body>
<?php include '../includes/cashier_sidebar.php'; ?>
<div class="main-area">
<?php include '../includes/topbar.php'; ?>
<div class="content">
  <div class="stats-grid" style="margin-bottom:20px">
    <div class="stat-card green"><div class="stat-icon si-green">🛒</div><div class="stat-info"><h3><?=$summary['cnt']?></h3><p>My Sales</p></div></div>
    <div class="stat-card blue"><div class="stat-icon si-blue">💰</div><div class="stat-info"><h3><?=$s['currency_symbol']?><?=number_format($summary['rev'],2)?></h3><p>My Revenue</p></div></div>
  </div>
<div class="card">
  <div class="card-header">
    <h3>My Sales History</h3>
    <form method="GET" class="search-bar">
      <input type="text" name="q" placeholder="🔍 Invoice number..." value="<?=htmlspecialchars($_GET['q']??'')?>">
      <input type="date" name="date" value="<?=htmlspecialchars($_GET['date']??'')?>">
      <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
      <?php if($search||$date_f):?><a href="sales_history.php" class="btn btn-outline btn-sm">Clear</a><?php endif;?>
    </form>
  </div>
  <div class="table-responsive"><table>
    <thead><tr><th>#</th><th>Invoice</th><th>Items</th><th>Total</th><th>Payment</th><th>Date</th><th>Receipt</th></tr></thead>
    <tbody>
    <?php if(!$sales||mysqli_num_rows($sales)==0):?>
    <tr><td colspan="7"><div class="empty-state"><div class="ei">📭</div><h3>No sales found</h3><p><a href="pos.php" class="btn btn-primary btn-sm">Start Selling</a></p></div></td></tr>
    <?php else: $i=$offset+1; while($r=mysqli_fetch_assoc($sales)):
      $items_c=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM sale_items WHERE sale_id=".$r['id']))['c'];
    ?>
    <tr>
      <td style="color:#aaa"><?=$i++?></td>
      <td><strong><?=htmlspecialchars($r['invoice_number'])?></strong></td>
      <td><?=$items_c?> item(s)</td>
      <td><strong><?=$s['currency_symbol']?><?=number_format($r['total'],2)?></strong></td>
      <td><span class="badge b-info"><?=$pm[$r['payment_method']]??$r['payment_method']?></span></td>
      <td style="font-size:12px;color:#aaa"><?=date('d M Y H:i',strtotime($r['created_at']))?></td>
      <td><a href="receipt.php?id=<?=$r['id']?>" target="_blank" class="btn btn-xs btn-secondary">🧾 View</a></td>
    </tr>
    <?php endwhile; endif;?>
    </tbody>
  </table></div>
  <?php if($total_pages>1):?>
  <div class="pagination"><?php for($p=1;$p<=$total_pages;$p++):?><?=$p==$page?"<span class='cur'>$p</span>":"<a href='?q=".urlencode($search)."&date=$date_f&page=$p'>$p</a>"?><?php endfor;?></div>
  <?php endif;?>
</div>
</div></div>
</body></html>
