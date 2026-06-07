<?php
require_once '../includes/auth.php';
require_manager();
require_once '../db.php';
$page_title='📥 Stock In';
$s=get_settings($conn);
$errors=[];$success='';

if($_SERVER['REQUEST_METHOD']=='POST'){
    $pid     = intval($_POST['product_id']??0);
    $qty     = intval($_POST['quantity']??0);
    $sup_id  = intval($_POST['supplier_id']??0) ?: 'NULL';
    $reason  = mysqli_real_escape_string($conn,trim($_POST['reason']??'Stock received'));

    if(!$pid)   $errors[]="Select a product.";
    if($qty<=0) $errors[]="Quantity must be greater than 0.";

    if(empty($errors)){
        mysqli_query($conn,"INSERT INTO stock_transactions (product_id,type,quantity,reason,supplier_id,user_id) VALUES ($pid,'in',$qty,'$reason',$sup_id,{$_SESSION['user_id']})");
        mysqli_query($conn,"UPDATE products SET quantity=quantity+$qty WHERE id=$pid");
        $pname=mysqli_fetch_assoc(mysqli_query($conn,"SELECT name FROM products WHERE id=$pid"))['name']??'Product';
        log_activity($conn,"Stock In","$pname: +$qty units");
        $success="✅ Stock added successfully. ($qty units added)";
    }
}

$products  = mysqli_query($conn,"SELECT * FROM products ORDER BY name");
$suppliers = mysqli_query($conn,"SELECT * FROM suppliers ORDER BY name");
$history   = mysqli_query($conn,"SELECT st.*,p.name pname,sp.name sname FROM stock_transactions st JOIN products p ON st.product_id=p.id LEFT JOIN suppliers sp ON st.supplier_id=sp.id WHERE st.type='in' ORDER BY st.created_at DESC LIMIT 15");
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
    <div class="card-header"><h3>📥 Record Stock In</h3></div>
    <div class="card-body">
      <form method="POST">
        <div class="form-group"><label>Product *</label>
          <select name="product_id" required>
            <option value="">— Select Product —</option>
            <?php while($p=mysqli_fetch_assoc($products)):?>
            <option value="<?=$p['id']?>"><?=htmlspecialchars($p['name'])?> (Current: <?=$p['quantity']?>)</option>
            <?php endwhile;?>
          </select>
        </div>
        <div class="form-row">
          <div class="form-group"><label>Quantity *</label><input type="number" name="quantity" min="1" value="1" required></div>
          <div class="form-group"><label>Supplier</label>
            <select name="supplier_id">
              <option value="">— Select Supplier —</option>
              <?php mysqli_data_seek($suppliers,0); while($sp=mysqli_fetch_assoc($suppliers)):?>
              <option value="<?=$sp['id']?>"><?=htmlspecialchars($sp['name'])?></option>
              <?php endwhile;?>
            </select>
          </div>
        </div>
        <div class="form-group"><label>Reason / Notes</label><input type="text" name="reason" value="Stock received" placeholder="e.g. Purchase from supplier"></div>
        <div class="form-actions"><button type="submit" class="btn btn-success">📥 Add Stock</button><a href="dashboard.php" class="btn btn-outline">Cancel</a></div>
      </form>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><h3>📜 Recent Stock In</h3><a href="stock_history.php" class="btn btn-sm btn-outline">Full History</a></div>
    <div class="table-responsive"><table>
      <thead><tr><th>Product</th><th>Qty</th><th>Supplier</th><th>Reason</th><th>Date</th></tr></thead>
      <tbody>
      <?php if(mysqli_num_rows($history)==0):?>
      <tr><td colspan="5"><div class="empty-state"><div class="ei">📭</div><p>No stock-in records yet</p></div></td></tr>
      <?php else: while($r=mysqli_fetch_assoc($history)):?>
      <tr>
        <td><strong><?=htmlspecialchars($r['pname'])?></strong></td>
        <td><span style="color:#27ae60;font-weight:700;">+<?=$r['quantity']?></span></td>
        <td><?=htmlspecialchars($r['sname']??'—')?></td>
        <td style="font-size:12px;color:#888"><?=htmlspecialchars($r['reason']??'—')?></td>
        <td style="font-size:12px;color:#aaa"><?=date('d M, H:i',strtotime($r['created_at']))?></td>
      </tr>
      <?php endwhile; endif;?>
      </tbody>
    </table></div>
  </div>
</div>
</div></div>
<script>setTimeout(()=>{const f=document.getElementById('fm');if(f)f.remove();},4000);</script>
</body></html>
