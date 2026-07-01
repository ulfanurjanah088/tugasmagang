<?php
/**
 * =====================================================
 * FILE: api/index.php
 * FUNGSI: Router untuk Vercel
 * VERSION: 5.0 - Full Fix
 * =====================================================
 */

// 🔥 START SESSION - HANYA SEKALI
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔥 Ambil path dari URL
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);
$path = ltrim($path, '/');

// 🔥 Jika path kosong, arahkan ke login
if (empty($path)) {
    require_once __DIR__ . '/../auth/login.php';
    exit;
}

// 🔥 Jika path adalah asset (css, js, images)
if (strpos($path, 'assets/') === 0) {
    $file = __DIR__ . '/../' . $path;
    if (file_exists($file) && is_file($file)) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'eot' => 'application/vnd.ms-fontobject',
        ];
        $mime = $mimeTypes[$ext] ?? 'application/octet-stream';
        header('Content-Type: ' . $mime);
        readfile($file);
        exit;
    }
}

// 🔥 Cek apakah ini halaman login/logout (tanpa session check)
$isAuthPage = $path === 'login' || $path === 'login.php' || 
              $path === 'auth/login' || $path === 'auth/login.php' ||
              $path === 'logout' || $path === 'logout.php' ||
              $path === 'auth/logout' || $path === 'auth/logout.php';

// 🔥 Jika belum login dan bukan auth page, redirect ke login
if (!$isAuthPage && empty($_SESSION['uid']) && empty($_COOKIE['uid'])) {
    header('Location: /login');
    exit;
}

// 🔥 Mapping file
$fileMap = [
    // Auth
    'login' => __DIR__ . '/../auth/login.php',
    'login.php' => __DIR__ . '/../auth/login.php',
    'auth/login' => __DIR__ . '/../auth/login.php',
    'auth/login.php' => __DIR__ . '/../auth/login.php',
    'logout' => __DIR__ . '/../auth/logout.php',
    'logout.php' => __DIR__ . '/../auth/logout.php',
    'auth/logout' => __DIR__ . '/../auth/logout.php',
    'auth/logout.php' => __DIR__ . '/../auth/logout.php',
    
    // User
    'home' => __DIR__ . '/../user/index.php',
    'home.php' => __DIR__ . '/../user/index.php',
    'user/index' => __DIR__ . '/../user/index.php',
    'user/index.php' => __DIR__ . '/../user/index.php',
    'riwayat' => __DIR__ . '/../user/riwayat.php',
    'riwayat.php' => __DIR__ . '/../user/riwayat.php',
    'user/riwayat' => __DIR__ . '/../user/riwayat.php',
    'user/riwayat.php' => __DIR__ . '/../user/riwayat.php',
    'profile' => __DIR__ . '/../user/profile.php',
    'profile.php' => __DIR__ . '/../user/profile.php',
    'user/profile' => __DIR__ . '/../user/profile.php',
    'user/profile.php' => __DIR__ . '/../user/profile.php',
    
    // Admin
    'admin' => __DIR__ . '/../admin/index.php',
    'admin.php' => __DIR__ . '/../admin/index.php',
    'admin/index' => __DIR__ . '/../admin/index.php',
    'admin/index.php' => __DIR__ . '/../admin/index.php',
    'admin_riwayat' => __DIR__ . '/../admin/riwayat.php',
    'admin_riwayat.php' => __DIR__ . '/../admin/riwayat.php',
    'admin/riwayat' => __DIR__ . '/../admin/riwayat.php',
    'admin/riwayat.php' => __DIR__ . '/../admin/riwayat.php',
    'admin_laporan' => __DIR__ . '/../admin/laporan.php',
    'admin_laporan.php' => __DIR__ . '/../admin/laporan.php',
    'admin/laporan' => __DIR__ . '/../admin/laporan.php',
    'admin/laporan.php' => __DIR__ . '/../admin/laporan.php',
    'admin_cetak_pdf' => __DIR__ . '/../admin/cetak_pdf.php',
    'admin_cetak_pdf.php' => __DIR__ . '/../admin/cetak_pdf.php',
    'admin/cetak_pdf' => __DIR__ . '/../admin/cetak_pdf.php',
    'admin/cetak_pdf.php' => __DIR__ . '/../admin/cetak_pdf.php',
    
    // AJAX
    'ajax/mark_read' => __DIR__ . '/../ajax/mark_read.php',
    'ajax/mark_read.php' => __DIR__ . '/../ajax/mark_read.php',
    'ajax/mark_all_read' => __DIR__ . '/../ajax/mark_all_read.php',
    'ajax/mark_all_read.php' => __DIR__ . '/../ajax/mark_all_read.php',
    'ajax/delete_notif' => __DIR__ . '/../ajax/delete_notif.php',
    'ajax/delete_notif.php' => __DIR__ . '/../ajax/delete_notif.php',
    'ajax/get_notif_count' => __DIR__ . '/../ajax/get_notif_count.php',
    'ajax/get_notif_count.php' => __DIR__ . '/../ajax/get_notif_count.php',
];

// 🔥 Jika path ada di fileMap
if (isset($fileMap[$path])) {
    require_once $fileMap[$path];
    exit;
}

// 🔥 Jika path adalah file langsung di root
$rootFile = __DIR__ . '/../' . $path;
if (file_exists($rootFile) && is_file($rootFile) && strpos($path, '.php') !== false) {
    require_once $rootFile;
    exit;
}

// 🔥 Jika tidak ada yang cocok, redirect ke login
header('Location: /login');
exit;
?>
