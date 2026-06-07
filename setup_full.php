<?php
$host = "localhost"; $user = "root"; $password = "";
$conn = mysqli_connect($host, $user, $password);
if (!$conn) die("Connection failed: " . mysqli_connect_error());

mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS inventory_db CHARACTER SET utf8 COLLATE utf8_general_ci");
mysqli_select_db($conn, "inventory_db");

$tables = [];

$tables[] = "CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_name VARCHAR(150) DEFAULT 'ShopStock',
    shop_address TEXT,
    shop_phone VARCHAR(50),
    shop_email VARCHAR(150),
    tax_rate DECIMAL(5,2) DEFAULT 0.00,
    currency VARCHAR(10) DEFAULT 'USD',
    currency_symbol VARCHAR(5) DEFAULT '$',
    theme VARCHAR(20) DEFAULT 'light',
    logo VARCHAR(255) DEFAULT '',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

$tables[] = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30),
    username VARCHAR(80) UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','manager','cashier') NOT NULL DEFAULT 'cashier',
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$tables[] = "CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$tables[] = "CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(50),
    email VARCHAR(150),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$tables[] = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    barcode VARCHAR(100),
    category_id INT DEFAULT NULL,
    supplier_id INT DEFAULT NULL,
    purchase_price DECIMAL(10,2) DEFAULT 0.00,
    selling_price DECIMAL(10,2) DEFAULT 0.00,
    quantity INT DEFAULT 0,
    reorder_level INT DEFAULT 5,
    image VARCHAR(255) DEFAULT '',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
)";

$tables[] = "CREATE TABLE IF NOT EXISTS stock_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    type ENUM('in','out','adjustment') NOT NULL,
    quantity INT NOT NULL,
    reason VARCHAR(255),
    supplier_id INT DEFAULT NULL,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)";

$tables[] = "CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(50) UNIQUE,
    supplier_id INT,
    user_id INT,
    status ENUM('pending','approved','received','cancelled') DEFAULT 'pending',
    total_cost DECIMAL(12,2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
)";

$tables[] = "CREATE TABLE IF NOT EXISTS purchase_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_cost DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)";

$tables[] = "CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(60) UNIQUE,
    user_id INT,
    subtotal DECIMAL(12,2) DEFAULT 0,
    discount DECIMAL(10,2) DEFAULT 0,
    tax DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(12,2) DEFAULT 0,
    payment_method ENUM('cash','card','mobile') DEFAULT 'cash',
    amount_paid DECIMAL(12,2) DEFAULT 0,
    change_amount DECIMAL(12,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$tables[] = "CREATE TABLE IF NOT EXISTS sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(150),
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
)";

$tables[] = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    message TEXT,
    type VARCHAR(50) DEFAULT 'info',
    is_read TINYINT DEFAULT 0,
    user_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$tables[] = "CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    user_name VARCHAR(100),
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$errors_list = [];
foreach ($tables as $sql) {
    if (!mysqli_query($conn, $sql)) {
        $errors_list[] = mysqli_error($conn);
    }
}

// Default settings row
mysqli_query($conn, "INSERT IGNORE INTO settings (id, shop_name, currency, currency_symbol, tax_rate) VALUES (1,'ShopStock','USD','$',0)");

// Default users
$hash1 = password_hash("admin123",   PASSWORD_DEFAULT);
$hash2 = password_hash("manager123", PASSWORD_DEFAULT);
$hash3 = password_hash("cashier123", PASSWORD_DEFAULT);
mysqli_query($conn, "INSERT IGNORE INTO users (full_name,email,username,password,role,status) VALUES ('System Administrator','admin@shop.com','admin','$hash1','admin','active')");
mysqli_query($conn, "INSERT IGNORE INTO users (full_name,email,username,password,role,status) VALUES ('Inventory Manager','manager@shop.com','manager','$hash2','manager','active')");
mysqli_query($conn, "INSERT IGNORE INTO users (full_name,email,username,password,role,status) VALUES ('Sales Cashier','cashier@shop.com','cashier','$hash3','cashier','active')");

