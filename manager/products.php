<?php
require_once '../includes/auth.php';
require_manager();
require_once '../db.php';
$page_title='📦 Products';
$s=get_settings($conn);
$search=isset($_GET['q'])?mysqli_real_escape_string($conn,$_GET['q']):'';
$where=$search?"WHERE p.name LIKE '%$search%' OR p.barcode LIKE '%$search%'":'WHERE 1=1';
$per=15;$page=max(1,intval($_GET['page']??1));$offset=($page-1)*$per;
$total=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM products p $where"))['c'];
$total_pages=ceil($total/$per);
$products=mysqli_query($conn,"SELECT p.*,c.name cat,sp.name sup FROM products p LEFT JOIN categories c ON p.category_id=c.id LEFT JOIN suppliers sp ON p.supplier_id=sp.id $where ORDER BY p.name LIMIT $per OFFSET $offset");
?>
<!DOCTYPE html><html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?=$page_title?> | <?=$s['shop_name']?></title><link rel="stylesheet" href="../css/dashboard.css"></head>
<body>
<?php include '../includes/manager_sidebar.php'; ?>
<div class="main-area">
<?php include '../includes/topbar.php'; ?>
<div class="content">
<div class="card">
  <div class="card-header">
    <h3>Products (<?=$total?>)</h3>
    <div style="display:flex;gap:8px">
      <form method="GET" class="search-bar">
        <input type="text" name="q" placeholder="🔍 Search..." value="<?=htmlspecialchars($_GET['q']??'')?>">
        <button type="submit" class="btn btn-secondary btn-sm">Search</button>
        <?php if($search):?><a href="products.php" class="btn btn-outline btn-sm">Clear</a><?php endif;?>
      </form>
      <a href="add_product.php" class="btn btn-primary btn-sm">➕ Add</a>
    </div>
  </div>
  <div class="table-responsive"><table>
    <thead><tr><th>#</th><th>Product</th><th>Barcode</th><th>Category</th><th>Sell Price</th><th>Qty</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php if(!$products||mysqli_num_rows($products)==0):?>
    <tr><td colspan="8"><div class="empty-state"><div class="ei">📭</div><p>No products found</p></div></td></tr>
    <?php else: $i=$offset+1; while($r=mysqli_fetch_assoc($products)):
      $qty=$r['quantity'];$rl=$r['reorder_level'];
      $st=$qty==0?['Out of Stock','b-danger']:($qty<=$rl?['Low Stock','b-warning']:['In Stock','b-success']);
    ?>
    <tr>
      <td style="color:#aaa"><?=$i++?></td>
      <td><strong><?=htmlspecialchars($r['name'])?></strong></td>
      <td style="font-family:monospace;font-size:12px"><?=htmlspecialchars($r['barcode']??'—')?></td>
      <td><?=$r['cat']?'<span class="badge b-info">'.htmlspecialchars($r['cat']).'</span>':'—'?></td>
      <td><?=$s['currency_symbol']?><?=number_format($r['selling_price'],2)?></td>
      <td><strong style="color:<?=$qty==0?'#e74c3c':($qty<=$rl?'#e67e22':'#27ae60')?>"><?=$qty?></strong></td>
      <td><span class="badge <?=$st[1]?>"><?=$st[0]?></span></td>
      <td>
        <div style="display:flex;gap:4px">
          <a href="edit_product.php?id=<?=$r['id']?>" class="btn btn-xs btn-warning">✏️</a>
          <a href="stock_in.php" class="btn btn-xs btn-success">📥</a>
        </div>
      </td>
    </tr>
    <?php endwhile; endif;?>
    </tbody>
  </table></div>
  <?php if($total_pages>1):?>
  <div class="pagination"><?php for($p=1;$p<=$total_pages;$p++):?><?=$p==$page?"<span class='cur'>$p</span>":"<a href='?q=".urlencode($search)."&page=$p'>$p</a>"?><?php endfor;?></div>
  <?php endif;?>
</div>
</div></div>
</body></html>
