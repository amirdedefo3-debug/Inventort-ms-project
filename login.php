<?php
session_start();

// Already logged in → go to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $email_esc = mysqli_real_escape_string($conn, $email);
        $result = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email_esc'");

        if ($result && mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            if (password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email']= $user['email'];

                header("Location: index.php");
                exit;
            } else {
                $error = "Incorrect password. Please try again.";
            }
        } else {
            $error = "No account found with that email.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | ShopStock</title>
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

        .login-container {
            width: 100%;
            max-width: 440px;
        }

        .login-logo {
            text-align: center;
            margin-bottom: 32px;
        }

        .login-logo .icon {
            font-size: 52px;
        }

        .login-logo h1 {
            color: #fff;
            font-size: 28px;
            font-weight: 700;
            margin-top: 8px;
        }

        .login-logo p {
            color: rgba(255,255,255,0.5);
            font-size: 13px;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 4px;
        }

        .login-card {
            background: #fff;
            border-radius: 16px;
            padding: 36px;
            box-shadow: 0 24px 60px rgba(0,0,0,0.3);
        }

        .login-card h2 {
            font-size: 20px;
            color: #1a1a2e;
            margin-bottom: 6px;
        }

        .login-card .subtitle {
            color: #aaa;
            font-size: 13px;
            margin-bottom: 28px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 7px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap span {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px 12px 40px;
            border: 2px solid #e8e8e8;
            border-radius: 8px;
            font-size: 14px;
            color: #333;
            transition: border-color 0.2s;
            background: #fafafa;
            font-family: inherit;
        }

        .form-group input:focus {
            outline: none;
            border-color: #e94560;
            background: #fff;
        }

        .toggle-pass {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 16px;
            user-select: none;
        }

        .alert-error {
            background: #fdecea;
            color: #e74c3c;
            border-left: 4px solid #e74c3c;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #e94560, #c0392b);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.1s;
            font-family: inherit;
            letter-spacing: 0.5px;
        }

        .btn-login:hover { opacity: 0.92; }
        .btn-login:active { transform: scale(0.98); }

        .divider {
            text-align: center;
            color: #ccc;
            font-size: 13px;
            margin: 20px 0;
            position: relative;
        }

        .divider::before, .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 42%;
            height: 1px;
            background: #eee;
        }
        .divider::before { left: 0; }
        .divider::after  { right: 0; }

        .register-link {
            text-align: center;
            font-size: 14px;
            color: #666;
        }

        .register-link a {
            color: #e94560;
            font-weight: 600;
            text-decoration: none;
        }

        .register-link a:hover { text-decoration: underline; }

        .demo-accounts {
            margin-top: 24px;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 14px;
            font-size: 12px;
            color: #888;
        }

        .demo-accounts strong {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .demo-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px solid #eee;
        }

        .demo-row:last-child { border-bottom: none; }

        .demo-fill {
            background: none;
            border: none;
            color: #3498db;
            cursor: pointer;
            font-size: 11px;
            font-weight: 600;
            padding: 0;
        }

        .demo-fill:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-logo">
        <div class="icon">🏪</div>
        <h1>ShopStock</h1>
        <p>Inventory Manager</p>
    </div>

    <div class="login-card">
        <h2>Welcome back 👋</h2>
        <p class="subtitle">Sign in to your account to continue</p>

        <?php if ($error): ?>
        <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'logged_out'): ?>
        <div style="background:#e6f9f0;color:#27ae60;border-left:4px solid #27ae60;padding:12px 16px;border-radius:8px;font-size:14px;margin-bottom:20px;">
            ✅ You have been logged out successfully.
        </div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <div class="form-group">
                <label>Email Address</label>
                <div class="input-wrap">
                    <span>📧</span>
                    <input type="email" name="email" id="email" placeholder="you@example.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="input-wrap">
                    <span>🔒</span>
                    <input type="password" name="password" id="password" placeholder="Enter your password" required>
                    <span class="toggle-pass" onclick="togglePass()" id="toggleIcon">👁️</span>
                </div>
            </div>

            <button type="submit" class="btn-login">🔐 Sign In</button>
        </form>

        <div class="divider">or</div>

        <div class="register-link">
            Don't have an account? <a href="register.php">Create account</a>
        </div>

        <!-- Demo accounts quick fill -->
        <div class="demo-accounts">
            <strong>🧪 Demo Accounts</strong>
            <div class="demo-row">
                <span>🔑 Admin — admin@shop.com / admin123</span>
                <button class="demo-fill" onclick="fillDemo('admin@shop.com','admin123')">Fill</button>
            </div>
            <div class="demo-row">
                <span>👤 Staff — staff@shop.com / staff123</span>
                <button class="demo-fill" onclick="fillDemo('staff@shop.com','staff123')">Fill</button>
            </div>
        </div>
    </div>
</div>

<script>
function togglePass() {
    const pwd = document.getElementById('password');
    const icon = document.getElementById('toggleIcon');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.textContent = '🙈';
    } else {
        pwd.type = 'password';
        icon.textContent = '👁️';
    }
}

function fillDemo(email, pass) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = pass;
}
</script>

</body>
</html>
