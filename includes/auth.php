<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Determine login page path (works from any subfolder depth)
function login_redirect() {
    $depth = substr_count(str_replace('\\','/',dirname($_SERVER['SCRIPT_FILENAME'])), '/');
    // Just always redirect to absolute path
    header("Location: /inventory ms project/Inventort-ms-project/login.php");
    exit;
}

if (!isset($_SESSION['user_id'])) {
    login_redirect();
}

function is_admin()   { return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'; }
function is_manager() { return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'manager'; }
function is_cashier() { return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'cashier'; }
function is_admin_or_manager() { return is_admin() || is_manager(); }

function require_admin() {
    if (!is_admin()) {
        header("Location: /inventory ms project/Inventort-ms-project/login.php?error=access_denied");
        exit;
    }
}
function require_manager() {
    if (!is_admin() && !is_manager()) {
        header("Location: /inventory ms project/Inventort-ms-project/login.php?error=access_denied");
        exit;
    }
}

function get_role_label() {
    $map = ['admin'=>'System Administrator','manager'=>'Inventory Manager','cashier'=>'Sales Cashier'];
    return $map[$_SESSION['user_role']] ?? 'User';
}

function log_activity($conn, $action, $details = '') {
    $uid   = intval($_SESSION['user_id'] ?? 0);
    $uname = mysqli_real_escape_string($conn, $_SESSION['user_name'] ?? 'Unknown');
    $act   = mysqli_real_escape_string($conn, $action);
    $det   = mysqli_real_escape_string($conn, $details);
    $ip    = $_SERVER['REMOTE_ADDR'] ?? '';
    mysqli_query($conn, "INSERT INTO activity_logs (user_id,user_name,action,details,ip_address) VALUES ($uid,'$uname','$act','$det','$ip')");
}

function add_notification($conn, $title, $message, $type = 'info', $user_id = null) {
    $t   = mysqli_real_escape_string($conn, $title);
    $m   = mysqli_real_escape_string($conn, $message);
    $tp  = mysqli_real_escape_string($conn, $type);
    $uid = $user_id ? intval($user_id) : 'NULL';
    mysqli_query($conn, "INSERT INTO notifications (title,message,type,user_id) VALUES ('$t','$m','$tp',$uid)");
}

function get_unread_notifications($conn) {
    $r = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE is_read=0");
    return $r ? (mysqli_fetch_assoc($r)['cnt'] ?? 0) : 0;
}

function get_settings($conn) {
    $r = mysqli_query($conn, "SELECT * FROM settings WHERE id=1 LIMIT 1");
    if ($r && mysqli_num_rows($r) > 0) return mysqli_fetch_assoc($r);
    return ['shop_name'=>'ShopStock','currency_symbol'=>'$','tax_rate'=>0,'theme'=>'light','shop_address'=>'','shop_phone'=>''];
}
?>
