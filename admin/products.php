<?php
require_once '../includes/auth.php';
require_admin();
require_once '../db.php';
$page_title = '📦 Products';
$s = get_settings($conn);

$msg=''; $msg_type='';
if(isset($_GET['msg'])){
    $msgs=['added'=>['✅ Product added.','success'],'updated'=>['✅ Product updated.','success'],'deleted'=>['🗑️ Product deleted.','danger']];
    if(isset($msgs[$_GET['msg']])){$msg=$msgs[$_GET['msg']][0];$msg_type=$msgs[$_GET['msg']][1];}
}

$search = isset($_GET['q'])?mysqli_real_escape_string($conn,trim($_GET['q'])):'';
$cat_f  = isset($_GET['cat'])?intval($_GET['cat']):0;
$sup_f  = isset($_GET['sup'])?intval($_GET['sup']):0;
$sort   = in_array($_GET['sort']??'','name,quantity,selling_price,created_at')?$_GET['sort']:'created_at';
// Actually check properly:
$allowed=['name','quantity','selling_price','created_at'];
if(!in_array($_GET['sort']??'',$allowed))$sort='created_at';
$order = strtoupper($_GET['order']??'')==='ASC'?'ASC':'DESC';

$where = "WHERE 1=1";
if($search) $where.=" AND (p.name LIKE '%$search%' OR p.barcode LIKE '%$search%')";
if($cat_f)  $where.=" AND p.category_id=$cat_f";
if($sup_f)  $where.=" AND p.supplier_id=$sup_f";

$per=12; $page=max(1,intval($_GET['page']??1)); $offset=($page-1)*$per;
$total=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM products p $where"))['c'];
$total_pages=ceil($total/$per);

