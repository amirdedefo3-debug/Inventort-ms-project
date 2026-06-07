<?php
require_once '../includes/auth.php';
require_manager();
require_once '../db.php';
$page_title='📦 Inventory Manager Dashboard';
$s=get_settings($conn);

$total_products=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM products"))['c'];
$available=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM products WHERE quantity>reorder_level"))['c'];
$low=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM products WHERE quantity>0 AND quantity<=reorder_level"))['c'];
$out=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM products WHERE quantity=0"))['c'];
$suppliers=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM suppliers"))['c'];
$pending_po=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM purchase_orders WHERE status='pending'"))['c'];

$low_items=mysqli_query($conn,"SELECT p.*,c.name cat FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.quantity<=p.reorder_level ORDER BY p.quantity ASC LIMIT 8");
$recent_stock=mysqli_query($conn,"SELECT st.*,p.name pname FROM stock_transactions st JOIN products p ON st.product_id=p.id ORDER BY st.created_at DESC LIMIT 8");
?>
<!DOCTYPE html><html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?=$page_title?> | <?=$s['shop_name']?></title>
<link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
<?php include '../includes/manager_sidebar.php'; ?>
<div class="main-area">
<?php include '../includes/topbar.php'; ?>
<div class="content">
  <div class="stats-grid">
    <div class="stat-card blue"><div class="stat-icon si-blue">📦</div><div class="stat-info"><h3><?=$total_products?></h3><p>Total Products</p></div></div>
    <div class="stat-card green"><div class="stat-icon si-green">✅</div><div class="stat-info"><h3><?=$available?></h3><p>Available Stock</p></div></div>
    <div class="stat-card orange"><div class="stat-icon si-orange">⚠️</div><div class="stat-info"><h3><?=$low?></h3><p>Low Stock</p></div></div>
    <div class="stat-card red"><div class="stat-icon si-red">❌</div><div class="stat-info"><h3><?=$out?></h3><p>Out of Stock</p></div></div>
    <div class="stat-card purple"><div class="stat-icon si-purple">🚚</div><div class="stat-info"><h3><?=$suppliers?></h3><p>Suppliers</p></div></div>
    <div class="stat-card teal"><div class="stat-icon si-teal">🧾</div><div class="stat-info"><h3><?=$pending_po?></h3><p>Pending Orders</p></div></div>
  </div>
  <div class="grid-2">
    <div class="card">
      <div class="card-header"><h3>⚠️ Low / Out of Stock</h3><a href="stock_in.php" class="btn btn-sm btn-primary">📥 Stock In</a></div>
      <div class="table-responsive"><table>
        <thead><tr><th>Product</th><th>Category</th><th>Qty</th><th>Reorder</th><th>Status</th></tr></thead>
        <tbody>
        <?php if(mysqli_num_rows($low_items)==0):?>
        <tr><td colspan="5"><div class="empty-state"><div class="ei">✅</div><p>All stock OK!</p></div></td></tr>
        <?php else: while($r=mysqli_fetch_assoc($low_items)):
          $st=$r['quantity']==0?['Out of Stock','b-danger']:['Low Stock','b-warning'];
        ?>
        <tr>
          <td><strong><?=htmlspecialchars($r['name'])?></strong></td>
          <td><?=$r['cat']?'<span class="badge b-info">'.htmlspecialchars($r['cat']).'</span>':'—'?></td>
          <td><strong style="color:<?=$r['quantity']==0?'#e74c3c':'#e67e22'?>"><?=$r['quantity']?></strong></td>
          <td><?=$r['reorder_level']?></td>
          <td><span class="badge <?=$st[1]?>"><?=$st[0]?></span></td>
        </tr>
        <?php endwhile; endif; ?>
        </tbody>
      </table></div>
    </div>
    <div class="card">
      <div class="card-header"><h3>📜 Recent Stock Transactions</h3><a href="stock_history.php" class="btn btn-sm btn-outline">View All</a></div>
      <div class="table-responsive"><table>
        <thead><tr><th>Product</th><th>Type</th><th>Qty</th><th>Date</th></tr></thead>
        <tbody>
        <?php if(mysqli_num_rows($recent_stock)==0):?>
        <tr><td colspan="4"><div class="empty-state"><div class="ei">📭</div><p>No transactions yet</p></div></td></tr>
        <?php else: while($r=mysqli_fetch_assoc($recent_stock)):
          $tc=['in'=>['📥 Stock In','b-success'],'out'=>['📤 Stock Out','b-danger'],'adjustment'=>['🔧 Adjust','b-warning']];
          $t=$tc[$r['type']]??[$r['type'],'b-gray'];
        ?>
        <tr>
          <td><strong><?=htmlspecialchars($r['pname'])?></strong></td>
          <td><span class="badge <?=$t[1]?>"><?=$t[0]?></span></td>
          <td><?=$r['quantity']?></td>
          <td style="color:#aaa;font-size:12px"><?=date('d M, H:i',strtotime($r['created_at']))?></td>
        </tr>
        <?php endwhile; endif; ?>
        </tbody>
      </table></div>
    </div>
  </div>
</div>
</div>
</body></html>
