<?php
require_once '../includes/auth.php';
require_admin();
require_once '../db.php';
$page_title = '📊 Admin Dashboard';
$s = get_settings($conn);

$total_products   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM products"))['c'];
$total_categories = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM categories"))['c'];
$total_suppliers  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM suppliers"))['c'];
$total_users      = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM users"))['c'];
$total_sales      = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM sales"))['c'];
$total_revenue    = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(total),0) v FROM sales"))['v'];
$low_stock        = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM products WHERE quantity>0 AND quantity<=reorder_level"))['c'];
$out_of_stock     = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM products WHERE quantity=0"))['c'];

// Monthly sales data (last 6 months)
$months=[]; $revenues=[];
for($i=5;$i>=0;$i--){
    $m = date('Y-m', strtotime("-$i months"));
    $label = date('M', strtotime("-$i months"));
    $res = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(total),0) v FROM sales WHERE DATE_FORMAT(created_at,'%Y-%m')='$m'"));
    $months[] = $label; $revenues[] = $res['v'];
}

// Top 5 selling products
$top_products = mysqli_query($conn,"SELECT p.name, SUM(si.quantity) qty FROM sale_items si JOIN products p ON si.product_id=p.id GROUP BY si.product_id ORDER BY qty DESC LIMIT 5");
$tp_names=[]; $tp_qty=[];
while($r=mysqli_fetch_assoc($top_products)){$tp_names[]=$r['name'];$tp_qty[]=$r['qty'];}

// Recent sales
$recent_sales = mysqli_query($conn,"SELECT s.*,u.full_name FROM sales s LEFT JOIN users u ON s.user_id=u.id ORDER BY s.created_at DESC LIMIT 8");

// Recent logs
$logs = mysqli_query($conn,"SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 6");
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?=$page_title?> | <?=$s['shop_name']?></title>
<link rel="stylesheet" href="../css/dashboard.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <span class="logo-icon">🏪</span>
    <div class="logo-text"><h2><?=$s['shop_name']?></h2><p>Admin Panel</p></div>
  </div>
  <div class="sidebar-user">
    <div class="user-avatar"><?=strtoupper(substr($_SESSION['user_name'],0,1))?></div>
    <div class="user-info">
      <div class="uname"><?=htmlspecialchars($_SESSION['user_name'])?></div>
      <div class="urole role-admin">🔑 Administrator</div>
    </div>
  </div>
  <nav>
    <div class="nav-label">Main</div>
    <a href="dashboard.php" class="active"><span class="nav-icon">📊</span><span class="nav-text">Dashboard</span></a>
    <div class="nav-label">Inventory</div>
    <a href="products.php"><span class="nav-icon">📦</span><span class="nav-text">Products</span></a>
    <a href="categories.php"><span class="nav-icon">🗂️</span><span class="nav-text">Categories</span></a>
    <a href="suppliers.php"><span class="nav-icon">🚚</span><span class="nav-text">Suppliers</span></a>
    <a href="stock_monitor.php"><span class="nav-icon">📉</span><span class="nav-text">Stock Monitor</span></a>
    <div class="nav-label">Sales</div>
    <a href="sales.php"><span class="nav-icon">🛒</span><span class="nav-text">Sales</span></a>
    <a href="reports.php"><span class="nav-icon">📄</span><span class="nav-text">Reports</span></a>
    <div class="nav-label">Admin</div>
    <a href="users.php"><span class="nav-icon">👥</span><span class="nav-text">Users</span></a>
    <a href="activity_logs.php"><span class="nav-icon">📋</span><span class="nav-text">Activity Logs</span></a>
    <a href="settings.php"><span class="nav-icon">⚙️</span><span class="nav-text">Settings</span></a>
  </nav>
  <div class="sidebar-footer">
    <a href="../logout.php"><span class="nav-icon">🚪</span><span class="nav-text">Logout</span></a>
  </div>
