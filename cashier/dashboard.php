<?php
require_once '../includes/auth.php';
require_once '../db.php';
if(!is_cashier()&&!is_admin()){header("Location: ../login.php?error=access_denied");exit;}
$page_title='🛒 Cashier Dashboard';
$s=get_settings($conn);
$uid=$_SESSION['user_id'];

$today_sales=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM sales WHERE user_id=$uid AND DATE(created_at)=CURDATE()"))['c'];
$today_revenue=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(total),0) v FROM sales WHERE user_id=$uid AND DATE(created_at)=CURDATE()"))['v'];
$total_transactions=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM sales WHERE user_id=$uid"))['c'];
$recent=mysqli_query($conn,"SELECT * FROM sales WHERE user_id=$uid ORDER BY created_at DESC LIMIT 8");
?>
<!DOCTYPE html><html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?=$page_title?> | <?=$s['shop_name']?></title>
<link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
<?php include '../includes/cashier_sidebar.php'; ?>
<div class="main-area">
<?php include '../includes/topbar.php'; ?>
<div class="content">
  <div class="stats-grid">
    <div class="stat-card green"><div class="stat-icon si-green">🛒</div><div class="stat-info"><h3><?=$today_sales?></h3><p>Today's Sales</p></div></div>
    <div class="stat-card blue"><div class="stat-icon si-blue">💰</div><div class="stat-info"><h3><?=$s['currency_symbol']?><?=number_format($today_revenue,2)?></h3><p>Revenue Today</p></div></div>
    <div class="stat-card purple"><div class="stat-icon si-purple">📋</div><div class="stat-info"><h3><?=$total_transactions?></h3><p>Total Transactions</p></div></div>
  </div>
  <div class="card">
    <div class="card-header">
      <h3>📜 My Recent Sales</h3>
      <a href="pos.php" class="btn btn-primary">🛒 New Sale</a>
    </div>
    <div class="table-responsive"><table>
      <thead><tr><th>Invoice</th><th>Items</th><th>Total</th><th>Payment</th><th>Date</th><th>Action</th></tr></thead>
      <tbody>
      <?php if(mysqli_num_rows($recent)==0):?>
      <tr><td colspan="6"><div class="empty-state"><div class="ei">🛒</div><h3>No sales yet</h3><p><a href="pos.php" class="btn btn-primary btn-sm">Start selling</a></p></div></td></tr>
      <?php else: while($r=mysqli_fetch_assoc($recent)):
        $items=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM sale_items WHERE sale_id=".$r['id']))['c'];
        $pm=['cash'=>'💵 Cash','card'=>'💳 Card','mobile'=>'📱 Mobile'];
      ?>
      <tr>
        <td><strong><?=htmlspecialchars($r['invoice_number'])?></strong></td>
        <td><?=$items?> item(s)</td>
        <td><strong><?=$s['currency_symbol']?><?=number_format($r['total'],2)?></strong></td>
        <td><span class="badge b-info"><?=$pm[$r['payment_method']]??$r['payment_method']?></span></td>
        <td style="color:#aaa;font-size:12px"><?=date('d M Y, H:i',strtotime($r['created_at']))?></td>
        <td><a href="receipt.php?id=<?=$r['id']?>" class="btn btn-xs btn-secondary" target="_blank">🧾 Receipt</a></td>
      </tr>
      <?php endwhile; endif;?>
      </tbody>
    </table></div>
  </div>
</div>
</div>
</body></html>
