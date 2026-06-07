<?php
require_once '../includes/auth.php';
require_manager();
require_once '../db.php';
$page_title='🧾 Purchase Orders';
$s=get_settings($conn);
$errors=[];$success='';

// Create PO
if(isset($_POST['create_po'])){
    $sup_id=intval($_POST['supplier_id']??0);$notes=mysqli_real_escape_string($conn,$_POST['notes']??'');
    $products_sel=$_POST['po_products']??[];$quantities=$_POST['po_quantities']??[];$costs=$_POST['po_costs']??[];
    if(!$sup_id)$errors[]="Select a supplier.";
    elseif(empty($products_sel))$errors[]="Add at least one product.";
    else{
        $po_num='PO-'.date('Ymd').'-'.str_pad(rand(1,999),3,'0',STR_PAD_LEFT);
        $uid=$_SESSION['user_id'];
        mysqli_query($conn,"INSERT INTO purchase_orders (po_number,supplier_id,user_id,status,notes) VALUES ('$po_num',$sup_id,$uid,'pending','$notes')");
        $po_id=mysqli_insert_id($conn);
        $total_cost=0;
        foreach($products_sel as $k=>$pid){
            $pid=intval($pid);$qty=intval($quantities[$k]??1);$cost=floatval($costs[$k]??0);
            if($pid&&$qty>0){mysqli_query($conn,"INSERT INTO purchase_order_items (po_id,product_id,quantity,unit_cost) VALUES ($po_id,$pid,$qty,$cost)");$total_cost+=$qty*$cost;}
        }
        mysqli_query($conn,"UPDATE purchase_orders SET total_cost=$total_cost WHERE id=$po_id");
        log_activity($conn,"Created Purchase Order",$po_num);
        $success="✅ Purchase Order $po_num created.";
    }
}
// Approve PO
if(isset($_GET['approve'])){
    $pid=intval($_GET['approve']);mysqli_query($conn,"UPDATE purchase_orders SET status='approved' WHERE id=$pid");
    log_activity($conn,"Approved PO","ID: $pid");$success="✅ PO approved.";
}
// Receive PO (updates stock)
if(isset($_GET['receive'])){
    $po_id=intval($_GET['receive']);
    $po=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM purchase_orders WHERE id=$po_id"));
    if($po&&$po['status']=='approved'){
        $items=mysqli_query($conn,"SELECT * FROM purchase_order_items WHERE po_id=$po_id");
        while($it=mysqli_fetch_assoc($items)){
            mysqli_query($conn,"UPDATE products SET quantity=quantity+{$it['quantity']} WHERE id={$it['product_id']}");
            mysqli_query($conn,"INSERT INTO stock_transactions (product_id,type,quantity,reason,user_id) VALUES ({$it['product_id']},'in',{$it['quantity']},'Purchase Order: {$po['po_number']}',{$_SESSION['user_id']})");
        }
        mysqli_query($conn,"UPDATE purchase_orders SET status='received' WHERE id=$po_id");
        log_activity($conn,"Received PO","ID: $po_id");$success="✅ Stock updated from PO.";
    }
}

$pos=mysqli_query($conn,"SELECT po.*,s.name sname FROM purchase_orders po LEFT JOIN suppliers s ON po.supplier_id=s.id ORDER BY po.created_at DESC");
$suppliers=mysqli_query($conn,"SELECT * FROM suppliers ORDER BY name");
$products=mysqli_query($conn,"SELECT id,name,purchase_price FROM products ORDER BY name");
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

<div style="margin-bottom:20px"><button class="btn btn-primary" onclick="document.getElementById('createPO').classList.add('active')">➕ Create Purchase Order</button></div>