</div>
<div class="main-area">
  <?php include '../includes/topbar.php'; ?>
  <div class="content">

    <!-- Stat Cards -->
    <div class="stats-grid">
      <div class="stat-card blue"><div class="stat-icon si-blue">📦</div><div class="stat-info"><h3><?=$total_products?></h3><p>Total Products</p></div></div>
      <div class="stat-card green"><div class="stat-icon si-green">🗂️</div><div class="stat-info"><h3><?=$total_categories?></h3><p>Categories</p></div></div>
      <div class="stat-card purple"><div class="stat-icon si-purple">🚚</div><div class="stat-info"><h3><?=$total_suppliers?></h3><p>Suppliers</p></div></div>
      <div class="stat-card teal"><div class="stat-icon si-teal">👥</div><div class="stat-info"><h3><?=$total_users?></h3><p>Users</p></div></div>
      <div class="stat-card green"><div class="stat-icon si-green">🛒</div><div class="stat-info"><h3><?=$total_sales?></h3><p>Total Sales</p></div></div>
      <div class="stat-card blue"><div class="stat-icon si-blue">💰</div><div class="stat-info"><h3><?=$s['currency_symbol']?><?=number_format($total_revenue,2)?></h3><p>Total Revenue</p></div></div>
      <div class="stat-card orange"><div class="stat-icon si-orange">⚠️</div><div class="stat-info"><h3><?=$low_stock?></h3><p>Low Stock</p></div></div>
      <div class="stat-card red"><div class="stat-icon si-red">❌</div><div class="stat-info"><h3><?=$out_of_stock?></h3><p>Out of Stock</p></div></div>
    </div>

    <!-- Charts Row -->
    <div class="grid-2">
      <div class="card">
        <div class="card-header"><h3>📈 Monthly Revenue (6 Months)</h3></div>
        <div class="card-body"><div class="chart-wrap"><canvas id="salesChart"></canvas></div></div>
      </div>
      <div class="card">
        <div class="card-header"><h3>🏆 Top Selling Products</h3></div>
        <div class="card-body"><div class="chart-wrap"><canvas id="topChart"></canvas></div></div>
      </div>
    </div>

    <!-- Tables Row -->
    <div class="grid-2">
      <div class="card">
        <div class="card-header"><h3>🛒 Recent Sales</h3><a href="sales.php" class="btn btn-sm btn-outline">View All</a></div>
        <div class="table-responsive">
          <table><thead><tr><th>Invoice</th><th>Cashier</th><th>Total</th><th>Date</th></tr></thead>
          <tbody>
          <?php mysqli_data_seek($recent_sales,0); while($r=mysqli_fetch_assoc($recent_sales)): ?>
          <tr>
            <td><strong><?=htmlspecialchars($r['invoice_number'])?></strong></td>
            <td><?=htmlspecialchars($r['full_name']??'—')?></td>
            <td><?=$s['currency_symbol']?><?=number_format($r['total'],2)?></td>
            <td style="color:#aaa;font-size:12px;"><?=date('d M, H:i',strtotime($r['created_at']))?></td>
          </tr>
          <?php endwhile; ?>
          </tbody></table>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><h3>📋 Recent Activity</h3><a href="activity_logs.php" class="btn btn-sm btn-outline">View All</a></div>
        <div class="table-responsive">
          <table><thead><tr><th>User</th><th>Action</th><th>Time</th></tr></thead>
          <tbody>
          <?php while($r=mysqli_fetch_assoc($logs)): ?>
          <tr>
            <td><strong><?=htmlspecialchars($r['user_name']??'—')?></strong></td>
            <td><?=htmlspecialchars($r['action'])?></td>
            <td style="color:#aaa;font-size:12px;"><?=date('d M, H:i',strtotime($r['created_at']))?></td>
          </tr>
          <?php endwhile; ?>
          </tbody></table>
        </div>
      </div>
    </div>

  </div>
</div>
<script>
const months = <?=json_encode($months)?>;
const revenues = <?=json_encode($revenues)?>;
new Chart(document.getElementById('salesChart'),{
  type:'bar',
  data:{labels:months,datasets:[{label:'Revenue',data:revenues,backgroundColor:'rgba(233,69,96,.7)',borderColor:'#e94560',borderWidth:2,borderRadius:6}]},
  options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:'rgba(0,0,0,.05)'}}}}
});
const tpNames = <?=json_encode($tp_names)?>;
const tpQty   = <?=json_encode($tp_qty)?>;
new Chart(document.getElementById('topChart'),{
  type:'doughnut',
  data:{labels:tpNames.length?tpNames:['No Data'],datasets:[{data:tpQty.length?tpQty:[1],backgroundColor:['#e94560','#3498db','#27ae60','#f39c12','#9b59b6'],borderWidth:2}]},
  options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}}}
});
</script>
</body>
</html>
