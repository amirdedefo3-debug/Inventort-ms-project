<?php
require_once '../includes/auth.php';
require_admin();
require_once '../db.php';
$page_title='📉 Stock Monitor';
$s=get_settings($conn);

$filter=isset($_GET['filter'])?$_GET['filter']:'all';
$where="WHERE 1=1";
if($filter=='low')    $where="WHERE p.quantity>0 AND p.quantity<=p.reorder_level";
elseif($filter=='out')$where="WHERE p.quantity=0";
elseif($filter=='ok') $where="WHERE p.quantity>p.reorder_level";

$products=mysqli_query($conn,"SELECT p.*,c.name cat,sp.name sup FROM products p LEFT JOIN categories c ON p.category_id=c.id LEFT JOIN suppliers sp ON p.supplier_id=sp.id $where ORDER BY p.quantity ASC");
$total_ok =mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM products WHERE quantity>reorder_level"))['c'];
$total_low =mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM products WHERE quantity>0 AND quantity<=reorder_level"))['c'];
$total_out =mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM products WHERE quantity=0"))['c'];
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
    <div class="stat-card green"><div class="stat-icon si-green">✅</div><div class="stat-info"><h3><?=$total_ok?></h3><p>In Stock</p></div></div>
    <div class="stat-card orange"><div class="stat-icon si-orange">⚠️</div><div class="stat-info"><h3><?=$total_low?></h3><p>Low Stock</p></div></div>
    <div class="stat-card red"><div class="stat-icon si-red">❌</div><div class="stat-info"><h3><?=$total_out?></h3><p>Out of Stock</p></div></div>
  </div>
<div class="card">
  <div class="card-header">
    <h3>📉 Stock Levels</h3>
    <div style="display:flex;gap:6px;flex-wrap:wrap">
      <a href="?filter=all" class="btn btn-sm <?=$filter=='all'?'btn-primary':'btn-outline'?>">All</a>
      <a href="?filter=ok"  class="btn btn-sm <?=$filter=='ok'?'btn-success':'btn-outline'?>">✅ In Stock</a>
      <a href="?filter=low" class="btn btn-sm <?=$filter=='low'?'btn-warning':'btn-outline'?>">⚠️ Low Stock</a>
      <a href="?filter=out" class="btn btn-sm <?=$filter=='out'?'btn-danger':'btn-outline'?>">❌ Out of Stock</a>
    </div>
  </div>
  <div class="table-responsive"><table>
    <thead><tr><th>Product</th><th>Category</th><th>Supplier</th><th>Qty</th><th>Reorder Level</th><th>Stock Level</th><th>Status</th><th>Action</th></tr></thead>
    <tbody>
    <?php if(!$products||mysqli_num_rows($products)==0):?>
    <tr><td colspan="8"><div class="empty-state"><div class="ei">✅</div><h3>All good!</h3><p>No items match this filter.</p></div></td></tr>
    <?php else: while($r=mysqli_fetch_assoc($products)):
      $qty=$r['quantity'];$rl=$r['reorder_level'];
      $pct=min(100,round($qty/max(1,$rl*2)*100));
      $cls=$qty==0?'sb-red':($qty<=$rl?'sb-orange':'sb-green');
      $st=$qty==0?['❌ Out of Stock','b-danger']:($qty<=$rl?['⚠️ Low Stock','b-warning']:['✅ In Stock','b-success']);
    ?>
    <tr>
      <td><strong><?=htmlspecialchars($r['name'])?></strong></td>
      <td><?=$r['cat']?'<span class="badge b-info">'.htmlspecialchars($r['cat']).'</span>':'—'?></td>
      <td style="font-size:12px"><?=htmlspecialchars($r['sup']??'—')?></td>
      <td><strong style="font-size:16px;color:<?=$qty==0?'#e74c3c':($qty<=$rl?'#e67e22':'#27ae60')?>"><?=$qty?></strong></td>
      <td><?=$rl?></td>
      <td>
        <div style="display:flex;align-items:center;gap:8px">
          <div class="stock-bar-wrap" style="width:120px"><div class="stock-bar <?=$cls?>" style="width:<?=$pct?>%"></div></div>
          <span style="font-size:11px;color:#aaa"><?=$pct?>%</span>
        </div>
      </td>
      <td><span class="badge <?=$st[1]?>"><?=$st[0]?></span></td>
      <td><a href="edit_product.php?id=<?=$r['id']?>" class="btn btn-xs btn-warning">✏️ Edit</a></td>
    </tr>
    <?php endwhile; endif;?>
    </tbody>
  </table></div>
</div>
</div></div>
</body></html>
