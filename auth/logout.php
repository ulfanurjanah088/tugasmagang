<?php
/**
 * =====================================================
 * FILE: auth/logout.php
 * FUNGSI: Proses Logout
 * =====================================================
 */

// 🔥 CEK SESSION - Jangan panggil session_start() jika sudah aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔥 Hapus semua session data
$_SESSION = array();

// 🔥 Hapus session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 🔥 Hapus custom cookie
setcookie('uid', '', time() - 3600, '/');
setcookie('user_email', '', time() - 3600, '/');
setcookie('user_data', '', time() - 3600, '/');

// 🔥 Destroy session
session_destroy();

// 🔥 Redirect ke login (tanpa output sebelum header)
header('Location: login.php');
exit;
?>
