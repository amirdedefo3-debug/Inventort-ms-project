<?php
session_start();

// Already logged in → redirect to correct dashboard
if (isset($_SESSION['user_id'])) {
    $r = $_SESSION['user_role'] ?? 'cashier';
    header("Location: " . ($r==='admin' ? 'admin/dashboard.php' : ($r==='manager' ? 'manager/dashboard.php' : 'cashier/dashboard.php')));
    exit;
}

require_once 'db.php';

// ═══════════════════════════════════════════════════════════════
// AUTO-UPGRADE: bring old database tables up to new structure
// ═══════════════════════════════════════════════════════════════

// Helper: get column names of a table
function get_cols($conn, $table) {
    $cols = [];
    $r = mysqli_query($conn, "SHOW COLUMNS FROM `$table`");
    if ($r) while ($c = mysqli_fetch_assoc($r)) $cols[] = $c['Field'];
    return $cols;
}

// ── USERS TABLE ──────────────────────────────────────────────────
$u_cols = get_cols($conn, 'users');

if (!in_array('full_name', $u_cols) && in_array('name', $u_cols)) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN full_name VARCHAR(100) NOT NULL DEFAULT '' AFTER id");
    mysqli_query($conn, "UPDATE users SET full_name = `name`");
}
if (!in_array('full_name', $u_cols) && !in_array('name', $u_cols)) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN full_name VARCHAR(100) NOT NULL DEFAULT 'User' AFTER id");
}
if (!in_array('phone',    $u_cols)) mysqli_query($conn, "ALTER TABLE users ADD COLUMN phone VARCHAR(30) DEFAULT NULL");
if (!in_array('username', $u_cols)) mysqli_query($conn, "ALTER TABLE users ADD COLUMN username VARCHAR(80) DEFAULT NULL");
if (!in_array('status',   $u_cols)) mysqli_query($conn, "ALTER TABLE users ADD COLUMN status VARCHAR(10) NOT NULL DEFAULT 'active'");
if (!in_array('role',     $u_cols)) mysqli_query($conn, "ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'cashier'");
// Fix old 'staff' role → 'cashier', 'admin' stays
@mysqli_query($conn, "UPDATE users SET role='cashier' WHERE role='staff'");
@mysqli_query($conn, "UPDATE users SET role='cashier' WHERE role NOT IN ('admin','manager','cashier')");

// ── PRODUCTS TABLE ───────────────────────────────────────────────
$p_cols = get_cols($conn, 'products');

// min_stock → reorder_level
if (in_array('min_stock', $p_cols) && !in_array('reorder_level', $p_cols)) {
    mysqli_query($conn, "ALTER TABLE products CHANGE `min_stock` `reorder_level` INT NOT NULL DEFAULT 5");
}
if (!in_array('reorder_level', $p_cols)) {
    mysqli_query($conn, "ALTER TABLE products ADD COLUMN reorder_level INT NOT NULL DEFAULT 5");
}
if (!in_array('barcode',        $p_cols)) mysqli_query($conn, "ALTER TABLE products ADD COLUMN barcode VARCHAR(100) DEFAULT NULL");
if (!in_array('supplier_id',    $p_cols)) mysqli_query($conn, "ALTER TABLE products ADD COLUMN supplier_id INT DEFAULT NULL");
if (!in_array('purchase_price', $p_cols)) {
    mysqli_query($conn, "ALTER TABLE products ADD COLUMN purchase_price DECIMAL(10,2) DEFAULT 0.00");
    if (in_array('price', $p_cols)) mysqli_query($conn, "UPDATE products SET purchase_price = price * 0.6");
}
if (!in_array('selling_price', $p_cols)) {
    mysqli_query($conn, "ALTER TABLE products ADD COLUMN selling_price DECIMAL(10,2) DEFAULT 0.00");
    if (in_array('price', $p_cols)) mysqli_query($conn, "UPDATE products SET selling_price = price");
}

// ── ENSURE ALL REQUIRED TABLES EXIST ────────────────────────────
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_name VARCHAR(150) DEFAULT 'ShopStock',
    shop_address TEXT, shop_phone VARCHAR(50), shop_email VARCHAR(150),
    tax_rate DECIMAL(5,2) DEFAULT 0.00,
    currency VARCHAR(10) DEFAULT 'USD', currency_symbol VARCHAR(5) DEFAULT '\$',
    theme VARCHAR(20) DEFAULT 'light', logo VARCHAR(255) DEFAULT '',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
