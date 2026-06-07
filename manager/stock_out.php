<?php
require_once '../includes/auth.php';
require_manager();
require_once '../db.php';
$page_title='📤 Stock Out';
$s=get_settings($conn);
$errors=[];$success='';

if($_SERVER['REQUEST_METHOD']=='POST'){
    $pid    = intval($_POST['product_id']??0);
    $qty    = intval($_POST['quantity']??0);
    $reason = mysqli_real_escape_string($conn,trim($_POST['reason']??''));

    if(!$pid)   $errors[]="Select a product.";
    if($qty<=0) $errors[]="Quantity must be greater than 0.";
    if(empty($reason)) $errors[]="Reason is required.";

    if(empty($errors)){
        $cur=mysqli_fetch_assoc(mysqli_query($conn,"SELECT quantity,name FROM products WHERE id=$pid"));
        if($cur['quantity']<$qty){
            $errors[]="Insufficient stock. Available: ".$cur['quantity'];
        } else {
            mysqli_query($conn,"INSERT INTO stock_transactions (product_id,type,quantity,reason,user_id) VALUES ($pid,'out',$qty,'$reason',{$_SESSION['user_id']})");
            mysqli_query($conn,"UPDATE products SET quantity=quantity-$qty WHERE id=$pid");
            log_activity($conn,"Stock Out","{$cur['name']}: -$qty units ($reason)");
            $success="✅ Stock removed. ($qty units removed from {$cur['name']})";
        }
    }
}

$products = mysqli_query($conn,"SELECT * FROM products WHERE quantity>0 ORDER BY name");
$history  = mysqli_query($conn,"SELECT st.*,p.name pname FROM stock_transactions st JOIN products p ON st.product_id=p.id WHERE st.type='out' ORDER BY st.created_at DESC LIMIT 15");
$reasons  = ["Damaged","Lost","Returned","Correction","Expired","Internal Use"];
?>
<!DOCTYPE html><html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?=$page_title?> | <?=$s['shop_name']?></title><link rel="stylesheet" href="../css/dashboard.css"></head>
<body>
<?php include '../includes/manager_sidebar.php'; ?>
<div class="main-area">
<?php include '../includes/topbar.php'; ?>
<div class="content">
<?php if($success):?><div class="alert alert-success" id="fm"><?=$success?><button class="close-btn" onclick="this.parentElement.remove()">✕</button></div><?php endif;?>
<?php if(!empty($errors)):?><div class="alert alert-danger"><?=implode('<br>',$errors)?></div><?php endif;?>
<div class="grid-2">
  <div class="card">
    <div class="card-header"><h3>📤 Record Stock Out</h3></div>
    <div class="card-body">
      <form method="POST">
        <div class="form-group"><label>Product *</label>
          <select name="product_id" required id="prodSel" onchange="updateStock(this)">
            <option value="">— Select Product —</option>
            <?php while($p=mysqli_fetch_assoc($products)):?>
            <option value="<?=$p['id']?>" data-stock="<?=$p['quantity']?>"><?=htmlspecialchars($p['name'])?> (Stock: <?=$p['quantity']?>)</option>
            <?php endwhile;?>
          </select>
        </div>
        <div id="stockInfo" style="display:none;background:#fff3e0;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:13px;color:#e67e22;">
          ⚠️ Available stock: <strong id="availStock">0</strong> units
        </div>
        <div class="form-row">
          <div class="form-group"><label>Quantity *</label><input type="number" name="quantity" min="1" value="1" required id="qtyInp"></div>
          <div class="form-group"><label>Reason *</label>
            <select name="reason" required>
              <option value="">— Select Reason —</option>
              <?php foreach($reasons as $r):?><option value="<?=$r?>"><?=$r?></option><?php endforeach;?>
            </select>
          </div>
        </div>
        <div class="form-actions"><button type="submit" class="btn btn-danger">📤 Remove Stock</button><a href="dashboard.php" class="btn btn-outline">Cancel</a></div>
      </form>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><h3>📜 Recent Stock Out</h3></div>
    <div class="table-responsive"><table>
      <thead><tr><th>Product</th><th>Qty</th><th>Reason</th><th>Date</th></tr></thead>
      <tbody>
      <?php if(mysqli_num_rows($history)==0):?>
      <tr><td colspan="4"><div class="empty-state"><div class="ei">📭</div><p>No records yet</p></div></td></tr>
      <?php else: while($r=mysqli_fetch_assoc($history)):?>
      <tr>
        <td><strong><?=htmlspecialchars($r['pname'])?></strong></td>
        <td><span style="color:#e74c3c;font-weight:700;">-<?=$r['quantity']?></span></td>
        <td><span class="badge b-warning"><?=htmlspecialchars($r['reason']??'—')?></span></td>
        <td style="font-size:12px;color:#aaa"><?=date('d M, H:i',strtotime($r['created_at']))?></td>
      </tr>
      <?php endwhile; endif;?>
      </tbody>
    </table></div>
  </div>
</div>
</div></div>
<script>
function updateStock(sel){
  const opt = sel.options[sel.selectedIndex];
  const stock = opt.dataset.stock;
  if(stock!==undefined){
    document.getElementById('stockInfo').style.display='block';
    document.getElementById('availStock').textContent=stock;
    document.getElementById('qtyInp').max=stock;
  } else {
    document.getElementById('stockInfo').style.display='none';
  }
}
setTimeout(()=>{const f=document.getElementById('fm');if(f)f.remove();},4000);
</script>
</body></html>
