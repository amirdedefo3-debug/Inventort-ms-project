<?php
$host = "localhost";
$user = "root";
$password = "";

$conn = mysqli_connect($host, $user, $password);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$sql = "CREATE DATABASE IF NOT EXISTS inventory_db CHARACTER SET utf8 COLLATE utf8_general_ci";
mysqli_query($conn, $sql);
mysqli_select_db($conn, "inventory_db");

$sql1 = "CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$sql2 = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    category_id INT DEFAULT NULL,
    quantity INT NOT NULL DEFAULT 0,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    min_stock INT NOT NULL DEFAULT 5,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
)";

mysqli_query($conn, $sql1);
mysqli_query($conn, $sql2);

// Insert sample categories
$cats = ["Electronics", "Clothing", "Food & Beverages", "Stationery", "Household"];
foreach ($cats as $cat) {
    mysqli_query($conn, "INSERT IGNORE INTO categories (name) VALUES ('$cat')");
}

// Insert sample products
$products = [
    ["USB Cable", 1, 50, 2.99, 10, "USB Type-A to Type-C cable"],
    ["Headphones", 1, 8, 15.99, 5, "Stereo over-ear headphones"],
    ["T-Shirt (L)", 2, 3, 9.99, 10, "Cotton T-shirt large size"],
    ["Notebook", 4, 120, 1.49, 20, "A5 lined notebook"],
    ["Rice 1kg", 3, 4, 1.99, 15, "White long grain rice"],
    ["Pen Pack", 4, 60, 0.99, 25, "Pack of 10 ballpoint pens"],
    ["Soap Bar", 5, 2, 0.79, 10, "Antibacterial soap"],
    ["Phone Case", 1, 25, 4.99, 8, "Universal phone case"],
];

foreach ($products as $p) {
    $name = $p[0]; $cat = $p[1]; $qty = $p[2]; $price = $p[3]; $min = $p[4]; $desc = $p[5];
    mysqli_query($conn, "INSERT INTO products (name, category_id, quantity, price, min_stock, description) VALUES ('$name', $cat, $qty, $price, $min, '$desc')");
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Setup Complete</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f4f8; display:flex; justify-content:center; align-items:center; height:100vh; margin:0; }
        .box { background:#fff; padding:40px; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.1); text-align:center; }
        h2 { color: #27ae60; }
        a { display:inline-block; margin-top:20px; padding:12px 30px; background:#3498db; color:#fff; text-decoration:none; border-radius:8px; font-size:16px; }
        a:hover { background:#2980b9; }
    </style>
</head>
<body>
<div class="box">
    <h2>✅ Database Setup Complete!</h2>
    <p>Database <strong>inventory_db</strong> created with sample data.</p>
    <a href="index.php">Go to Dashboard →</a>
</div>
</body>
</html>
