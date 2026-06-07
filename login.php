<?php
session_start();
if (isset($_SESSION['user_id'])) {
    $r = $_SESSION['user_role'] ?? 'cashier';
    header("Location: " . ($r=='admin' ? 'admin/dashboard.php' : ($r=='manager' ? 'manager/dashboard.php' : 'cashier/dashboard.php')));
    exit;
}
require_once 'db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login    = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($login) || empty($password)) {
        $error = "Please enter your email/username and password.";
    } else {
        $esc = mysqli_real_escape_string($conn, $login);
        $res = mysqli_query($conn, "SELECT * FROM users WHERE (email='$esc' OR username='$esc') AND status='active'");
        if ($res && mysqli_num_rows($res) == 1) {
            $u = mysqli_fetch_assoc($res);
            if (password_verify($password, $u['password'])) {
                $_SESSION['user_id']   = $u['id'];
                $_SESSION['user_name'] = $u['full_name'];
                $_SESSION['user_role'] = $u['role'];
                $_SESSION['user_email']= $u['email'];
                // Log login
                $uid  = $u['id'];
                $uname= mysqli_real_escape_string($conn, $u['full_name']);
                $ip   = $_SERVER['REMOTE_ADDR'] ?? '';
                mysqli_query($conn,"INSERT INTO activity_logs (user_id,user_name,action,ip_address) VALUES ($uid,'$uname','Logged in','$ip')");
                $dest = $u['role']=='admin' ? 'admin/dashboard.php' : ($u['role']=='manager' ? 'manager/dashboard.php' : 'cashier/dashboard.php');
                header("Location: $dest"); exit;
            } else { $error = "Incorrect password."; }
        } else { $error = "No active account found with that email or username."; }
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
    <?php if ($error): ?><div class="err">⚠️ <?=htmlspecialchars($error)?></div><?php endif; ?>
    <?php if(isset($_GET['msg'])&&$_GET['msg']=='logged_out'): ?><div class="ok">✅ Logged out successfully.</div><?php endif; ?>
    <?php if(isset($_GET['error'])&&$_GET['error']=='access_denied'): ?><div class="err">🚫 Access denied.</div><?php endif; ?>
    <form method="POST">
      <div class="fg"><label>Email or Username</label>
        <div class="iw"><span class="ic">👤</span>
        <input type="text" name="login" placeholder="Email or username" value="<?=htmlspecialchars($_POST['login']??'')?>" required autofocus></div></div>
      <div class="fg"><label>Password</label>
        <div class="iw"><span class="ic">🔒</span>
        <input type="password" name="password" id="pwd" placeholder="Password" required>
        <span class="eye" onclick="var p=document.getElementById('pwd');p.type=p.type=='password'?'text':'password';this.textContent=p.type=='password'?'👁️':'🙈'">👁️</span>
        </div></div>
      <button type="submit" class="btn">🔐 Sign In</button>
    </form>
    <div class="roles">
      <strong>🧪 Demo Accounts</strong>
      <div class="role-row"><span>🔑 Admin — admin / admin123</span><button class="fill-btn" onclick="f('admin','admin123')">Fill</button></div>
      <div class="role-row"><span>📦 Manager — manager / manager123</span><button class="fill-btn" onclick="f('manager','manager123')">Fill</button></div>
      <div class="role-row"><span>🛒 Cashier — cashier / cashier123</span><button class="fill-btn" onclick="f('cashier','cashier123')">Fill</button></div>
    </div>
    <div class="reg-link">No account? <a href="register.php">Register here</a></div>
  </div>
</div>
<script>
function f(u,p){document.querySelector('[name=login]').value=u;document.querySelector('[name=password]').value=p;}
</script>
</body>
</html>
