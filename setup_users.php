<?php
require_once 'db.php';

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','staff') NOT NULL DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $sql);

// Insert default admin account
$hash = password_hash("admin123", PASSWORD_DEFAULT);
mysqli_query($conn, "INSERT IGNORE INTO users (name, email, password, role)
    VALUES ('Admin User', 'admin@shop.com', '$hash', 'admin')");

// Insert default staff account
$hash2 = password_hash("staff123", PASSWORD_DEFAULT);
mysqli_query($conn, "INSERT IGNORE INTO users (name, email, password, role)
    VALUES ('Staff User', 'staff@shop.com', '$hash2', 'staff')");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Setup</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f4f8; display:flex; justify-content:center; align-items:center; height:100vh; margin:0; }
        .box { background:#fff; padding:40px; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.1); text-align:center; min-width:340px; }
        h2 { color:#27ae60; margin-bottom:16px; }
        table { width:100%; border-collapse:collapse; margin:20px 0; text-align:left; }
        th, td { padding:10px 14px; border:1px solid #eee; font-size:14px; }
        th { background:#f8f9fa; font-weight:600; }
        a { display:inline-block; margin-top:10px; padding:12px 30px; background:#e94560; color:#fff; text-decoration:none; border-radius:8px; font-size:16px; }
        a:hover { background:#c0392b; }
    </style>
</head>
<body>
<div class="box">
    <h2>✅ User Accounts Ready!</h2>
    <p>Default accounts created:</p>
    <table>
        <tr><th>Role</th><th>Email</th><th>Password</th></tr>
        <tr><td>🔑 Admin</td><td>admin@shop.com</td><td>admin123</td></tr>
        <tr><td>👤 Staff</td><td>staff@shop.com</td><td>staff123</td></tr>
    </table>
    <a href="login.php">Go to Login →</a>
</div>
</body>
</html>
