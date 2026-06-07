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

foreach ($tables as $sql) {
    if (!mysqli_query($conn, $sql)) {
        echo "<p style='color:red;'>Error: " . mysqli_error($conn) . "</p>";
    }
}

// Default settings
mysqli_query($conn, "INSERT IGNORE INTO settings (id, shop_name, currency, currency_symbol, tax_rate) VALUES (1, 'ShopStock', 'USD', '$', 0)");

// Default admin
$hash = password_hash("admin123", PASSWORD_DEFAULT);
mysqli_query($conn, "INSERT IGNORE INTO users (full_name, email, username, password, role) VALUES ('System Administrator','admin@shop.com','admin','$hash','admin')");

// Default manager
$hash2 = password_hash("manager123", PASSWORD_DEFAULT);
mysqli_query($conn, "INSERT IGNORE INTO users (full_name, email, username, password, role) VALUES ('Inventory Manager','manager@shop.com','manager','$hash2','manager')");

// Default cashier
$hash3 = password_hash("cashier123", PASSWORD_DEFAULT);
mysqli_query($conn, "INSERT IGNORE INTO users (full_name, email, username, password, role) VALUES ('Sales Cashier','cashier@shop.com','cashier','$hash3','cashier')");

// Sample categories
$cats = [
    ["Electronics","Electronic devices and accessories"],
    ["Clothing","Apparel and fashion items"],
    ["Food & Beverages","Consumable food and drinks"],
    ["Stationery","Office and school supplies"],
    ["Household","Home and household items"]
];
foreach ($cats as $c) mysqli_query($conn, "INSERT IGNORE INTO categories (name,description) VALUES ('".mysqli_real_escape_string($conn,$c[0])."','".mysqli_real_escape_string($conn,$c[1])."')");

// Sample suppliers
$suppliers = [
    ["TechWorld Supplies","John Doe","0911000001","tech@supplier.com","123 Tech Street"],
    ["FashionHub","Jane Smith","0911000002","fashion@supplier.com","456 Fashion Ave"],
    ["FoodMart Wholesale","Bob Brown","0911000003","food@supplier.com","789 Market Rd"],
];
foreach ($suppliers as $s) {
    mysqli_query($conn,"INSERT IGNORE INTO suppliers (name,contact_person,phone,email,address) VALUES ('$s[0]','$s[1]','$s[2]','$s[3]','$s[4]')");
}

// Sample products
$products = [
    ["USB Cable","BC001",1,1,1.50,2.99,50,10,""],
    ["Headphones","BC002",1,1,8.00,15.99,8,5,""],
    ["T-Shirt (L)","BC003",2,2,4.00,9.99,3,10,""],
    ["Notebook A5","BC004",4,3,0.50,1.49,120,20,""],
    ["Rice 1kg","BC005",3,3,0.80,1.99,4,15,""],
    ["Pen Pack","BC006",4,3,0.30,0.99,60,25,""],
    ["Soap Bar","BC007",5,3,0.25,0.79,2,10,""],
    ["Phone Case","BC008",1,1,1.99,4.99,25,8,""],
];
foreach ($products as $p) {
    $name=$p[0];$bc=$p[1];$cat=$p[2];$sup=$p[3];$pp=$p[4];$sp=$p[5];$qty=$p[6];$rl=$p[7];
    mysqli_query($conn,"INSERT IGNORE INTO products (name,barcode,category_id,supplier_id,purchase_price,selling_price,quantity,reorder_level) VALUES ('$name','$bc',$cat,$sup,$pp,$sp,$qty,$rl)");
}

// Sample sales
for ($i = 1; $i <= 20; $i++) {
    $inv = "INV-" . str_pad($i, 5, "0", STR_PAD_LEFT);
    $total = rand(5, 150) + 0.99;
    $days_ago = rand(0, 30);
    $date = date('Y-m-d H:i:s', strtotime("-$days_ago days"));
    $methods = ['cash','card','mobile'];
    $method = $methods[array_rand($methods)];
    mysqli_query($conn,"INSERT IGNORE INTO sales (invoice_number,user_id,subtotal,total,payment_method,amount_paid,created_at) VALUES ('$inv',3,$total,$total,'$method',$total,'$date')");
    $sale_id = mysqli_insert_id($conn);
    if ($sale_id) {
        mysqli_query($conn,"INSERT INTO sale_items (sale_id,product_id,product_name,quantity,unit_price,total_price) VALUES ($sale_id,1,'USB Cable',".rand(1,5).",2.99,".(rand(1,5)*2.99).")");
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Full Setup</title>
<style>
body{font-family:Arial,sans-serif;background:#f0f4f8;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;}
.box{background:#fff;padding:40px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.1);text-align:center;max-width:480px;}
h2{color:#27ae60;} table{width:100%;border-collapse:collapse;margin:20px 0;text-align:left;}
th,td{padding:9px 14px;border:1px solid #eee;font-size:13px;} th{background:#f8f9fa;font-weight:600;}
a{display:inline-block;margin-top:14px;padding:12px 30px;background:#e94560;color:#fff;text-decoration:none;border-radius:8px;font-size:15px;}
a:hover{background:#c0392b;}
</style>
</head>
<body>
<div class="box">
    <h2>✅ Full System Setup Complete!</h2>
    <p>All tables, roles and sample data created.</p>
    <table>
        <tr><th>Role</th><th>Email</th><th>Password</th></tr>
        <tr><td>🔑 Admin</td><td>admin@shop.com</td><td>admin123</td></tr>
        <tr><td>📦 Manager</td><td>manager@shop.com</td><td>manager123</td></tr>
        <tr><td>🛒 Cashier</td><td>cashier@shop.com</td><td>cashier123</td></tr>
    </table>
    <a href="login.php">Go to Login →</a>
</div>
</body>
</html>
