<?php
require_once '../includes/auth.php';
require_admin();
require_once '../db.php';
$page_title='📄 Reports';
$s=get_settings($conn);

$type   = $_GET['type']??'sales';
$period = $_GET['period']??'monthly';
$from   = $_GET['from']??date('Y-m-01');
$to     = $_GET['to']??date('Y-m-d');
$export = $_GET['export']??'';

// Date condition
$date_cond="";
if($period=='daily')  $date_cond=" AND DATE(s.created_at)=CURDATE()";
elseif($period=='weekly') $date_cond=" AND s.created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)";
elseif($period=='monthly')$date_cond=" AND MONTH(s.created_at)=MONTH(NOW()) AND YEAR(s.created_at)=YEAR(NOW())";
elseif($period=='yearly') $date_cond=" AND YEAR(s.created_at)=YEAR(NOW())";
elseif($period=='custom') $date_cond=" AND DATE(s.created_at) BETWEEN '$from' AND '$to'";

// Sales report
if($type=='sales'){
    $data=mysqli_query($conn,"SELECT s.invoice_number,s.total,s.payment_method,s.created_at,u.full_name FROM sales s LEFT JOIN users u ON s.user_id=u.id WHERE 1=1 $date_cond ORDER BY s.created_at DESC");
    $summary=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) cnt,COALESCE(SUM(total),0) rev FROM sales s WHERE 1=1 $date_cond"));

    // CSV export
    if($export=='csv'){
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="sales_report_'.date('Ymd').'.csv"');
        $out=fopen('php://output','w');
        fputcsv($out,['Invoice','Cashier','Total','Payment','Date']);
        while($r=mysqli_fetch_assoc($data)) fputcsv($out,[$r['invoice_number'],$r['full_name']??'—',$r['total'],$r['payment_method'],date('d M Y H:i',strtotime($r['created_at']))]);
        fclose($out); exit;
    }
}
// Inventory report
elseif($type=='inventory'){
    $data=mysqli_query($conn,"SELECT p.name,p.barcode,p.quantity,p.reorder_level,p.selling_price,(p.quantity*p.selling_price) val,c.name cat,sp.name sup FROM products p LEFT JOIN categories c ON p.category_id=c.id LEFT JOIN suppliers sp ON p.supplier_id=sp.id ORDER BY p.name");
    if($export=='csv'){
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="inventory_report_'.date('Ymd').'.csv"');
        $out=fopen('php://output','w');
        fputcsv($out,['Product','Barcode','Category','Supplier','Qty','Reorder','Price','Value']);
        while($r=mysqli_fetch_assoc($data)) fputcsv($out,[$r['name'],$r['barcode']??'',$r['cat']??'',$r['sup']??'',$r['quantity'],$r['reorder_level'],$r['selling_price'],$r['val']]);
        fclose($out); exit;
    }
}
// Product report
elseif($type=='product'){
    $data=mysqli_query($conn,"SELECT si.product_name,SUM(si.quantity) sold,SUM(si.total_price) revenue FROM sale_items si JOIN sales s ON si.sale_id=s.id WHERE 1=1 $date_cond GROUP BY si.product_name ORDER BY sold DESC");
    if($export=='csv'){
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="product_report_'.date('Ymd').'.csv"');
        $out=fopen('php://output','w');
        fputcsv($out,['Product','Units Sold','Revenue']);
        while($r=mysqli_fetch_assoc($data)) fputcsv($out,[$r['product_name'],$r['sold'],$r['revenue']]);
        fclose($out); exit;
    }
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

<!-- Report Filters -->
<div class="card" style="margin-bottom:20px">
  <div class="card-body">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
      <div class="form-group" style="margin:0">
        <label>Report Type</label>
        <select name="type" onchange="this.form.submit()">
          <option value="sales" <?=$type=='sales'?'selected':''?>>🛒 Sales Report</option>
          <option value="inventory" <?=$type=='inventory'?'selected':''?>>📦 Inventory Report</option>
          <option value="product" <?=$type=='product'?'selected':''?>>🏆 Product Report</option>
        </select>
      </div>
      <?php if($type!='inventory'):?>
      <div class="form-group" style="margin:0">
        <label>Period</label>
        <select name="period" id="periodSel" onchange="toggleCustom(this.value)">
          <option value="daily" <?=$period=='daily'?'selected':''?>>Today</option>
          <option value="weekly" <?=$period=='weekly'?'selected':''?>>This Week</option>
          <option value="monthly" <?=$period=='monthly'?'selected':''?>>This Month</option>
          <option value="yearly" <?=$period=='yearly'?'selected':''?>>This Year</option>
          <option value="custom" <?=$period=='custom'?'selected':''?>>Custom Range</option>
        </select>
      </div>
      <div id="customDates" style="display:<?=$period=='custom'?'flex':'none'?>;gap:8px;">
        <div class="form-group" style="margin:0"><label>From</label><input type="date" name="from" value="<?=htmlspecialchars($from)?>"></div>
        <div class="form-group" style="margin:0"><label>To</label><input type="date" name="to" value="<?=htmlspecialchars($to)?>"></div>
      </div>
      <?php endif;?>
      <button type="submit" class="btn btn-secondary">🔄 Generate</button>
      <a href="?type=<?=$type?>&period=<?=$period?>&from=<?=$from?>&to=<?=$to?>&export=csv" class="btn btn-success">⬇️ Export CSV</a>
    </form>
  </div>
</div>

<?php if($type=='sales'&&isset($summary)):?>
<div class="stats-grid" style="margin-bottom:20px">
  <div class="stat-card green"><div class="stat-icon si-green">🛒</div><div class="stat-info"><h3><?=$summary['cnt']?></h3><p>Total Transactions</p></div></div>
  <div class="stat-card blue"><div class="stat-icon si-blue">💰</div><div class="stat-info"><h3><?=$s['currency_symbol']?><?=number_format($summary['rev'],2)?></h3><p>Total Revenue</p></div></div>
  <div class="stat-card orange"><div class="stat-icon si-orange">📊</div><div class="stat-info"><h3><?=$s['currency_symbol']?><?=number_format($summary['cnt']>0?$summary['rev']/$summary['cnt']:0,2)?></h3><p>Avg per Sale</p></div></div>
</div>
<?php endif;?>

<div class="card">
  <div class="card-header"><h3>
    <?php if($type=='sales'):?>🛒 Sales Report
    <?php elseif($type=='inventory'):?>📦 Inventory Report
    <?php else:?>🏆 Product Performance Report<?php endif;?>
  </h3></div>
  <div class="table-responsive"><table>
    <?php if($type=='sales'):?>
    <thead><tr><th>Invoice</th><th>Cashier</th><th>Total</th><th>Payment</th><th>Date</th></tr></thead>
    <tbody>
    <?php $pm=['cash'=>'💵 Cash','card'=>'💳 Card','mobile'=>'📱 Mobile'];
    if(!$data||mysqli_num_rows($data)==0):?>
    <tr><td colspan="5"><div class="empty-state"><div class="ei">📭</div><p>No data for this period</p></div></td></tr>
    <?php else: while($r=mysqli_fetch_assoc($data)):?>
    <tr><td><strong><?=htmlspecialchars($r['invoice_number'])?></strong></td><td><?=htmlspecialchars($r['full_name']??'—')?></td><td><?=$s['currency_symbol']?><?=number_format($r['total'],2)?></td><td><span class="badge b-info"><?=$pm[$r['payment_method']]??$r['payment_method']?></span></td><td style="font-size:12px;color:#aaa"><?=date('d M Y H:i',strtotime($r['created_at']))?></td></tr>
    <?php endwhile; endif;?>

    <?php elseif($type=='inventory'):?>
    <thead><tr><th>Product</th><th>Category</th><th>Supplier</th><th>Qty</th><th>Reorder</th><th>Price</th><th>Value</th><th>Status</th></tr></thead>
    <tbody>
    <?php if(!$data||mysqli_num_rows($data)==0):?>
    <tr><td colspan="8"><div class="empty-state"><div class="ei">📭</div><p>No products</p></div></td></tr>
    <?php else: while($r=mysqli_fetch_assoc($data)):
      $st=$r['quantity']==0?['Out of Stock','b-danger']:($r['quantity']<=$r['reorder_level']?['Low Stock','b-warning']:['In Stock','b-success']);?>
    <tr><td><strong><?=htmlspecialchars($r['name'])?></strong></td><td><?=$r['cat']?'<span class="badge b-info">'.htmlspecialchars($r['cat']).'</span>':'—'?></td><td><?=htmlspecialchars($r['sup']??'—')?></td><td><?=$r['quantity']?></td><td><?=$r['reorder_level']?></td><td><?=$s['currency_symbol']?><?=number_format($r['selling_price'],2)?></td><td><?=$s['currency_symbol']?><?=number_format($r['val'],2)?></td><td><span class="badge <?=$st[1]?>"><?=$st[0]?></span></td></tr>
    <?php endwhile; endif;?>

    <?php else:?>
    <thead><tr><th>Product</th><th>Units Sold</th><th>Revenue</th></tr></thead>
    <tbody>
    <?php if(!$data||mysqli_num_rows($data)==0):?>
    <tr><td colspan="3"><div class="empty-state"><div class="ei">📭</div><p>No sales data</p></div></td></tr>
    <?php else: $rank=1; while($r=mysqli_fetch_assoc($data)):?>
    <tr><td><strong>#<?=$rank++?> <?=htmlspecialchars($r['product_name'])?></strong></td><td><?=$r['sold']?> units</td><td><?=$s['currency_symbol']?><?=number_format($r['revenue'],2)?></td></tr>
    <?php endwhile; endif;?>
    <?php endif;?>
    </tbody>
  </table></div>
</div>
</div></div>
<script>
function toggleCustom(v){document.getElementById('customDates').style.display=v==='custom'?'flex':'none';}
</script>
</body></html>
