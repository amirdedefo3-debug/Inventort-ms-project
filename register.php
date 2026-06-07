<?php
session_start();

// Already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'db.php';

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $role     = $_POST['role'] ?? 'staff';

    // Validation
    if (empty($name))               $errors[] = "Full name is required.";
    if (strlen($name) > 100)        $errors[] = "Name must be under 100 characters.";
    if (empty($email))              $errors[] = "Email address is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Please enter a valid email.";
    if (empty($password))           $errors[] = "Password is required.";
    if (strlen($password) < 6)      $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirm)     $errors[] = "Passwords do not match.";
    if (!in_array($role, ['admin','staff'])) $role = 'staff';

    // Check duplicate email
    if (empty($errors)) {
        $email_esc = mysqli_real_escape_string($conn, $email);
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email_esc'");
        if (mysqli_num_rows($check) > 0) {
            $errors[] = "An account with this email already exists.";
        }
    }

    if (empty($errors)) {
        $name_esc = mysqli_real_escape_string($conn, $name);
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (name, email, password, role)
                VALUES ('$name_esc', '$email_esc', '$hash', '$role')";

        if (mysqli_query($conn, $sql)) {
            $success = true;
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | ShopStock</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .reg-container {
            width: 100%;
            max-width: 460px;
        }

        .reg-logo {
            text-align: center;
            margin-bottom: 28px;
        }

        .reg-logo .icon { font-size: 46px; }
        .reg-logo h1 { color:#fff; font-size:26px; font-weight:700; margin-top:6px; }
        .reg-logo p  { color:rgba(255,255,255,0.5); font-size:12px; letter-spacing:2px; text-transform:uppercase; margin-top:4px; }

        .reg-card {
            background: #fff;
            border-radius: 16px;
            padding: 36px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.3);
        }

        .reg-card h2 { font-size:20px; color:#1a1a2e; margin-bottom:4px; }
        .reg-card .subtitle { color:#aaa; font-size:13px; margin-bottom:26px; }

        .form-group { margin-bottom:18px; }

        .form-group label {
            display:block;
            font-size:12px;
            font-weight:700;
            color:#555;
            text-transform:uppercase;
            letter-spacing:0.5px;
            margin-bottom:6px;
        }

        .input-wrap { position:relative; }
        .input-wrap .icon-left {
            position:absolute; left:13px; top:50%; transform:translateY(-50%); font-size:16px;
        }

        .form-group input,
        .form-group select {
            width:100%;
            padding:11px 14px 11px 40px;
            border:2px solid #e8e8e8;
            border-radius:8px;
            font-size:14px;
            color:#333;
            transition:border-color 0.2s;
            background:#fafafa;
            font-family:inherit;
        }

        .form-group select { padding-left: 40px; }

        .form-group input:focus,
        .form-group select:focus {
            outline:none; border-color:#e94560; background:#fff;
        }

        .form-group input.error { border-color:#e74c3c; }

        .toggle-pass {
            position:absolute; right:13px; top:50%; transform:translateY(-50%);
            cursor:pointer; font-size:16px; user-select:none;
        }

        .role-selector {
            display:grid;
            grid-template-columns: 1fr 1fr;
            gap:12px;
            margin-top:6px;
        }

        .role-option {
            border: 2px solid #e8e8e8;
            border-radius: 10px;
            padding: 14px;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
            position: relative;
        }

        .role-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0; height: 0;
        }

        .role-option .role-icon { font-size: 28px; display:block; margin-bottom:6px; }
        .role-option .role-name { font-size: 14px; font-weight: 700; color:#333; }
        .role-option .role-desc { font-size: 11px; color:#aaa; margin-top:3px; }

        .role-option.selected {
            border-color: #e94560;
            background: #fff5f6;
        }

        .role-option.selected .role-name { color:#e94560; }

        .alert-list {
            background:#fdecea;
            color:#e74c3c;
            border-left:4px solid #e74c3c;
            padding:12px 16px;
            border-radius:8px;
            font-size:13px;
            margin-bottom:20px;
        }

        .alert-list ul { margin-top:6px; padding-left:18px; }

        .alert-success {
            background:#e6f9f0;
            color:#27ae60;
            border-left:4px solid #27ae60;
            padding:16px;
            border-radius:8px;
            font-size:14px;
            text-align:center;
        }

        .alert-success a {
            display:inline-block;
            margin-top:12px;
            padding:10px 28px;
            background:#27ae60;
            color:#fff;
            border-radius:8px;
            text-decoration:none;
            font-weight:600;
        }

        .btn-register {
            width:100%;
            padding:13px;
            background:linear-gradient(135deg,#e94560,#c0392b);
            color:#fff;
            border:none;
            border-radius:8px;
            font-size:15px;
            font-weight:700;
            cursor:pointer;
            transition:opacity 0.2s, transform 0.1s;
            font-family:inherit;
            letter-spacing:0.5px;
        }

        .btn-register:hover { opacity:0.92; }
        .btn-register:active { transform:scale(0.98); }

        .login-link { text-align:center; font-size:14px; color:#666; margin-top:18px; }
        .login-link a { color:#e94560; font-weight:600; text-decoration:none; }
        .login-link a:hover { text-decoration:underline; }

        .strength-bar {
            height:4px; border-radius:4px;
            background:#eee; margin-top:6px; overflow:hidden;
        }
        .strength-fill { height:100%; border-radius:4px; transition:width 0.3s, background 0.3s; width:0%; }
        .strength-label { font-size:11px; color:#aaa; margin-top:3px; }
    </style>
</head>
<body>

<div class="reg-container">
    <div class="reg-logo">
        <div class="icon">🏪</div>
        <h1>ShopStock</h1>
        <p>Inventory Manager</p>
    </div>

    <div class="reg-card">
        <h2>Create Account ✨</h2>
        <p class="subtitle">Fill in the details below to register</p>

        <?php if (!empty($errors)): ?>
        <div class="alert-list">
            <strong>⚠️ Please fix the following:</strong>
            <ul>
                <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if (isset($success) && $success): ?>
        <div class="alert-success">
            <div style="font-size:32px;">✅</div>
            <strong>Account created successfully!</strong>
            <p style="color:#555; font-size:13px; margin-top:6px;">You can now log in with your credentials.</p>
            <a href="login.php">Go to Login →</a>
        </div>
        <?php else: ?>

        <form method="POST" id="regForm">
            <div class="form-group">
                <label>Full Name *</label>
                <div class="input-wrap">
                    <span class="icon-left">👤</span>
                    <input type="text" name="name" id="name" placeholder="Your full name"
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" maxlength="100" required>
                </div>
            </div>

            <div class="form-group">
                <label>Email Address *</label>
                <div class="input-wrap">
                    <span class="icon-left">📧</span>
                    <input type="email" name="email" id="reg_email" placeholder="you@example.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Password *</label>
                <div class="input-wrap">
                    <span class="icon-left">🔒</span>
                    <input type="password" name="password" id="reg_password" placeholder="Min. 6 characters" required>
                    <span class="toggle-pass" onclick="togglePass('reg_password','t1')" id="t1">👁️</span>
                </div>
                <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                <div class="strength-label" id="strengthLabel"></div>
            </div>

            <div class="form-group">
                <label>Confirm Password *</label>
                <div class="input-wrap">
                    <span class="icon-left">🔒</span>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Repeat password" required>
                    <span class="toggle-pass" onclick="togglePass('confirm_password','t2')" id="t2">👁️</span>
                </div>
                <small id="matchMsg" style="font-size:12px;"></small>
            </div>

            <div class="form-group">
                <label>Account Role *</label>
                <div class="role-selector">
                    <label class="role-option <?= (($_POST['role'] ?? 'staff') == 'admin') ? 'selected' : '' ?>" id="roleAdmin">
                        <input type="radio" name="role" value="admin" <?= (($_POST['role'] ?? '') == 'admin') ? 'checked' : '' ?> onchange="selectRole('admin')">
                        <span class="role-icon">🔑</span>
                        <div class="role-name">Admin</div>
                        <div class="role-desc">Full access</div>
                    </label>
                    <label class="role-option <?= (($_POST['role'] ?? 'staff') == 'staff' || !isset($_POST['role'])) ? 'selected' : '' ?>" id="roleStaff">
                        <input type="radio" name="role" value="staff" <?= (($_POST['role'] ?? 'staff') == 'staff') ? 'checked' : '' ?> onchange="selectRole('staff')">
                        <span class="role-icon">👤</span>
                        <div class="role-name">Staff</div>
                        <div class="role-desc">View only</div>
                    </label>
                </div>
            </div>

            <button type="submit" class="btn-register">🚀 Create Account</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Sign in</a>
        </div>

        <?php endif; ?>
    </div>
</div>

<script>
function togglePass(id, iconId) {
    const f = document.getElementById(id);
    const i = document.getElementById(iconId);
    f.type = f.type === 'password' ? 'text' : 'password';
    i.textContent = f.type === 'password' ? '👁️' : '🙈';
}

function selectRole(role) {
    document.getElementById('roleAdmin').classList.toggle('selected', role === 'admin');
    document.getElementById('roleStaff').classList.toggle('selected', role === 'staff');
}

// Password strength
document.getElementById('reg_password').addEventListener('input', function() {
    const val = this.value;
    const fill = document.getElementById('strengthFill');
    const label = document.getElementById('strengthLabel');
    let strength = 0;
    if (val.length >= 6)  strength++;
    if (val.length >= 10) strength++;
    if (/[A-Z]/.test(val)) strength++;
    if (/[0-9]/.test(val)) strength++;
    if (/[^A-Za-z0-9]/.test(val)) strength++;

    const levels = [
        { w: '0%',   bg: '#eee',     text: '' },
        { w: '25%',  bg: '#e74c3c',  text: 'Weak' },
        { w: '50%',  bg: '#e67e22',  text: 'Fair' },
        { w: '75%',  bg: '#f1c40f',  text: 'Good' },
        { w: '100%', bg: '#27ae60',  text: 'Strong' },
    ];
    const lvl = levels[Math.min(strength, 4)];
    fill.style.width = lvl.w;
    fill.style.background = lvl.bg;
    label.textContent = lvl.text;
    label.style.color = lvl.bg;
});

// Password match check
document.getElementById('confirm_password').addEventListener('input', function() {
    const pass = document.getElementById('reg_password').value;
    const msg  = document.getElementById('matchMsg');
    if (this.value === '') { msg.textContent = ''; return; }
    if (this.value === pass) {
        msg.textContent = '✅ Passwords match';
        msg.style.color = '#27ae60';
    } else {
        msg.textContent = '❌ Passwords do not match';
        msg.style.color = '#e74c3c';
    }
});
</script>

</body>
</html>