<div class="card">
  <div class="card-header"><h3>🧾 Purchase Orders (<?=mysqli_num_rows($pos)?>)</h3></div>
  <div class="table-responsive"><table>
    <thead><tr><th>PO Number</th><th>Supplier</th><th>Total Cost</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
    <tbody>
    <?php if(!$pos||mysqli_num_rows($pos)==0):?>
    <tr><td colspan="6"><div class="empty-state"><div class="ei">🧾</div><p>No purchase orders yet</p></div></td></tr>
    <?php else:
    $statuses=['pending'=>['⏳ Pending','b-warning'],'approved'=>['✅ Approved','b-success'],'received'=>['📦 Received','b-info'],'cancelled'=>['❌ Cancelled','b-danger']];
    while($r=mysqli_fetch_assoc($pos)):$st=$statuses[$r['status']]??[$r['status'],'b-gray'];?>
    <tr>
      <td><strong><?=htmlspecialchars($r['po_number'])?></strong></td>
      <td><?=htmlspecialchars($r['sname']??'—')?></td>
      <td><?=$s['currency_symbol']?><?=number_format($r['total_cost'],2)?></td>
      <td><span class="badge <?=$st[1]?>"><?=$st[0]?></span></td>
      <td style="font-size:12px;color:#aaa"><?=date('d M Y',strtotime($r['created_at']))?></td>
      <td>
        <div style="display:flex;gap:4px">
          <?php if($r['status']=='pending'):?><a href="?approve=<?=$r['id']?>" class="btn btn-xs btn-success" onclick="return confirm('Approve?')">✅</a><?php endif;?>
          <?php if($r['status']=='approved'):?><a href="?receive=<?=$r['id']?>" class="btn btn-xs btn-primary" onclick="return confirm('Mark received & update stock?')">📦</a><?php endif;?>
        </div>
      </td>
    </tr>
    <?php endwhile; endif;?>
    </tbody>
  </table></div>
</div>
</div></div>

<!-- Create PO Modal -->
<div class="modal-overlay" id="createPO">
  <div class="modal modal-lg">
    <h3>➕ Create Purchase Order</h3>
    <form method="POST">
      <input type="hidden" name="create_po" value="1">
      <div class="form-row">
        <div class="form-group"><label>Supplier *</label>
          <select name="supplier_id" required><option value="">— Select Supplier —</option>
          <?php mysqli_data_seek($suppliers,0);while($sp=mysqli_fetch_assoc($suppliers)):?><option value="<?=$sp['id']?>"><?=htmlspecialchars($sp['name'])?></option><?php endwhile;?>
          </select>
        </div>
        <div class="form-group"><label>Notes</label><input type="text" name="notes" placeholder="Optional notes"></div>
      </div>
      <div style="margin-bottom:10px;font-size:13px;font-weight:700;color:#555;text-transform:uppercase">Products</div>
      <div id="poItems">
        <div class="po-item" style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:8px;margin-bottom:8px;align-items:center">
          <select name="po_products[]" required><option value="">— Product —</option><?php mysqli_data_seek($products,0);while($p=mysqli_fetch_assoc($products)):?><option value="<?=$p['id']?>" data-cost="<?=$p['purchase_price']?>"><?=htmlspecialchars($p['name'])?></option><?php endwhile;?></select>
          <input type="number" name="po_quantities[]" placeholder="Qty" min="1" value="1" required>
          <input type="number" name="po_costs[]" placeholder="Unit Cost" min="0" step="0.01" value="0">
          <button type="button" onclick="this.closest('.po-item').remove()" class="btn btn-xs btn-danger">✕</button>
        </div>
      </div>
      <button type="button" class="btn btn-sm btn-outline" onclick="addPOItem()" style="margin-bottom:16px">➕ Add Product Row</button>
      <div class="modal-actions">
        <button type="button" onclick="document.getElementById('createPO').classList.remove('active')" class="btn btn-outline">Cancel</button>
        <button type="submit" class="btn btn-primary">🧾 Create PO</button>
      </div>
    </form>
  </div>
</div>
<script>
const prodOptionsHTML = document.querySelector('#poItems select').innerHTML;
function addPOItem(){
  const d=document.createElement('div');d.className='po-item';d.style.cssText='display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:8px;margin-bottom:8px;align-items:center';
  d.innerHTML=`<select name="po_products[]" required>${prodOptionsHTML}</select><input type="number" name="po_quantities[]" placeholder="Qty" min="1" value="1" required><input type="number" name="po_costs[]" placeholder="Unit Cost" min="0" step="0.01" value="0"><button type="button" onclick="this.closest('.po-item').remove()" class="btn btn-xs btn-danger">✕</button>`;
  document.getElementById('poItems').appendChild(d);
}
document.getElementById('createPO').addEventListener('click',e=>{if(e.target===e.currentTarget)e.currentTarget.classList.remove('active')});
setTimeout(()=>{const f=document.getElementById('fm');if(f)f.remove();},4000);
</script>
</body></html>
