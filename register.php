<?php
session_start();
if (isset($_SESSION['user_id'])) {
    $r = $_SESSION['user_role'] ?? 'cashier';
    header("Location: " . ($r=='admin'?'admin/dashboard.php':($r=='manager'?'manager/dashboard.php':'cashier/dashboard.php')));
    exit;
}
require_once 'db.php';
$errors = []; $success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $role      = in_array($_POST['role']??'cashier', ['admin','manager','cashier']) ? $_POST['role'] : 'cashier';

    if (!$full_name)               $errors[] = "Full name is required.";
    if (!$email)                   $errors[] = "Email is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email address.";
    if (!$password)                $errors[] = "Password is required.";
    if (strlen($password) < 6)    $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm)    $errors[] = "Passwords do not match.";

    if (empty($errors)) {
        $em = mysqli_real_escape_string($conn, $email);
        $chk = mysqli_query($conn, "SELECT id FROM users WHERE email='$em'");
        if (mysqli_num_rows($chk) > 0) $errors[] = "Email already registered.";
    }
    if ($username) {
        $un = mysqli_real_escape_string($conn, $username);
        $chk2 = mysqli_query($conn, "SELECT id FROM users WHERE username='$un'");
        if (mysqli_num_rows($chk2) > 0) $errors[] = "Username already taken.";
    }

    if (empty($errors)) {
        $fn  = mysqli_real_escape_string($conn, $full_name);
        $em  = mysqli_real_escape_string($conn, $email);
        $un  = mysqli_real_escape_string($conn, $username);
        $ph  = mysqli_real_escape_string($conn, $phone);
        $hash = password_hash($password, PASSWORD_DEFAULT);
        mysqli_query($conn, "INSERT INTO users (full_name,email,phone,username,password,role) VALUES ('$fn','$em','$ph','$un','$hash','$role')");
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Register | ShopStock</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Segoe UI',sans-serif;background:linear-gradient(135deg,#1a1a2e 0%,#16213e 50%,#0f3460 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.wrap{width:100%;max-width:480px;}
.logo{text-align:center;margin-bottom:24px;}
.logo .icon{font-size:46px;} .logo h1{color:#fff;font-size:24px;font-weight:700;margin-top:6px;}
.logo p{color:rgba(255,255,255,.4);font-size:11px;letter-spacing:2px;text-transform:uppercase;margin-top:4px;}
.card{background:#fff;border-radius:16px;padding:32px;box-shadow:0 24px 60px rgba(0,0,0,.3);}
.card h2{font-size:19px;color:#1a1a2e;margin-bottom:4px;} .sub{color:#aaa;font-size:13px;margin-bottom:22px;}
.fg{margin-bottom:16px;}
.fg label{display:block;font-size:11px;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px;}
.fg input,.fg select{width:100%;padding:10px 14px;border:2px solid #e8e8e8;border-radius:8px;font-size:13px;color:#333;background:#fafafa;font-family:inherit;transition:border-color .2s;}
.fg input:focus,.fg select:focus{outline:none;border-color:#e94560;background:#fff;}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.roles{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-top:6px;}
.role-opt{border:2px solid #e8e8e8;border-radius:10px;padding:12px 8px;cursor:pointer;text-align:center;transition:all .2s;position:relative;}
.role-opt input[type=radio]{position:absolute;opacity:0;width:0;height:0;}
.role-opt .ri{font-size:24px;display:block;margin-bottom:4px;} .role-opt .rn{font-size:12px;font-weight:700;color:#333;} .role-opt .rd{font-size:10px;color:#aaa;margin-top:2px;}
.role-opt.sel{border-color:#e94560;background:#fff5f6;} .role-opt.sel .rn{color:#e94560;}
.err-box{background:#fdecea;color:#e74c3c;border-left:4px solid #e74c3c;padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:16px;}
.err-box ul{margin-top:6px;padding-left:18px;}
.ok-box{background:#e6f9f0;color:#27ae60;border-left:4px solid #27ae60;padding:16px;border-radius:8px;text-align:center;font-size:14px;}
.ok-box a{display:inline-block;margin-top:12px;padding:10px 26px;background:#27ae60;color:#fff;border-radius:8px;text-decoration:none;font-weight:700;}
.btn{width:100%;padding:12px;background:linear-gradient(135deg,#e94560,#c0392b);color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;font-family:inherit;margin-top:6px;}
.btn:hover{opacity:.92;}
.strength-bar{height:4px;background:#eee;border-radius:4px;margin-top:5px;overflow:hidden;}
.strength-fill{height:100%;border-radius:4px;transition:width .3s,background .3s;width:0;}
.match-msg{font-size:11px;margin-top:3px;}
.login-lnk{text-align:center;font-size:13px;color:#666;margin-top:14px;}
.login-lnk a{color:#e94560;font-weight:700;text-decoration:none;}
</style>
</head>
<body>
<div class="wrap">
  <div class="logo"><div class="icon">🏪</div><h1>ShopStock</h1><p>Inventory Manager</p></div>
  <div class="card">
    <h2>Create Account ✨</h2>
    <p class="sub">Fill in the form to register a new account</p>

    <?php if ($success): ?>
    <div class="ok-box">
      <div style="font-size:30px">✅</div>
      <strong>Account created successfully!</strong>
      <br><a href="login.php">Sign In Now →</a>
    </div>
    <?php else: ?>

    <?php if (!empty($errors)): ?>
    <div class="err-box"><strong>⚠️ Please fix:</strong><ul><?php foreach($errors as $e): ?><li><?=htmlspecialchars($e)?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="POST" id="regForm">
      <div class="row2">
        <div class="fg"><label>Full Name *</label><input type="text" name="full_name" placeholder="Your full name" value="<?=htmlspecialchars($_POST['full_name']??'')?>" required></div>
        <div class="fg"><label>Email *</label><input type="email" name="email" placeholder="email@example.com" value="<?=htmlspecialchars($_POST['email']??'')?>" required></div>
      </div>
      <div class="row2">
        <div class="fg"><label>Username</label><input type="text" name="username" placeholder="Optional username" value="<?=htmlspecialchars($_POST['username']??'')?>"></div>
        <div class="fg"><label>Phone</label><input type="text" name="phone" placeholder="Phone number" value="<?=htmlspecialchars($_POST['phone']??'')?>"></div>
      </div>
      <div class="row2">
        <div class="fg">
          <label>Password *</label>
          <input type="password" name="password" id="pwd" placeholder="Min 6 characters" required>
          <div class="strength-bar"><div class="strength-fill" id="sf"></div></div>
        </div>
        <div class="fg">
          <label>Confirm Password *</label>
          <input type="password" name="confirm_password" id="cpwd" placeholder="Repeat password" required>
          <div class="match-msg" id="mm"></div>
        </div>
      </div>

      <div class="fg">
        <label>Account Role *</label>
        <div class="roles">
          <label class="role-opt <?=(($_POST['role']??'cashier')==='admin')?'sel':''?>" id="r_admin" onclick="pickRole('admin',this)">
            <input type="radio" name="role" value="admin" <?=(($_POST['role']??'')==='admin')?'checked':''?>>
            <span class="ri">🔑</span><div class="rn">Admin</div><div class="rd">Full access</div>
          </label>
          <label class="role-opt <?=(($_POST['role']??'cashier')==='manager')?'sel':''?>" id="r_manager" onclick="pickRole('manager',this)">
            <input type="radio" name="role" value="manager" <?=(($_POST['role']??'')==='manager')?'checked':''?>>
            <span class="ri">📦</span><div class="rn">Manager</div><div class="rd">Inventory</div>
          </label>
          <label class="role-opt <?=(($_POST['role']??'cashier')==='cashier'||!isset($_POST['role']))?'sel':''?>" id="r_cashier" onclick="pickRole('cashier',this)">
            <input type="radio" name="role" value="cashier" <?=(($_POST['role']??'cashier')==='cashier')?'checked':''?>>
            <span class="ri">🛒</span><div class="rn">Cashier</div><div class="rd">Sales</div>
          </label>
        </div>
      </div>

      <button type="submit" class="btn">🚀 Create Account</button>
    </form>
    <?php endif; ?>
    <div class="login-lnk">Already have an account? <a href="login.php">Sign In</a></div>
  </div>
</div>
<script>
function pickRole(v, el) {
    ['r_admin','r_manager','r_cashier'].forEach(id => document.getElementById(id).classList.remove('sel'));
    el.classList.add('sel');
    el.querySelector('input').checked = true;
}
document.getElementById('pwd').addEventListener('input', function() {
    var v=this.value,s=0;
    if(v.length>=6)s++;if(v.length>=10)s++;if(/[A-Z]/.test(v))s++;if(/[0-9]/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;
    var lvl=[{w:'0%',bg:'#eee'},{w:'25%',bg:'#e74c3c'},{w:'50%',bg:'#e67e22'},{w:'75%',bg:'#f1c40f'},{w:'100%',bg:'#27ae60'}];
    var l=lvl[Math.min(s,4)];
    var f=document.getElementById('sf');f.style.width=l.w;f.style.background=l.bg;
});
document.getElementById('cpwd').addEventListener('input', function() {
    var mm=document.getElementById('mm');
    if(!this.value){mm.textContent='';return;}
    if(this.value===document.getElementById('pwd').value){mm.textContent='✅ Passwords match';mm.style.color='#27ae60';}
    else{mm.textContent='❌ Passwords do not match';mm.style.color='#e74c3c';}
});
</script>
</body>
</html>
