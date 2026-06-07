<?php
// Include this at the top of every protected page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Helper: check if current user is admin
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Block non-admin from a page
function require_admin() {
    if (!is_admin()) {
        header("Location: index.php?error=access_denied");
        exit;
    }
}
?>
