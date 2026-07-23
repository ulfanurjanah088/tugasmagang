<?php
/**
 * =====================================================
 * FILE: includes/header.php
 * FUNGSI: Header/Topbar untuk semua halaman
 * VERSION: FINAL - Mobile Nav Fixed
 * =====================================================
 */

if (!isset($user)) {
    $user = $_SESSION['user'] ?? null;
}

if (!isset($currentPage)) {
    $currentPage = 'home';
}

if (!isset($uid)) {
    $uid = $_SESSION['uid'] ?? null;
}

$isAdmin = isset($user['role']) && $user['role'] === 'admin';

// Load notifikasi
if (isset($database) && isset($uid)) {
    require_once __DIR__ . '/notifikasi.php';
    $notifManager = new NotifikasiManager($database, $uid, $isAdmin);
    $unreadCount = $notifManager->getUnreadCount();
    $recentNotif = $notifManager->getRecentNotifikasi(5);
} else {
    $unreadCount = 0;
    $recentNotif = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>sistem.perizinan.cuti — Manajemen Cuti Karyawan</title>
    
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.min.css">
    
    <style>
        /* ============================================
           TOPBAR
           ============================================ */
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
            height: 56px;
            background: #fff;
            border-bottom: 1px solid #e5e5e5;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .topbar-logo {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            font-size: 16px;
            color: #1a1a1a;
            flex-shrink: 0;
        }
        .topbar-logo span { color: #B8860B; }
        
        .topbar-nav {
            display: flex;
            align-items: center;
            gap: 2px;
        }
        .topbar-nav a {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            color: #888;
            text-decoration: none;
            transition: all 0.2s;
        }
        .topbar-nav a:hover,
        .topbar-nav a.active {
            color: #1a1a1a;
            background: #f5f5f0;
        }
        
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }
        .topbar-search {
            display: none;
            align-items: center;
            gap: 6px;
            background: #f5f5f0;
            border: 1px solid #e5e5e5;
            border-radius: 20px;
            padding: 4px 12px;
        }
        .topbar-search input {
            border: none;
            background: none;
            font-size: 13px;
            outline: none;
            width: 120px;
        }
        .admin-mode-badge {
            display: flex;
            align-items: center;
            gap: 4px;
            background: rgba(184,134,11,0.12);
            color: #B8860B;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 12px;
            border: 1px solid rgba(184,134,11,0.2);
        }
        .user-info {
            text-align: right;
            display: none;
        }
        .user-info strong { font-size: 12px; font-weight: 600; display: block; }
        .user-info small { font-size: 10px; color: #888; }
        
        .notif-btn-wrapper {
            position: relative;
            display: inline-block;
            z-index: 1001;
        }
        .notif-btn {
            position: relative;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #f5f5f0;
            border: 1px solid #e5e5e5;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            cursor: pointer;
        }
        .notif-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: #C0392B;
            color: #fff;
            font-size: 9px;
            font-weight: 700;
            min-width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
            border: 2px solid #fff;
        }
        .topbar-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #B8860B;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
            flex-shrink: 0;
        }
        
        /* ============================================
           NOTIFIKASI DROPDOWN
           ============================================ */
        .notif-dropdown {
            display: none;
            position: absolute;
            top: 42px;
            right: 0;
            width: 320px;
            max-height: 380px;
            overflow-y: auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
            border: 1px solid #e5e5e5;
            z-index: 1000;
        }
        .notif-dropdown.active {
            display: block !important;
        }
        .notif-dropdown-header {
            padding: 10px 14px;
            border-bottom: 1px solid #e5e5e5;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
            font-size: 13px;
            position: sticky;
            top: 0;
            background: #fff;
            border-radius: 12px 12px 0 0;
            z-index: 5;
        }
        .notif-dropdown-header .mark-all {
            font-size: 11px;
            color: #B8860B;
            cursor: pointer;
        }
        .notif-item {
            padding: 10px 14px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            transition: background 0.2s;
        }
        .notif-item:hover { background: #f8f8f8; }
        .notif-item.unread { background: rgba(184,134,11,0.04); border-left: 3px solid #B8860B; }
        .notif-item .notif-icon {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 12px;
        }
        .notif-item .notif-icon.success { background: #E8F5EE; color: #2D7A4F; }
        .notif-item .notif-icon.warning { background: #FEF9E7; color: #B8860B; }
        .notif-item .notif-icon.danger { background: #FDECEA; color: #C0392B; }
        .notif-item .notif-icon.info { background: #f5f5f0; color: #888; }
        .notif-item .notif-body { flex: 1; min-width: 0; }
        .notif-item .notif-body .notif-judul {
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 1px;
        }
        .notif-item .notif-body .notif-pesan {
            font-size: 11px;
            color: #888;
            line-height: 1.3;
            word-wrap: break-word;
        }
        .notif-item .notif-body .notif-waktu {
            font-size: 10px;
            color: #aaa;
            margin-top: 2px;
        }
        .notif-empty {
            padding: 30px 20px;
            text-align: center;
            color: #888;
        }
        .notif-empty i { font-size: 32px; display: block; margin-bottom: 8px; color: #ddd; }
        
        /* ============================================
           MOBILE NAVBAR - FIX
           ============================================ */
        .mobile-nav-bar {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #fff;
            border-top: 1px solid #e5e5e5;
            z-index: 200;
            padding: 4px 0 env(safe-area-inset-bottom);
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        }
        .mobile-nav-bar .mobile-nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1px;
            padding: 6px 2px;
            font-size: 8px;
            font-weight: 600;
            color: #888;
            cursor: pointer;
            border: none;
            background: none;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            transition: color 0.2s;
            position: relative;
        }
        .mobile-nav-bar .mobile-nav-item i {
            font-size: 18px;
            transition: color 0.2s;
        }
        .mobile-nav-bar .mobile-nav-item.active {
            color: #1a1a1a;
        }
        .mobile-nav-bar .mobile-nav-item.active i {
            color: #B8860B;
        }
        .mobile-nav-bar .mobile-nav-item .nav-badge {
            position: absolute;
            top: 2px;
            right: 50%;
            transform: translateX(18px);
            background: #C0392B;
            color: #fff;
            font-size: 7px;
            font-weight: 700;
            min-width: 14px;
            height: 14px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 3px;
        }

        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (min-width: 769px) {
            .mobile-nav-bar { display: none !important; }
            .topbar-search { display: flex; }
            .user-info { display: block; }
        }
        
        @media (max-width: 768px) {
            .topbar { padding: 0 12px; height: 50px; }
            .topbar-logo { font-size: 14px; }
            .topbar-nav { display: none !important; }
            .topbar-search { display: none !important; }
            .user-info { display: none !important; }
            .admin-mode-badge { font-size: 10px; padding: 2px 8px; }
            .notif-btn { width: 32px; height: 32px; font-size: 14px; }
            .topbar-avatar { width: 28px; height: 28px; font-size: 10px; }
            .notif-dropdown { width: 280px; right: -40px; }
            
            .mobile-nav-bar {
                display: flex !important;
            }
            .page-with-mobile-nav .main-content {
                padding-bottom: 72px !important;
            }
        }
        
        @media (max-width: 400px) {
            .notif-dropdown { width: 240px; right: -20px; }
            .notif-item { padding: 8px 10px; }
            .notif-item .notif-body .notif-judul { font-size: 11px; }
            .notif-item .notif-body .notif-pesan { font-size: 10px; }
        }
    </style>
</head>
<body>

<!-- =====================================================
     TOPBAR
     ===================================================== -->
<header class="topbar">
    <div class="topbar-logo">Sistem Perizinan<span>Cuti</span></div>
    
    <?php if ($isAdmin): ?>
    <nav class="topbar-nav">
        <a href="../admin/index.php" class="<?= $currentPage === 'admin' ? 'active' : '' ?>">Review</a>
        <a href="../admin/riwayat.php" class="<?= $currentPage === 'admin-riwayat' ? 'active' : '' ?>">Riwayat</a>
        <a href="../admin/laporan.php" class="<?= $currentPage === 'admin-laporan' ? 'active' : '' ?>">Laporan</a>
    </nav>
    <?php else: ?>
    <nav class="topbar-nav">
        <a href="../user/index.php" class="<?= $currentPage === 'home' ? 'active' : '' ?>">Dashboard</a>
        <a href="../user/index.php#ajukan-cuti" class="<?= $currentPage === 'ajukan' ? 'active' : '' ?>">Ajukan</a>
        <a href="../user/riwayat.php" class="<?= $currentPage === 'riwayat' ? 'active' : '' ?>">Riwayat</a>
    </nav>
    <?php endif; ?>
    
    <div class="topbar-right">
        <div class="topbar-search">
            <i class="ri-search-line" style="color:#888;font-size:14px;"></i>
            <input type="text" placeholder="Cari...">
        </div>
        
        <?php if ($isAdmin): ?>
        <div class="admin-mode-badge">
            <i class="ri-settings-3-line"></i> Admin
        </div>
        <?php endif; ?>
        
        <div class="notif-btn-wrapper">
            <button class="notif-btn" id="notifToggle" onclick="toggleNotif()">
                <i class="ri-notification-3-line"></i>
                <?php if ($unreadCount > 0): ?>
                    <span class="notif-badge"><?= $unreadCount > 99 ? '99+' : $unreadCount ?></span>
                <?php endif; ?>
            </button>
            
            <div class="notif-dropdown" id="notifDropdown">
                <div class="notif-dropdown-header">
                    <span>Notifikasi</span>
                    <?php if (!empty($recentNotif)): ?>
                        <span class="mark-all" onclick="markAllNotif()">Tandai semua dibaca</span>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($recentNotif)): ?>
                    <div class="notif-empty">
                        <i class="ri-inbox-line"></i>
                        <p>Belum ada notifikasi</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentNotif as $key => $notif): ?>
                        <?php if (!is_array($notif)) continue; ?>
                        <div class="notif-item <?= ($notif['dibaca'] ?? false) ? '' : 'unread' ?>" 
                             onclick="clickNotif('<?= $key ?>', '<?= $notif['link'] ?? '#' ?>')">
                            <div class="notif-icon <?= $notif['type'] ?? 'info' ?>">
                                <i class="ri-notification-3-line"></i>
                            </div>
                            <div class="notif-body">
                                <div class="notif-judul"><?= escape($notif['judul'] ?? 'Notifikasi') ?></div>
                                <div class="notif-pesan"><?= escape($notif['pesan'] ?? '') ?></div>
                                <div class="notif-waktu"><?= formatTanggalWaktu($notif['created_at'] ?? '') ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="user-info">
            <strong><?= escape($user['name'] ?? 'User') ?></strong>
            <small><?= escape($user['jabatan'] ?? 'Karyawan') ?></small>
        </div>
        
        <div class="topbar-avatar" onclick="window.location.href='../user/profile.php'">
            <?= strtoupper(substr($user['name'] ?? 'U', 0, 2)) ?>
        </div>
    </div>
</header>

<!-- =====================================================
     MOBILE NAVBAR - FIXED PATH
     ===================================================== -->
<nav class="mobile-nav-bar">
    <?php if ($isAdmin): ?>
        <!-- 🔥 ADMIN MOBILE NAV -->
        <button class="mobile-nav-item <?= $currentPage === 'admin' ? 'active' : '' ?>" onclick="window.location.href='../admin/index.php'">
            <i class="ri-file-search-line"></i>
            <span>Review</span>
        </button>
        <button class="mobile-nav-item <?= $currentPage === 'admin-riwayat' ? 'active' : '' ?>" onclick="window.location.href='../admin/riwayat.php'">
            <i class="ri-history-line"></i>
            <span>Riwayat</span>
        </button>
        <button class="mobile-nav-item <?= $currentPage === 'admin-laporan' ? 'active' : '' ?>" onclick="window.location.href='../admin/laporan.php'">
            <i class="ri-file-chart-line"></i>
            <span>Laporan</span>
        </button>
        <button class="mobile-nav-item <?= $currentPage === 'profile' ? 'active' : '' ?>" onclick="window.location.href='../user/profile.php'">
            <i class="ri-user-line"></i>
            <span>Profil</span>
        </button>
        <button class="mobile-nav-item" onclick="window.location.href='../auth/logout.php'">
            <i class="ri-logout-box-line"></i>
            <span>Keluar</span>
        </button>
    <?php else: ?>
        <!-- 🔥 USER MOBILE NAV -->
        <button class="mobile-nav-item <?= $currentPage === 'home' ? 'active' : '' ?>" onclick="window.location.href='../user/index.php'">
            <i class="ri-dashboard-line"></i>
            <span>Dashboard</span>
        </button>
        <button class="mobile-nav-item" onclick="window.location.href='../user/index.php#ajukan-cuti'">
            <i class="ri-add-circle-line"></i>
            <span>Ajukan</span>
        </button>
        <button class="mobile-nav-item <?= $currentPage === 'riwayat' ? 'active' : '' ?>" onclick="window.location.href='../user/riwayat.php'">
            <i class="ri-history-line"></i>
            <span>Riwayat</span>
            <?php if (isset($stats['menunggu']) && $stats['menunggu'] > 0): ?>
                <span class="nav-badge"><?= $stats['menunggu'] ?></span>
            <?php endif; ?>
        </button>
        <button class="mobile-nav-item <?= $currentPage === 'profile' ? 'active' : '' ?>" onclick="window.location.href='../user/profile.php'">
            <i class="ri-user-line"></i>
            <span>Profil</span>
        </button>
        <button class="mobile-nav-item" onclick="window.location.href='../auth/logout.php'">
            <i class="ri-logout-box-line"></i>
            <span>Keluar</span>
        </button>
    <?php endif; ?>
</nav>

<!-- =====================================================
     NOTIFIKASI SCRIPT
     ===================================================== -->
<script>
function toggleNotif() {
    const dropdown = document.getElementById('notifDropdown');
    if (dropdown) dropdown.classList.toggle('active');
}

document.addEventListener('click', function(e) {
    const wrapper = document.querySelector('.notif-btn-wrapper');
    if (wrapper && !wrapper.contains(e.target)) {
        const dropdown = document.getElementById('notifDropdown');
        if (dropdown) dropdown.classList.remove('active');
    }
});

function clickNotif(id, link) {
    fetch('../ajax/mark_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id
    })
    .then(() => { if (link && link !== '#') window.location.href = link; else location.reload(); })
    .catch(() => { if (link && link !== '#') window.location.href = link; });
}

function markAllNotif() {
    fetch('../ajax/mark_all_read.php', { method: 'POST' })
    .then(() => location.reload())
    .catch(() => location.reload());
}

setInterval(function() {
    fetch('../ajax/get_notif_count.php')
    .then(response => response.json())
    .then(data => {
        const badge = document.querySelector('.notif-badge');
        if (data.count > 0) {
            if (badge) {
                badge.textContent = data.count > 99 ? '99+' : data.count;
            } else {
                const btn = document.querySelector('.notif-btn');
                if (btn) {
                    btn.innerHTML += `<span class="notif-badge">${data.count > 99 ? '99+' : data.count}</span>`;
                }
            }
        } else {
            if (badge) badge.remove();
        }
    });
}, 30000);
</script>