$products=mysqli_query($conn,"SELECT p.*,c.name cat,sp.name sup FROM products p LEFT JOIN categories c ON p.category_id=c.id LEFT JOIN suppliers sp ON p.supplier_id=sp.id $where ORDER BY p.$sort $order LIMIT $per OFFSET $offset");
$categories=mysqli_query($conn,"SELECT * FROM categories ORDER BY name");
$suppliers=mysqli_query($conn,"SELECT * FROM suppliers ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?=$page_title?> | <?=$s['shop_name']?></title>
<link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
<?php include '../includes/admin_sidebar.php'; ?>
<div class="main-area">
<?php include '../includes/topbar.php'; ?>
<div class="content">
<?php if($msg): ?><div class="alert alert-<?=$msg_type?>" id="fm"><?=$msg?><button class="close-btn" onclick="document.getElementById('fm').remove()">✕</button></div><?php endif; ?>

<div class="card">
  <div class="card-header">
    <h3>All Products (<?=$total?>)</h3>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
      <form method="GET" class="search-bar">
        <input type="text" name="q" placeholder="🔍 Search name/barcode..." value="<?=htmlspecialchars($_GET['q']??'')?>">
        <select name="cat"><option value="">All Categories</option><?php mysqli_data_seek($categories,0);while($c=mysqli_fetch_assoc($categories)):?><option value="<?=$c['id']?>" <?=$cat_f==$c['id']?'selected':''?>><?=htmlspecialchars($c['name'])?></option><?php endwhile;?></select>
        <select name="sup"><option value="">All Suppliers</option><?php mysqli_data_seek($suppliers,0);while($sp=mysqli_fetch_assoc($suppliers)):?><option value="<?=$sp['id']?>" <?=$sup_f==$sp['id']?'selected':''?>><?=htmlspecialchars($sp['name'])?></option><?php endwhile;?></select>
        <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
        <?php if($search||$cat_f||$sup_f):?><a href="products.php" class="btn btn-outline btn-sm">Clear</a><?php endif;?>
      </form>
      <a href="add_product.php" class="btn btn-primary btn-sm">➕ Add Product</a>
    </div>
  </div>
  <div class="table-responsive">
    <table>
      <thead><tr><th>#</th><th>Product</th><th>Barcode</th><th>Category</th><th>Supplier</th><th>Buy Price</th><th>Sell Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if(!$products||mysqli_num_rows($products)==0):?>
      <tr><td colspan="10"><div class="empty-state"><div class="ei">📭</div><h3>No products found</h3></div></td></tr>
      <?php else: $i=$offset+1; while($r=mysqli_fetch_assoc($products)):
        $qty=$r['quantity']; $rl=$r['reorder_level'];
        $status=$qty==0?['Out of Stock','b-danger']:($qty<=$rl?['Low Stock','b-warning']:['In Stock','b-success']);
      ?>
      <tr>
        <td style="color:#aaa"><?=$i++?></td>
        <td><strong><?=htmlspecialchars($r['name'])?></strong><?php if($r['description']):?><br><small style="color:#aaa"><?=htmlspecialchars(substr($r['description'],0,35))?>...</small><?php endif;?></td>
        <td style="font-family:monospace;font-size:12px"><?=htmlspecialchars($r['barcode']??'—')?></td>
        <td><?=$r['cat']?'<span class="badge b-info">'.htmlspecialchars($r['cat']).'</span>':'<span style="color:#ccc">—</span>'?></td>
        <td><?=htmlspecialchars($r['sup']??'—')?></td>
        <td><?=$s['currency_symbol']?><?=number_format($r['purchase_price'],2)?></td>
        <td><strong><?=$s['currency_symbol']?><?=number_format($r['selling_price'],2)?></strong></td>
        <td>
          <div style="display:flex;align-items:center;gap:6px">
            <strong style="color:<?=$qty==0?'#e74c3c':($qty<=$rl?'#e67e22':'#27ae60')?>"><?=$qty?></strong>
            <div class="stock-bar-wrap"><div class="stock-bar <?=$qty==0?'sb-red':($qty<=$rl?'sb-orange':'sb-green')?>" style="width:<?=min(100,round($qty/max(1,$rl*2)*100))?>%"></div></div>
          </div>
        </td>
        <td><span class="badge <?=$status[1]?>"><?=$status[0]?></span></td>
        <td>
          <div style="display:flex;gap:4px">
            <a href="edit_product.php?id=<?=$r['id']?>" class="btn btn-xs btn-warning">✏️</a>
            <button onclick="delP(<?=$r['id']?>,'<?=addslashes($r['name'])?>')" class="btn btn-xs btn-danger">🗑️</button>
          </div>
        </td>
      </tr>
      <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
  <?php if($total_pages>1):?>
  <div class="pagination">
    <?php for($p=1;$p<=$total_pages;$p++):?>
      <?=$p==$page?"<span class='cur'>$p</span>":"<a href='?q=".urlencode($search)."&cat=$cat_f&sup=$sup_f&page=$p'>$p</a>"?>
    <?php endfor;?>
  </div>
  <?php endif;?>
</div>
</div>
</div>

<div class="modal-overlay" id="delModal">
  <div class="modal">
    <h3>🗑️ Delete Product</h3>
    <p id="delMsg"></p>
    <div class="modal-actions">
      <button onclick="document.getElementById('delModal').classList.remove('active')" class="btn btn-outline">Cancel</button>
      <a href="#" id="delBtn" class="btn btn-danger">Delete</a>
    </div>
  </div>
</div>
<script>
function delP(id,name){
  document.getElementById('delMsg').textContent='Delete "'+name+'"? This cannot be undone.';
  document.getElementById('delBtn').href='delete_product.php?id='+id;
  document.getElementById('delModal').classList.add('active');
}
document.getElementById('delModal').addEventListener('click',e=>{if(e.target===e.currentTarget)e.currentTarget.classList.remove('active')});
setTimeout(()=>{const f=document.getElementById('fm');if(f)f.remove()},4000);
</script>
</body></html>