// Sample categories
$cats = [
    ["Electronics","Electronic devices and accessories"],
    ["Clothing","Apparel and fashion items"],
    ["Food & Beverages","Consumable food and drinks"],
    ["Stationery","Office and school supplies"],
    ["Household","Home and household items"]
];
foreach ($cats as $c) {
    $n = mysqli_real_escape_string($conn,$c[0]);
    $d = mysqli_real_escape_string($conn,$c[1]);
    mysqli_query($conn,"INSERT IGNORE INTO categories (name,description) VALUES ('$n','$d')");
}

// Sample suppliers
$suppliers = [
    ["TechWorld Supplies","John Doe","0911000001","tech@supplier.com","123 Tech Street"],
    ["FashionHub Ltd","Jane Smith","0911000002","fashion@supplier.com","456 Fashion Ave"],
    ["FoodMart Wholesale","Bob Brown","0911000003","food@supplier.com","789 Market Road"],
];
foreach ($suppliers as $s) {
    mysqli_query($conn,"INSERT IGNORE INTO suppliers (name,contact_person,phone,email,address)
        VALUES ('".mysqli_real_escape_string($conn,$s[0])."','".mysqli_real_escape_string($conn,$s[1])."',
                '".mysqli_real_escape_string($conn,$s[2])."','".mysqli_real_escape_string($conn,$s[3])."',
                '".mysqli_real_escape_string($conn,$s[4])."')");
}

// Sample products
$products = [
    ["USB Cable","BC001",1,1,1.50,2.99,50,10,"USB Type-A to Type-C cable"],
    ["Headphones","BC002",1,1,8.00,15.99,8,5,"Stereo over-ear headphones"],
    ["T-Shirt (L)","BC003",2,2,4.00,9.99,3,10,"Cotton T-shirt large size"],
    ["Notebook A5","BC004",4,3,0.50,1.49,120,20,"A5 lined notebook"],
    ["Rice 1kg","BC005",3,3,0.80,1.99,4,15,"White long grain rice"],
    ["Pen Pack","BC006",4,3,0.30,0.99,60,25,"Pack of 10 ballpoint pens"],
    ["Soap Bar","BC007",5,3,0.25,0.79,2,10,"Antibacterial soap bar"],
    ["Phone Case","BC008",1,1,1.99,4.99,25,8,"Universal phone protective case"],
    ["Water Bottle","BC009",5,3,1.50,3.49,15,5,"1L reusable water bottle"],
    ["LED Bulb","BC010",1,1,1.00,2.49,40,12,"9W LED energy saving bulb"],
];
foreach ($products as $p) {
    $n=mysqli_real_escape_string($conn,$p[0]);
    $d=mysqli_real_escape_string($conn,$p[8]);
    $existing = mysqli_query($conn,"SELECT id FROM products WHERE barcode='$p[1]'");
    if (mysqli_num_rows($existing)==0) {
        mysqli_query($conn,"INSERT INTO products (name,barcode,category_id,supplier_id,purchase_price,selling_price,quantity,reorder_level,description)
            VALUES ('$n','$p[1]',$p[2],$p[3],$p[4],$p[5],$p[6],$p[7],'$d')");
    }
}

// Sample sales (last 30 days)
$cashier_id = mysqli_fetch_assoc(mysqli_query($conn,"SELECT id FROM users WHERE role='cashier' LIMIT 1"))['id'] ?? 3;
for ($i = 1; $i <= 25; $i++) {
    $inv = "INV-" . str_pad($i, 5,"0",STR_PAD_LEFT);
    $existing = mysqli_query($conn,"SELECT id FROM sales WHERE invoice_number='$inv'");
    if (mysqli_num_rows($existing) > 0) continue;
    $days_ago = rand(0, 29);
    $date = date('Y-m-d H:i:s', strtotime("-$days_ago days"));
    $methods = ['cash','card','mobile'];
    $method  = $methods[array_rand($methods)];
    $subtotal= round(rand(3,80) + 0.99, 2);
    mysqli_query($conn,"INSERT INTO sales (invoice_number,user_id,subtotal,total,payment_method,amount_paid,created_at)
        VALUES ('$inv',$cashier_id,$subtotal,$subtotal,'$method',$subtotal,'$date')");
    $sale_id = mysqli_insert_id($conn);
    if ($sale_id) {
        $qty2 = rand(1,4);
        mysqli_query($conn,"INSERT INTO sale_items (sale_id,product_id,product_name,quantity,unit_price,total_price)
            VALUES ($sale_id,1,'USB Cable',$qty2,2.99,".round($qty2*2.99,2).")");
    }
}

// Initial stock transactions
$prods_with_stock = mysqli_query($conn,"SELECT id,quantity FROM products WHERE quantity>0");
while ($pr = mysqli_fetch_assoc($prods_with_stock)) {
    $chk = mysqli_query($conn,"SELECT id FROM stock_transactions WHERE product_id={$pr['id']} AND type='in' LIMIT 1");
    if (mysqli_num_rows($chk)==0) {
        mysqli_query($conn,"INSERT INTO stock_transactions (product_id,type,quantity,reason,user_id) VALUES ({$pr['id']},'in',{$pr['quantity']},'Initial stock',1)");
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Full System Setup | ShopStock</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',sans-serif;background:linear-gradient(135deg,#1a1a2e,#0f3460);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.box{background:#fff;padding:40px;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.3);max-width:520px;width:100%;}
h2{color:#27ae60;font-size:22px;margin-bottom:6px;}
.sub{color:#888;font-size:13px;margin-bottom:24px;}
table{width:100%;border-collapse:collapse;margin:16px 0;}
th,td{padding:10px 14px;border:1px solid #eee;font-size:13px;text-align:left;}
th{background:#f8f9fa;font-weight:700;color:#555;}
.role-badge{display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:700;}
.admin{background:#fdecea;color:#e94560;}
.manager{background:#e8f0fe;color:#3498db;}
.cashier{background:#e6f9f0;color:#27ae60;}
.btn{display:inline-block;margin-top:6px;padding:12px 28px;background:#e94560;color:#fff;text-decoration:none;border-radius:8px;font-size:15px;font-weight:700;}
.btn:hover{background:#c0392b;}
.btn-2{background:#3498db;margin-left:8px;}
.btn-2:hover{background:#2980b9;}
<?php if(!empty($errors_list)):?>
.err{background:#fdecea;border-left:4px solid #e74c3c;padding:10px 14px;border-radius:8px;font-size:12px;color:#e74c3c;margin-bottom:14px;}
<?php endif;?>
</style>
</head>
<body>
<div class="box">
  <h2>✅ Full System Setup Complete!</h2>
  <p class="sub">All tables, users, and sample data have been created in <strong>inventory_db</strong></p>

  <?php if (!empty($errors_list)): ?>
  <div class="err">⚠️ Some errors: <?= implode(', ', $errors_list) ?></div>
  <?php endif; ?>

  <table>
    <tr><th>Role</th><th>Email / Username</th><th>Password</th></tr>
    <tr>
      <td><span class="role-badge admin">🔑 Admin</span></td>
      <td>admin@shop.com &nbsp;|&nbsp; <strong>admin</strong></td>
      <td><code>admin123</code></td>
    </tr>
    <tr>
      <td><span class="role-badge manager">📦 Manager</span></td>
      <td>manager@shop.com &nbsp;|&nbsp; <strong>manager</strong></td>
      <td><code>manager123</code></td>
    </tr>
    <tr>
      <td><span class="role-badge cashier">🛒 Cashier</span></td>
      <td>cashier@shop.com &nbsp;|&nbsp; <strong>cashier</strong></td>
      <td><code>cashier123</code></td>
    </tr>
  </table>

  <div>
    <a href="login.php" class="btn">🔐 Go to Login</a>
    <a href="register.php" class="btn btn-2">📝 Register</a>
  </div>
</div>
</body>
</html>