mysqli_query($conn, "INSERT IGNORE INTO settings (id,shop_name,currency,currency_symbol,tax_rate) VALUES (1,'ShopStock','USD','\$',0)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100), phone VARCHAR(50), email VARCHAR(150),
    address TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(200) NOT NULL,
    message TEXT, type VARCHAR(50) DEFAULT 'info',
    is_read TINYINT DEFAULT 0, user_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, user_name VARCHAR(100),
    action VARCHAR(255) NOT NULL, details TEXT, ip_address VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS stock_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY, product_id INT NOT NULL,
    type ENUM('in','out','adjustment') NOT NULL, quantity INT NOT NULL,
    reason VARCHAR(255), supplier_id INT DEFAULT NULL, user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY, invoice_number VARCHAR(60) UNIQUE,
    user_id INT, subtotal DECIMAL(12,2) DEFAULT 0, discount DECIMAL(10,2) DEFAULT 0,
    tax DECIMAL(10,2) DEFAULT 0, total DECIMAL(12,2) DEFAULT 0,
    payment_method VARCHAR(20) DEFAULT 'cash',
    amount_paid DECIMAL(12,2) DEFAULT 0, change_amount DECIMAL(12,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY, sale_id INT NOT NULL,
    product_id INT NOT NULL, product_name VARCHAR(150),
    quantity INT NOT NULL, unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT AUTO_INCREMENT PRIMARY KEY, po_number VARCHAR(50) UNIQUE,
    supplier_id INT, user_id INT,
    status VARCHAR(20) DEFAULT 'pending',
    total_cost DECIMAL(12,2) DEFAULT 0, notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS purchase_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY, po_id INT NOT NULL,
    product_id INT NOT NULL, quantity INT NOT NULL, unit_cost DECIMAL(10,2) NOT NULL
)");

// ── INSERT DEFAULT ACCOUNTS IF MISSING ──────────────────────────
$name_col = in_array('full_name', get_cols($conn,'users')) ? 'full_name' : 'name';
$h1 = password_hash("admin123",   PASSWORD_DEFAULT);
$h2 = password_hash("manager123", PASSWORD_DEFAULT);
$h3 = password_hash("cashier123", PASSWORD_DEFAULT);
mysqli_query($conn, "INSERT IGNORE INTO users ($name_col,email,password,role) VALUES ('System Administrator','admin@shop.com','$h1','admin')");
mysqli_query($conn, "INSERT IGNORE INTO users ($name_col,email,password,role) VALUES ('Inventory Manager','manager@shop.com','$h2','manager')");
mysqli_query($conn, "INSERT IGNORE INTO users ($name_col,email,password,role) VALUES ('Sales Cashier','cashier@shop.com','$h3','cashier')");

// ═══════════════════════════════════════════════════════════════
// LOGIN LOGIC
// ═══════════════════════════════════════════════════════════════
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login) || empty($password)) {
        $error = "Please enter your email/username and password.";
    } else {
        $esc  = mysqli_real_escape_string($conn, $login);
        $ucols = get_cols($conn, 'users');
        $has_username = in_array('username', $ucols);
        $has_status   = in_array('status',   $ucols);
        $name_field   = in_array('full_name', $ucols) ? 'full_name' : 'name';

        $where  = $has_username ? "(email='$esc' OR username='$esc')" : "email='$esc'";
        $status = $has_status   ? " AND status='active'" : "";

        $res = mysqli_query($conn, "SELECT * FROM users WHERE $where $status LIMIT 1");

        if ($res && mysqli_num_rows($res) === 1) {
            $u = mysqli_fetch_assoc($res);
            if (password_verify($password, $u['password'])) {
                $display_name          = $u[$name_field] ?? $u['email'];
                $_SESSION['user_id']   = $u['id'];
                $_SESSION['user_name'] = $display_name;
                $_SESSION['user_role'] = $u['role'] ?? 'cashier';
                $_SESSION['user_email']= $u['email'];

                $uid   = intval($u['id']);
                $uname = mysqli_real_escape_string($conn, $display_name);
                $ip    = $_SERVER['REMOTE_ADDR'] ?? '';
                mysqli_query($conn, "INSERT INTO activity_logs (user_id,user_name,action,ip_address) VALUES ($uid,'$uname','Logged in','$ip')");

                $role = $_SESSION['user_role'];
                $dest = $role === 'admin' ? 'admin/dashboard.php' : ($role === 'manager' ? 'manager/dashboard.php' : 'cashier/dashboard.php');
                header("Location: $dest");
                exit;
            } else {
                $error = "Incorrect password. Please try again.";
            }
        } else {
            $error = "No active account found with that email or username.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Login | ShopStock</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',sans-serif;background:linear-gradient(135deg,#1a1a2e 0%,#16213e 50%,#0f3460 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.wrap{width:100%;max-width:440px;}
.logo{text-align:center;margin-bottom:28px;}
.logo .icon{font-size:50px;}
.logo h1{color:#fff;font-size:26px;font-weight:700;margin-top:8px;}
.logo p{color:rgba(255,255,255,.5);font-size:12px;letter-spacing:2px;text-transform:uppercase;margin-top:4px;}
.card{background:#fff;border-radius:16px;padding:36px;box-shadow:0 24px 60px rgba(0,0,0,.3);}
.card h2{font-size:20px;color:#1a1a2e;margin-bottom:4px;}
.card .sub{color:#aaa;font-size:13px;margin-bottom:26px;}
.fg{margin-bottom:18px;}
.fg label{display:block;font-size:11px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;}
.iw{position:relative;}
.iw .ic{position:absolute;left:13px;top:50%;transform:translateY(-50%);font-size:16px;}
.fg input{width:100%;padding:11px 14px 11px 40px;border:2px solid #e8e8e8;border-radius:8px;font-size:14px;color:#333;transition:border-color .2s;background:#fafafa;font-family:inherit;}
.fg input:focus{outline:none;border-color:#e94560;background:#fff;}
.eye{position:absolute;right:13px;top:50%;transform:translateY(-50%);cursor:pointer;font-size:16px;}
.err{background:#fdecea;color:#e74c3c;border-left:4px solid #e74c3c;padding:12px 16px;border-radius:8px;font-size:14px;margin-bottom:18px;}
.ok{background:#e6f9f0;color:#27ae60;border-left:4px solid #27ae60;padding:12px 16px;border-radius:8px;font-size:14px;margin-bottom:18px;}
.btn{width:100%;padding:13px;background:linear-gradient(135deg,#e94560,#c0392b);color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;}
.btn:hover{opacity:.92;}
.roles{margin-top:22px;background:#f8f9fa;border-radius:8px;padding:14px;}
.roles strong{display:block;font-size:11px;color:#888;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;}
.role-row{display:flex;justify-content:space-between;align-items:center;padding:5px 0;border-bottom:1px solid #eee;font-size:12px;color:#666;}
.role-row:last-child{border-bottom:none;}
.fill-btn{background:none;border:none;color:#3498db;cursor:pointer;font-size:11px;font-weight:700;}
.fill-btn:hover{text-decoration:underline;}
.reg-link{text-align:center;font-size:13px;color:#666;margin-top:16px;}
.reg-link a{color:#e94560;font-weight:600;text-decoration:none;}
</style>
</head>
<body>
<div class="wrap">
  <div class="logo"><div class="icon">🏪</div><h1>ShopStock</h1><p>Inventory Manager</p></div>
  <div class="card">
    <h2>Welcome back 👋</h2>
    <p class="sub">Sign in to access your dashboard</p>
    <?php if ($error): ?>
    <div class="err">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'logged_out'): ?>
    <div class="ok">✅ You have been logged out successfully.</div>
    <?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'access_denied'): ?>
    <div class="err">🚫 Access denied. You don't have permission for that page.</div>
    <?php endif; ?>
    <form method="POST">
      <div class="fg">
        <label>Email or Username</label>
        <div class="iw">
          <span class="ic">👤</span>
          <input type="text" name="login" placeholder="Email or username"
                 value="<?= htmlspecialchars($_POST['login'] ?? '') ?>" required autofocus>
        </div>
      </div>
      <div class="fg">
        <label>Password</label>
        <div class="iw">
          <span class="ic">🔒</span>
          <input type="password" name="password" id="pwd" placeholder="Password" required>
          <span class="eye" onclick="var p=document.getElementById('pwd');p.type=p.type=='password'?'text':'password';this.textContent=p.type=='password'?'👁️':'🙈'">👁️</span>
        </div>
      </div>
      <button type="submit" class="btn">🔐 Sign In</button>
    </form>
    <div class="roles">
      <strong>🧪 Demo Accounts</strong>
      <div class="role-row">
        <span>🔑 Admin — admin@shop.com / admin123</span>
        <button class="fill-btn" onclick="f('admin@shop.com','admin123')">Fill</button>
      </div>
      <div class="role-row">
        <span>📦 Manager — manager@shop.com / manager123</span>
        <button class="fill-btn" onclick="f('manager@shop.com','manager123')">Fill</button>
      </div>
      <div class="role-row">
        <span>🛒 Cashier — cashier@shop.com / cashier123</span>
        <button class="fill-btn" onclick="f('cashier@shop.com','cashier123')">Fill</button>
      </div>
    </div>
    <div class="reg-link">No account? <a href="register.php">Register here</a></div>
  </div>
</div>
<script>
function f(u, p) {
    document.querySelector('[name=login]').value = u;
    document.querySelector('[name=password]').value = p;
}
</script>
</body>
</html>
