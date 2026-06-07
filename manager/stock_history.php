<?php
require_once '../includes/auth.php';
require_manager();
require_once '../db.php';
$page_title='📜 Stock History';
$s=get_settings($conn);

$type_f = isset($_GET['type'])?$_GET['type']:'';
$prod_f = isset($_GET['product'])?intval($_GET['product']):0;
$date_f = isset($_GET['date'])?$_GET['date']:'';
$where="WHERE 1=1";
if($type_f && in_array($type_f,['in','out','adjustment']))$where.=" AND st.type='$type_f'";
if($prod_f)$where.=" AND st.product_id=$prod_f";
if($date_f)$where.=" AND DATE(st.created_at)='".mysqli_real_escape_string($conn,$date_f)."'";

$per=15;$page=max(1,intval($_GET['page']??1));$offset=($page-1)*$per;
$total=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM stock_transactions st $where"))['c'];
$total_pages=ceil($total/$per);
$history=mysqli_query($conn,"SELECT st.*,p.name pname,sp.name sname,u.full_name uname FROM stock_transactions st JOIN products p ON st.product_id=p.id LEFT JOIN suppliers sp ON st.supplier_id=sp.id LEFT JOIN users u ON st.user_id=u.id $where ORDER BY st.created_at DESC LIMIT $per OFFSET $offset");
$products=mysqli_query($conn,"SELECT id,name FROM products ORDER BY name");
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
    <h3>Stock Transaction History (<?=$total?>)</h3>
    <form method="GET" class="search-bar">
      <select name="type">
        <option value="">All Types</option>
        <option value="in" <?=$type_f=='in'?'selected':''?>>📥 Stock In</option>
        <option value="out" <?=$type_f=='out'?'selected':''?>>📤 Stock Out</option>
        <option value="adjustment" <?=$type_f=='adjustment'?'selected':''?>>🔧 Adjustment</option>
      </select>
      <select name="product">
        <option value="">All Products</option>
        <?php while($p=mysqli_fetch_assoc($products)):?><option value="<?=$p['id']?>" <?=$prod_f==$p['id']?'selected':''?>><?=htmlspecialchars($p['name'])?></option><?php endwhile;?>
      </select>
      <input type="date" name="date" value="<?=htmlspecialchars($date_f)?>">
      <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
      <?php if($type_f||$prod_f||$date_f):?><a href="stock_history.php" class="btn btn-outline btn-sm">Clear</a><?php endif;?>
    </form>
  </div>
  <div class="table-responsive"><table>
    <thead><tr><th>#</th><th>Product</th><th>Type</th><th>Qty</th><th>Reason</th><th>Supplier</th><th>By</th><th>Date</th></tr></thead>
    <tbody>
    <?php if(!$history||mysqli_num_rows($history)==0):?>
    <tr><td colspan="8"><div class="empty-state"><div class="ei">📭</div><h3>No records found</h3></div></td></tr>
    <?php else: $i=$offset+1; while($r=mysqli_fetch_assoc($history)):
      $tc=['in'=>['📥 Stock In','b-success'],  'out'=>['📤 Stock Out','b-danger'],'adjustment'=>['🔧 Adjust','b-warning']];
      $t=$tc[$r['type']]??[$r['type'],'b-gray'];
    ?>
    <tr>
      <td style="color:#aaa"><?=$i++?></td>
      <td><strong><?=htmlspecialchars($r['pname'])?></strong></td>
      <td><span class="badge <?=$t[1]?>"><?=$t[0]?></span></td>
      <td><strong style="color:<?=$r['type']=='in'?'#27ae60':($r['type']=='out'?'#e74c3c':'#e67e22')?>"><?=$r['type']=='in'?'+':($r['type']=='out'?'-':'±')?><?=$r['quantity']?></strong></td>
      <td style="font-size:12px"><?=htmlspecialchars($r['reason']??'—')?></td>
      <td style="font-size:12px"><?=htmlspecialchars($r['sname']??'—')?></td>
      <td style="font-size:12px"><?=htmlspecialchars($r['uname']??'—')?></td>
      <td style="font-size:12px;color:#aaa"><?=date('d M Y, H:i',strtotime($r['created_at']))?></td>
    </tr>
    <?php endwhile; endif;?>
    </tbody>
  </table></div>
  <?php if($total_pages>1):?>
  <div class="pagination">
    <?php for($p=1;$p<=$total_pages;$p++):?>
      <?=$p==$page?"<span class='cur'>$p</span>":"<a href='?type=$type_f&product=$prod_f&date=$date_f&page=$p'>$p</a>"?>
    <?php endfor;?>
  </div>
  <?php endif;?>
</div>
</div></div>
</body></html>
