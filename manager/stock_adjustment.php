<?php
require_once '../includes/auth.php';
require_manager();
require_once '../db.php';
$page_title='🔧 Stock Adjustment';
$s=get_settings($conn);
$errors=[];$success='';

if($_SERVER['REQUEST_METHOD']=='POST'){
    $pid=intval($_POST['product_id']??0);$qty=intval($_POST['quantity']??0);
    $atype=$_POST['adjust_type']??'increase';$reason=mysqli_real_escape_string($conn,$_POST['reason']??'');
    if(!$pid)$errors[]="Select a product.";
    if($qty<=0)$errors[]="Quantity must be > 0.";
    if(!$reason)$errors[]="Reason required.";
    if(empty($errors)){
        $cur=mysqli_fetch_assoc(mysqli_query($conn,"SELECT quantity,name FROM products WHERE id=$pid"));
        if($atype=='decrease'&&$cur['quantity']<$qty){$errors[]="Cannot decrease by more than current stock (".$cur['quantity'].").";}
        else{
            $new_qty=$atype=='increase'?$cur['quantity']+$qty:$cur['quantity']-$qty;
            mysqli_query($conn,"INSERT INTO stock_transactions (product_id,type,quantity,reason,user_id) VALUES ($pid,'adjustment',$qty,'[$atype] $reason',{$_SESSION['user_id']})");
            mysqli_query($conn,"UPDATE products SET quantity=$new_qty WHERE id=$pid");
            log_activity($conn,"Stock Adjustment","{$cur['name']}: $atype by $qty ($reason)");
            $success="✅ Stock adjusted. {$cur['name']} is now $new_qty units.";
        }
    }
}
$products=mysqli_query($conn,"SELECT * FROM products ORDER BY name");
$reasons=["Damaged","Lost","Returned","Correction","Found","Recounted"];
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
<div style="max-width:600px">
<div class="card">
  <div class="card-header"><h3>🔧 Adjust Stock</h3></div>
  <div class="card-body">
    <form method="POST">
      <div class="form-group"><label>Product *</label>
        <select name="product_id" required onchange="showCurrent(this)">
          <option value="">— Select Product —</option>
          <?php while($p=mysqli_fetch_assoc($products)):?>
          <option value="<?=$p['id']?>" data-qty="<?=$p['quantity']?>"><?=htmlspecialchars($p['name'])?> (Current: <?=$p['quantity']?>)</option>
          <?php endwhile;?>
        </select>
      </div>
      <div id="currentInfo" style="display:none;background:#e8f0fe;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:13px;color:#3498db">
        📦 Current stock: <strong id="curQty">0</strong> units
      </div>
      <div class="form-row">
        <div class="form-group"><label>Adjustment Type *</label>
          <select name="adjust_type" id="adjType" onchange="updateLabel()">
            <option value="increase">➕ Increase Stock</option>
            <option value="decrease">➖ Decrease Stock</option>
          </select>
        </div>
        <div class="form-group"><label id="qtyLabel">Quantity to Add *</label><input type="number" name="quantity" min="1" value="1" required></div>
      </div>
      <div class="form-group"><label>Reason *</label>
        <select name="reason" required>
          <option value="">— Select Reason —</option>
          <?php foreach($reasons as $r):?><option value="<?=$r?>"><?=$r?></option><?php endforeach;?>
        </select>
      </div>
      <div class="form-actions"><button type="submit" class="btn btn-warning">🔧 Apply Adjustment</button><a href="dashboard.php" class="btn btn-outline">Cancel</a></div>
    </form>
  </div>
</div>
</div>
</div></div>
<script>
function showCurrent(sel){
  const opt=sel.options[sel.selectedIndex];
  if(opt.dataset.qty!==undefined){document.getElementById('currentInfo').style.display='block';document.getElementById('curQty').textContent=opt.dataset.qty;}
  else document.getElementById('currentInfo').style.display='none';
}
function updateLabel(){
  const t=document.getElementById('adjType').value;
  document.getElementById('qtyLabel').textContent=t==='increase'?'Quantity to Add *':'Quantity to Remove *';
}
setTimeout(()=>{const f=document.getElementById('fm');if(f)f.remove();},4000);
</script>
</body></html>
