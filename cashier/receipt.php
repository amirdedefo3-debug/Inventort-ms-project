<?php
require_once '../includes/auth.php';
require_once '../db.php';
$s = get_settings($conn);
$id = intval($_GET['id']??0);
if(!$id){header("Location: dashboard.php");exit;}
$sale = mysqli_fetch_assoc(mysqli_query($conn,"SELECT s.*,u.full_name FROM sales s LEFT JOIN users u ON s.user_id=u.id WHERE s.id=$id"));
if(!$sale){header("Location: dashboard.php");exit;}
$items = mysqli_query($conn,"SELECT * FROM sale_items WHERE sale_id=$id");
$pm=['cash'=>'Cash','card'=>'Card','mobile'=>'Mobile Payment'];
?>
<!DOCTYPE html><html lang="en">
<head><meta charset="UTF-8"><title>Receipt <?=htmlspecialchars($sale['invoice_number'])?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Courier New',monospace;background:#f0f0f0;padding:20px;}
.receipt{background:#fff;max-width:380px;margin:0 auto;padding:28px;box-shadow:0 2px 20px rgba(0,0,0,.1);}
.shop-name{text-align:center;font-size:22px;font-weight:900;letter-spacing:2px;margin-bottom:4px;}
.shop-info{text-align:center;font-size:12px;color:#666;margin-bottom:16px;line-height:1.6;}
.divider{border:none;border-top:2px dashed #ccc;margin:12px 0;}
.inv-info{font-size:12px;margin-bottom:12px;}
.inv-info div{display:flex;justify-content:space-between;padding:2px 0;}
table{width:100%;font-size:12px;margin-bottom:4px;}
th{text-align:left;padding:4px 0;border-bottom:1px solid #eee;font-size:11px;text-transform:uppercase;}
td{padding:5px 0;vertical-align:top;}
td:last-child{text-align:right;}
.totals{margin-top:10px;font-size:13px;}
.totals div{display:flex;justify-content:space-between;padding:3px 0;}
.grand-total{font-size:16px;font-weight:900;border-top:2px solid #000;padding-top:8px;margin-top:4px;}
.footer{text-align:center;font-size:11px;color:#888;margin-top:16px;line-height:1.8;}
.btn-row{display:flex;gap:10px;margin-top:20px;justify-content:center;}
.btn{padding:10px 22px;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;border:none;font-family:sans-serif;}
.btn-print{background:#1a1a2e;color:#fff;}
.btn-back{background:#e8e8e8;color:#333;}
@media print{
  body{background:#fff;padding:0;}
  .receipt{box-shadow:none;}
  .btn-row{display:none;}
}
</style>
</head>
<body>
<div class="receipt">
  <div class="shop-name"><?=htmlspecialchars($s['shop_name'])?></div>
  <div class="shop-info">
    <?php if($s['shop_address']):?><?=htmlspecialchars($s['shop_address'])?><br><?php endif;?>
    <?php if($s['shop_phone']):?>Tel: <?=htmlspecialchars($s['shop_phone'])?><br><?php endif;?>
  </div>
  <hr class="divider">
  <div class="inv-info">
    <div><span>Invoice:</span><strong><?=htmlspecialchars($sale['invoice_number'])?></strong></div>
    <div><span>Date:</span><span><?=date('d M Y H:i',strtotime($sale['created_at']))?></span></div>
    <div><span>Cashier:</span><span><?=htmlspecialchars($sale['full_name']??'—')?></span></div>
    <div><span>Payment:</span><span><?=$pm[$sale['payment_method']]??$sale['payment_method']?></span></div>
  </div>
  <hr class="divider">
  <table>
    <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
    <tbody>
    <?php while($r=mysqli_fetch_assoc($items)):?>
    <tr>
      <td><?=htmlspecialchars($r['product_name'])?></td>
      <td><?=$r['quantity']?></td>
      <td><?=$s['currency_symbol']?><?=number_format($r['unit_price'],2)?></td>
      <td><?=$s['currency_symbol']?><?=number_format($r['total_price'],2)?></td>
    </tr>
    <?php endwhile;?>
    </tbody>
  </table>
  <hr class="divider">
  <div class="totals">
    <div><span>Subtotal</span><span><?=$s['currency_symbol']?><?=number_format($sale['subtotal'],2)?></span></div>
    <?php if($sale['discount']>0):?><div><span>Discount</span><span>-<?=$s['currency_symbol']?><?=number_format($sale['discount'],2)?></span></div><?php endif;?>
    <?php if($sale['tax']>0):?><div><span>Tax</span><span><?=$s['currency_symbol']?><?=number_format($sale['tax'],2)?></span></div><?php endif;?>
    <div class="grand-total"><span>TOTAL</span><span><?=$s['currency_symbol']?><?=number_format($sale['total'],2)?></span></div>
    <div><span>Paid</span><span><?=$s['currency_symbol']?><?=number_format($sale['amount_paid'],2)?></span></div>
    <?php if($sale['change_amount']>0):?><div><span>Change</span><span><?=$s['currency_symbol']?><?=number_format($sale['change_amount'],2)?></span></div><?php endif;?>
  </div>
  <hr class="divider">
  <div class="footer">Thank you for your purchase!<br>Please come again 😊</div>
</div>
<div class="btn-row">
  <button class="btn btn-print" onclick="window.print()">🖨️ Print Receipt</button>
  <a href="dashboard.php" class="btn btn-back">← Back</a>
  <a href="pos.php" class="btn btn-print" style="background:#27ae60;">🛒 New Sale</a>
</div>
</body></html>
