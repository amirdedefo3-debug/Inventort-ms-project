<?php
require_once '../includes/auth.php';
require_admin();
require_once '../db.php';
$page_title='🛒 Sales';
$s=get_settings($conn);

$period=isset($_GET['period'])?$_GET['period']:'monthly';
$search=isset($_GET['q'])?mysqli_real_escape_string($conn,$_GET['q']):'';
$where="WHERE 1=1";
if($search)$where.=" AND s.invoice_number LIKE '%$search%'";
if($period=='daily')  $where.=" AND DATE(s.created_at)=CURDATE()";
elseif($period=='weekly')$where.=" AND s.created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)";
elseif($period=='monthly')$where.=" AND MONTH(s.created_at)=MONTH(NOW()) AND YEAR(s.created_at)=YEAR(NOW())";
elseif($period=='yearly')$where.=" AND YEAR(s.created_at)=YEAR(NOW())";

$per=15;$page=max(1,intval($_GET['page']??1));$offset=($page-1)*$per;
$total=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM sales s $where"))['c'];
$total_pages=ceil($total/$per);
$summary=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) cnt,COALESCE(SUM(total),0) rev FROM sales s $where"));
$sales=mysqli_query($conn,"SELECT s.*,u.full_name FROM sales s LEFT JOIN users u ON s.user_id=u.id $where ORDER BY s.created_at DESC LIMIT $per OFFSET $offset");
?>
<!DOCTYPE html><html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?=$page_title?> | <?=$s['shop_name']?></title><link rel="stylesheet" href="../css/dashboard.css"></head>
<body>
<?php include '../includes/admin_sidebar.php'; ?>
<div class="main-area">
<?php include '../includes/topbar.php'; ?>
<div class="content">
  <div class="stats-grid" style="margin-bottom:20px">
    <div class="stat-card green"><div class="stat-icon si-green">🛒</div><div class="stat-info"><h3><?=$summary['cnt']?></h3><p>Transactions</p></div></div>
    <div class="stat-card blue"><div class="stat-icon si-blue">💰</div><div class="stat-info"><h3><?=$s['currency_symbol']?><?=number_format($summary['rev'],2)?></h3><p>Revenue</p></div></div>
    <div class="stat-card orange"><div class="stat-icon si-orange">📊</div><div class="stat-info"><h3><?=$s['currency_symbol']?><?=number_format($summary['cnt']>0?$summary['rev']/$summary['cnt']:0,2)?></h3><p>Avg Sale</p></div></div>
  </div>
<div class="card">
  <div class="card-header">
    <h3>Sales Records (<?=$total?>)</h3>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
      <form method="GET" class="search-bar">
        <input type="text" name="q" placeholder="🔍 Search invoice..." value="<?=htmlspecialchars($_GET['q']??'')?>">
        <select name="period" onchange="this.form.submit()">
          <option value="all" <?=$period=='all'?'selected':''?>>All Time</option>
          <option value="daily" <?=$period=='daily'?'selected':''?>>Today</option>
          <option value="weekly" <?=$period=='weekly'?'selected':''?>>This Week</option>
          <option value="monthly" <?=$period=='monthly'?'selected':''?>>This Month</option>
          <option value="yearly" <?=$period=='yearly'?'selected':''?>>This Year</option>
        </select>
        <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
      </form>
    </div>
  </div>
  <div class="table-responsive"><table>
    <thead><tr><th>#</th><th>Invoice</th><th>Cashier</th><th>Subtotal</th><th>Discount</th><th>Tax</th><th>Total</th><th>Payment</th><th>Date</th><th>Receipt</th></tr></thead>
    <tbody>
    <?php if(!$sales||mysqli_num_rows($sales)==0):?>
    <tr><td colspan="10"><div class="empty-state"><div class="ei">🛒</div><p>No sales found</p></div></td></tr>
    <?php else: $pm=['cash'=>'💵 Cash','card'=>'💳 Card','mobile'=>'📱 Mobile'];
    $i=$offset+1; while($r=mysqli_fetch_assoc($sales)):?>
    <tr>
      <td style="color:#aaa"><?=$i++?></td>
      <td><strong><?=htmlspecialchars($r['invoice_number'])?></strong></td>
      <td><?=htmlspecialchars($r['full_name']??'—')?></td>
      <td><?=$s['currency_symbol']?><?=number_format($r['subtotal'],2)?></td>
      <td><?=$r['discount']>0?'-'.$s['currency_symbol'].number_format($r['discount'],2):'—'?></td>
      <td><?=$r['tax']>0?$s['currency_symbol'].number_format($r['tax'],2):'—'?></td>
      <td><strong><?=$s['currency_symbol']?><?=number_format($r['total'],2)?></strong></td>
      <td><span class="badge b-info"><?=$pm[$r['payment_method']]??$r['payment_method']?></span></td>
      <td style="font-size:12px;color:#aaa"><?=date('d M Y H:i',strtotime($r['created_at']))?></td>
      <td><a href="../cashier/receipt.php?id=<?=$r['id']?>" target="_blank" class="btn btn-xs btn-secondary">🧾</a></td>
    </tr>
    <?php endwhile; endif;?>
    </tbody>
  </table></div>
  <?php if($total_pages>1):?>
  <div class="pagination">
    <?php for($p=1;$p<=$total_pages;$p++):?>
      <?=$p==$page?"<span class='cur'>$p</span>":"<a href='?period=$period&q=".urlencode($search)."&page=$p'>$p</a>"?>
    <?php endfor;?>
  </div>
  <?php endif;?>
</div>
</div></div>
</body></html>
