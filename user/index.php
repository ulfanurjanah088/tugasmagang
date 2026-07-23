<?php
/**
 * =====================================================
 * FILE: home.php
 * FUNGSI: Dashboard User
 * VERSION: FINAL - Mobile Fix
 * =====================================================
 */

require_once __DIR__ . '/../config/firebase.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notifikasi.php';
require_once __DIR__ . '/../config/supabase.php';

if (!$auth->isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}


requireLogin($auth);

$user = $auth->getCurrentUser();
$uid = $auth->getCurrentUid();
$database = FirebaseConfig::getDatabase();

// =====================================================
// PROSES AJUKAN CUTI
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajukan_cuti'])) {
    $jenis_cuti = $_POST['jenis_cuti'] ?? '';
    $tanggal_mulai = $_POST['tanggal_mulai'] ?? '';
    $tanggal_selesai = $_POST['tanggal_selesai'] ?? '';
    $alasan = trim($_POST['alasan'] ?? '');
    $dokumen = $_FILES['dokumen'] ?? null;
    
    $errors = [];
    if (empty($jenis_cuti)) $errors[] = 'Jenis cuti harus dipilih';
    if (empty($tanggal_mulai)) $errors[] = 'Tanggal mulai harus diisi';
    if (empty($tanggal_selesai)) $errors[] = 'Tanggal selesai harus diisi';
    if (strtotime($tanggal_mulai) > strtotime($tanggal_selesai)) {
        $errors[] = 'Tanggal mulai harus sebelum tanggal selesai';
    }
    if (empty($alasan)) $errors[] = 'Alasan cuti harus diisi';
    
    $sisaCuti = getSisaCuti($uid, $database);
    $start = new DateTime($tanggal_mulai);
    $end = new DateTime($tanggal_selesai);
    $durasi = $start->diff($end)->days + 1;
    
    if ($durasi > $sisaCuti) {
        $errors[] = "Sisa cuti Anda $sisaCuti hari, namun Anda mengajukan $durasi hari";
    }
    
    if (empty($errors)) {
        if (empty($user['name']) || empty($user['nip']) || empty($user['jabatan'])) {
            $userData = $database->getReference('users/' . $uid)->getValue();
            if (is_array($userData)) {
                $user = array_merge($user, $userData);
                $_SESSION['user'] = $user;
            }
        }
        
        $cutiData = [
            'id' => generateCutiId(),
            'user_id' => $uid,
            'user_name' => $user['name'] ?? 'User',
            'user_email' => $user['email'] ?? '',
            'nip' => $user['nip'] ?? '',
            'jabatan' => $user['jabatan'] ?? '',
            'departemen' => $user['departemen'] ?? '',
            'jenis_cuti' => $jenis_cuti,
            'tanggal_mulai' => $tanggal_mulai,
            'tanggal_selesai' => $tanggal_selesai,
            'durasi' => $durasi,
            'alasan' => $alasan,
            'status' => 'Menunggu',
            'dokumen' => '',
            'catatan_admin' => '',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($dokumen && $dokumen['error'] === 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
            $maxSize = 5 * 1024 * 1024;
            
            if (!in_array($dokumen['type'], $allowedTypes)) {
                $errors[] = 'Format file tidak didukung. Gunakan PDF, JPG, atau PNG';
            } elseif ($dokumen['size'] > $maxSize) {
                $errors[] = 'Ukuran file maksimal 5MB';
            } else {
                $extension = pathinfo($dokumen['name'], PATHINFO_EXTENSION);
                $fileName = $uid . '_' . date('Ymd_His') . '.' . $extension;
                $destinationPath = 'cuti/' . $fileName;
                
                $fileUrl = SupabaseConfig::uploadFile($dokumen['tmp_name'], $destinationPath);
                if ($fileUrl) {
                    $cutiData['dokumen'] = $fileUrl;
                } else {
                    $errors[] = 'Gagal upload: ' . SupabaseConfig::getLastError();
                }
            }
        }
        
        if (empty($errors)) {
            try {
                $database->getReference('permohonan')->push($cutiData);
                NotifikasiManager::notifikasiPengajuanBaru($database, $cutiData);
                $_SESSION['flash_message'] = 'Pengajuan cuti berhasil dikirim!';
                redirect('home.php');
            } catch (Exception $e) {
                $error = 'Gagal menyimpan data: ' . $e->getMessage();
            }
        } else {
            $error = implode(', ', $errors);
        }
    } else {
        $error = implode(', ', $errors);
    }
}

// =====================================================
// AMBIL DATA
// =====================================================

$allPermohonan = $database->getReference('permohonan')->getValue();
$permohonan = [];

if (is_array($allPermohonan) && !empty($allPermohonan)) {
    foreach ($allPermohonan as $key => $izin) {
        if (!is_array($izin)) continue;
        if (($izin['user_id'] ?? '') === $uid) {
            $permohonan[$key] = $izin;
        }
    }
}

$stats = ['total' => 0, 'menunggu' => 0, 'disetujui' => 0, 'ditolak' => 0, 'selesai' => 0];
$recentActivities = [];

if (is_array($permohonan) && !empty($permohonan)) {
    foreach ($permohonan as $key => $izin) {
        if (!is_array($izin)) continue;
        $izin['_key'] = $key;
        $stats['total']++;
        $status = $izin['status'] ?? 'Menunggu';
        switch ($status) {
            case 'Menunggu': $stats['menunggu']++; break;
            case 'Disetujui': $stats['disetujui']++; break;
            case 'Ditolak': $stats['ditolak']++; break;
            case 'Selesai': $stats['selesai']++; break;
        }
        $recentActivities[] = $izin;
    }
}

if (!empty($recentActivities)) {
    usort($recentActivities, function($a, $b) {
        return strtotime($b['created_at'] ?? '1970-01-01') - strtotime($a['created_at'] ?? '1970-01-01');
    });
    $recentActivities = array_slice($recentActivities, 0, 5);
}

$sisaCuti = getSisaCuti($uid, $database);
$jenisCutiList = getJenisCuti();

$currentPage = 'home';
include __DIR__ . '/../includes/header.php';
?>

<!-- =====================================================
     STYLE MOBILE FIX
     ===================================================== -->
<style>
/* ============================================
   DASHBOARD MOBILE FIX
   ============================================ */
@media (max-width: 768px) {
    .dashboard-hero {
        padding: 16px !important;
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 10px !important;
    }
    .dashboard-hero h2 { font-size: 18px !important; }
    .dashboard-hero p { font-size: 12px !important; }
    .dashboard-hero .avatar-wrapper { align-self: flex-end !important; }
    
    .quick-actions-grid {
        grid-template-columns: 1fr !important;
        gap: 8px !important;
    }
    .quick-action-card { padding: 14px !important; }
    .quick-action-card h3 { font-size: 15px !important; }
    
    .status-summary-grid {
        grid-template-columns: 1fr !important;
        gap: 6px !important;
    }
    .status-card { padding: 12px !important; }
    .status-card .status-card-value { font-size: 24px !important; }
    
    .dashboard-bottom {
        grid-template-columns: 1fr !important;
        gap: 10px !important;
    }
    
    .section-header { flex-direction: column; align-items: flex-start; gap: 4px; }
    .section-header h3 { font-size: 16px !important; }
}

/* ============================================
   FORM CUTI MOBILE FIX
   ============================================ */
@media (max-width: 768px) {
    .form-cuti-mobile .form-grid {
        grid-template-columns: 1fr !important;
        gap: 6px !important;
    }
    .form-cuti-mobile .date-grid {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 6px !important;
    }
    .form-cuti-mobile .date-grid .form-group {
        margin-bottom: 0 !important;
    }
    .form-cuti-mobile .date-grid .form-label {
        font-size: 9px !important;
        margin-bottom: 1px !important;
    }
    .form-cuti-mobile input[type="date"] {
        font-size: 10px !important;
        padding: 4px 4px !important;
        min-height: 26px !important;
        width: 100% !important;
        box-sizing: border-box !important;
        border-radius: 4px !important;
        border: 1px solid #ddd !important;
        background: #fff !important;
    }
    .form-cuti-mobile input[type="date"]::-webkit-calendar-picker-indicator {
        padding: 0 !important;
        margin: 0 !important;
        width: 12px !important;
        height: 12px !important;
        opacity: 0.5 !important;
    }
    .form-cuti-mobile .form-control {
        font-size: 12px !important;
        padding: 4px 6px !important;
        min-height: 28px !important;
        border-radius: 4px !important;
        border: 1px solid #ddd !important;
        background: #fff !important;
        width: 100% !important;
        box-sizing: border-box !important;
    }
    .form-cuti-mobile textarea.form-control {
        min-height: 38px !important;
        font-size: 12px !important;
        font-family: inherit !important;
    }
    .form-cuti-mobile .btn-submit {
        padding: 4px 10px !important;
        font-size: 11px !important;
        height: 28px !important;
        min-height: 28px !important;
        border-radius: 4px !important;
        white-space: nowrap !important;
    }
    .form-cuti-mobile .file-input {
        padding: 2px 4px !important;
        font-size: 10px !important;
        min-height: 26px !important;
    }
    .form-cuti-mobile .form-label {
        font-size: 9px !important;
        margin-bottom: 1px !important;
        letter-spacing: 0.2px !important;
        display: block !important;
    }
    .form-cuti-mobile .row-dokumen {
        display: grid !important;
        grid-template-columns: 2fr 1fr !important;
        gap: 6px !important;
        align-items: end !important;
        margin-top: 4px !important;
    }
    .form-cuti-mobile .row-dokumen .btn-wrap {
        display: flex !important;
        align-items: flex-end !important;
        justify-content: flex-end !important;
    }
    .form-cuti-mobile .card-form {
        padding: 10px 12px !important;
        margin-bottom: 10px !important;
        border-radius: 8px !important;
    }
    .form-cuti-mobile .form-group {
        margin-bottom: 4px !important;
    }
    .form-cuti-mobile .small-text {
        font-size: 8px !important;
        margin-top: 1px !important;
        display: block !important;
        color: #999 !important;
    }
    .form-cuti-mobile select.form-control {
        font-size: 12px !important;
        padding: 4px 6px !important;
        min-height: 28px !important;
        appearance: auto !important;
        -webkit-appearance: auto !important;
    }
}

/* ============================================
   MOBILE DASHBOARD VIEW
   ============================================ */
@media (max-width: 768px) {
    .mobile-dashboard-header {
        padding: 10px 14px !important;
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        flex-wrap: wrap !important;
        gap: 6px !important;
    }
    .mobile-dashboard-header h2 { font-size: 17px !important; }
    .mobile-dashboard-header .mobile-greet small { font-size: 10px !important; }
    .mobile-dashboard-header .mobile-greet div { font-size: 10px !important; }
    
    .mobile-search {
        margin: 0 10px 10px !important;
        padding: 6px 10px !important;
        border-radius: 6px !important;
    }
    .mobile-search input { font-size: 12px !important; }
    
    .mobile-main-action {
        min-height: 70px !important;
        padding: 12px !important;
        margin: 0 10px 6px !important;
        border-radius: 10px !important;
    }
    .mobile-main-action h3 { font-size: 14px !important; }
    .mobile-main-action p { font-size: 10px !important; }
    .mobile-main-action .plus-icon { width: 24px !important; height: 24px !important; font-size: 14px !important; }
    .mobile-main-action .mobile-action-bg-icon { font-size: 48px !important; }
    
    .mobile-sub-actions {
        gap: 6px !important;
        padding: 0 10px !important;
        margin-bottom: 10px !important;
    }
    .mobile-sub-action { padding: 8px !important; border-radius: 8px !important; }
    .mobile-sub-action i { font-size: 16px !important; }
    .mobile-sub-action span { font-size: 10px !important; }
    
    .mobile-status-row { padding: 0 10px !important; gap: 6px !important; margin-bottom: 10px !important; }
    .mobile-status-item { padding: 10px !important; border-radius: 8px !important; }
    .mobile-status-item .mobile-status-count { font-size: 18px !important; }
    .mobile-status-item .mobile-status-label { font-size: 9px !important; }
    .mobile-status-item .mobile-status-sub { font-size: 9px !important; }
    .mobile-status-item .mobile-status-icon { width: 32px !important; height: 32px !important; font-size: 14px !important; }
    
    .mobile-status-grid { gap: 6px !important; padding: 0 10px !important; margin-bottom: 10px !important; }
    .mobile-stat-mini { padding: 8px !important; border-radius: 8px !important; }
    .mobile-stat-mini .stat-num { font-size: 18px !important; }
    .mobile-stat-mini .stat-label { font-size: 8px !important; }
    .mobile-stat-mini .stat-sub { font-size: 8px !important; }
    
    .mobile-activity-list { padding: 0 10px !important; }
    .mobile-activity-header h4 { font-size: 12px !important; }
    .mobile-activity-header .see-all { font-size: 10px !important; }
    .mobile-activity-item { padding: 8px 0 !important; gap: 8px !important; }
    .mobile-activity-item .ma-icon { width: 28px !important; height: 28px !important; font-size: 12px !important; }
    .mobile-activity-item .ma-title { font-size: 11px !important; }
    .mobile-activity-item .ma-sub { font-size: 9px !important; }
    .mobile-activity-item .ma-time { font-size: 8px !important; }
    
    .mobile-section-label { font-size: 9px !important; padding: 0 10px !important; margin-bottom: 4px !important; }
    
    .mobile-ajukan-form { padding: 0 10px !important; margin-bottom: 12px !important; }
    .mobile-ajukan-form .card-form { padding: 10px 12px !important; border-radius: 8px !important; }
}
</style>

<!-- =====================================================
     DESKTOP DASHBOARD
     ===================================================== -->
<div id="desktop-dashboard-view" class="layout-with-sidebar page-with-mobile-nav">
    <aside class="sidebar">
        <div class="sidebar-logo">Sistem Perizinan<span>Cuti</span><br><small style="font-size:11px;font-weight:400;color:rgba(255,255,255,.4);">Cuti Karyawan</small></div>
        <div class="sidebar-item active"><i class="ri-dashboard-line"></i> Dashboard</div>
        <div class="sidebar-item" onclick="document.getElementById('ajukan-cuti').scrollIntoView()"><i class="ri-add-circle-line"></i> Ajukan Cuti</div>
        <div class="sidebar-item" onclick="window.location.href='riwayat.php'"><i class="ri-history-line"></i> Riwayat</div>
        <div class="sidebar-item" onclick="window.location.href='profile.php'"><i class="ri-user-line"></i> Profil</div>
        <div class="sidebar-bottom">
          
            <div class="sidebar-item" onclick="window.location.href='../auth/logout.php'"><i class="ri-logout-box-line"></i> Keluar</div>
        </div>
    </aside>

    <main class="main-content">
        <!-- HERO -->
        <div class="dashboard-hero">
            <div>
                <h2>Selamat Datang, <?= escape($user['name'] ?? 'User') ?></h2>
                <p>Kelola pengajuan cuti Anda dengan mudah.</p>
                
                <?php if ($stats['menunggu'] > 0): ?>
                    <div style="background:rgba(184,134,11,.15);border:1px solid #B8860B;border-radius:6px;padding:6px 12px;margin-top:6px;display:flex;align-items:center;gap:6px;">
                        <i class="ri-notification-3-line" style="color:#B8860B;font-size:16px;"></i>
                        <span style="font-size:12px;color:#1a1a1a;">Anda memiliki <strong style="color:#B8860B;"><?= $stats['menunggu'] ?></strong> pengajuan menunggu</span>
                    </div>
                <?php else: ?>
                    <div style="background:#E8F5EE;border:1px solid #2D7A4F;border-radius:6px;padding:6px 12px;margin-top:6px;display:flex;align-items:center;gap:6px;">
                        <i class="ri-checkbox-circle-line" style="color:#2D7A4F;font-size:16px;"></i>
                        <span style="font-size:12px;color:#1a1a1a;">Semua pengajuan sudah diproses</span>
                    </div>
                <?php endif; ?>
                
                <div style="display:flex;gap:8px;margin-top:6px;flex-wrap:wrap;">
                    <span style="background:rgba(255,255,255,.1);padding:3px 10px;border-radius:4px;color:rgba(255,255,255,.8);font-size:11px;">
                        <i class="ri-user-line"></i> <?= escape($user['jabatan'] ?? '-') ?>
                    </span>
                    <span style="background:rgba(255,255,255,.1);padding:3px 10px;border-radius:4px;color:rgba(255,255,255,.8);font-size:11px;">
                        <i class="ri-building-line"></i> <?= escape($user['departemen'] ?? '-') ?>
                    </span>
                    <span style="background:rgba(184,134,11,.2);padding:3px 10px;border-radius:4px;color:#B8860B;font-size:11px;font-weight:600;">
                        <i class="ri-calendar-2-line"></i> Sisa: <?= $sisaCuti ?> Hari
                    </span>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="text-align:right;color:rgba(255,255,255,.5);font-size:10px;">
                    <div>NIP: <?= escape($user['nip'] ?? '-') ?></div>
                </div>
                <div style="width:48px;height:48px;border-radius:50%;background:#B8860B;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:18px;border:3px solid rgba(255,255,255,.15);flex-shrink:0;">
                    <?= strtoupper(substr($user['name'] ?? 'U', 0, 2)) ?>
                </div>
            </div>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="quick-actions-grid">
            <div class="quick-action-card dark" onclick="document.getElementById('ajukan-cuti').scrollIntoView()">
                <div>
                    <div class="quick-action-label primary">Utama</div>
                    <h3>Ajukan Cuti</h3>
                    <p>Mulai proses pengajuan cuti.</p>
                </div>
                <div class="qa-icon dark-icon"><i class="ri-add-circle-line"></i></div>
            </div>
            <div class="quick-action-card" onclick="window.location.href='riwayat.php'">
                <div>
                    <div class="quick-action-label muted">Arsip</div>
                    <h3>Lihat Riwayat</h3>
                    <p>Pantau perkembangan cuti.</p>
                </div>
                <div class="qa-icon"><i class="ri-history-line"></i></div>
            </div>
        </div>

        <!-- STATUS -->
        <div class="section-header">
            <div><h3>Ringkasan Status</h3><small style="font-size:11px;color:#888;">Update: <?= date('d M Y, H:i') ?></small></div>
            <a href="riwayat.php" class="see-all">Lihat Semua <i class="ri-arrow-right-line"></i></a>
        </div>
        
        <div class="status-summary-grid">
            <div class="status-card warning">
                <div class="status-card-icon"><i class="ri-time-line"></i></div>
                <div class="status-card-value"><?= $stats['menunggu'] ?></div>
                <div class="status-card-label">Menunggu</div>
                <div class="status-card-sub">Menunggu persetujuan</div>
            </div>
            <div class="status-card success">
                <div class="status-card-icon"><i class="ri-checkbox-circle-line"></i></div>
                <div class="status-card-value"><?= $stats['disetujui'] ?></div>
                <div class="status-card-label">Disetujui</div>
                <div class="status-card-sub">Cuti disetujui</div>
            </div>
            <div class="status-card danger">
                <div class="status-card-icon"><i class="ri-close-circle-line"></i></div>
                <div class="status-card-value"><?= $stats['ditolak'] ?></div>
                <div class="status-card-label">Ditolak</div>
                <div class="status-card-sub">Perlu perbaikan</div>
            </div>
        </div>

        <!-- =====================================================
             FORM AJUKAN CUTI - DESKTOP & MOBILE
             ===================================================== -->
        <div id="ajukan-cuti" style="margin-top:6px;scroll-margin-top:70px;">
            <div class="section-header">
                <div>
                    <h3>Ajukan Cuti Baru</h3>
                    <small style="font-size:11px;color:#888;">Sisa cuti: <strong style="color:#B8860B;"><?= $sisaCuti ?> hari</strong></small>
                </div>
            </div>
            
            <?php if (isset($error)): ?>
                <div style="background:#FDECEA;border:1px solid #C0392B;border-radius:6px;padding:4px 10px;margin-bottom:6px;color:#C0392B;font-size:12px;">
                    <i class="ri-error-warning-line"></i> <?= escape($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div style="background:#E8F5EE;border:1px solid #2D7A4F;border-radius:6px;padding:4px 10px;margin-bottom:6px;color:#2D7A4F;font-size:12px;">
                    <i class="ri-checkbox-circle-line"></i> <?= $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
                </div>
            <?php endif; ?>
            
            <div class="card card-form form-cuti-mobile" style="padding:16px 20px;margin-bottom:16px;border-radius:10px;border:1px solid #eee;">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="ajukan_cuti" value="1">
                    
                    <!-- Jenis Cuti -->
                    <div class="form-group" style="margin-bottom:6px;">
                        <label class="form-label" style="font-size:10px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:0.3px;">Jenis Cuti <span style="color:red;">*</span></label>
                        <select name="jenis_cuti" class="form-control" style="padding:5px 8px;font-size:13px;border-radius:6px;width:100%;border:1px solid #ddd;background:#fff;min-height:32px;" required>
                            <option value="">Pilih</option>
                            <?php foreach ($jenisCutiList as $value => $label): ?>
                                <option value="<?= escape($value) ?>"><?= escape($value) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Tanggal -->
                    <div class="date-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:6px;">
                        <div class="form-group">
                            <label class="form-label" style="font-size:10px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:0.3px;">Mulai <span style="color:red;">*</span></label>
                            <input type="date" name="tanggal_mulai" class="form-control" style="padding:5px 6px;font-size:12px;border-radius:6px;width:100%;border:1px solid #ddd;background:#fff;min-height:32px;" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size:10px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:0.3px;">Selesai <span style="color:red;">*</span></label>
                            <input type="date" name="tanggal_selesai" class="form-control" style="padding:5px 6px;font-size:12px;border-radius:6px;width:100%;border:1px solid #ddd;background:#fff;min-height:32px;" required>
                        </div>
                    </div>
                    
                    <!-- Alasan -->
                    <div class="form-group" style="margin-bottom:6px;">
                        <label class="form-label" style="font-size:10px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:0.3px;">Alasan <span style="color:red;">*</span></label>
                        <textarea name="alasan" class="form-control" rows="2" placeholder="Jelaskan alasan..." style="padding:5px 8px;font-size:13px;border-radius:6px;width:100%;border:1px solid #ddd;resize:vertical;font-family:inherit;min-height:40px;" required></textarea>
                    </div>
                    
                    <!-- Dokumen + Submit -->
                    <div class="row-dokumen" style="display:grid;grid-template-columns:2fr 1fr;gap:8px;align-items:end;">
                        <div class="form-group">
                            <label class="form-label" style="font-size:10px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:0.3px;">Dokumen</label>
                            <input type="file" name="dokumen" class="form-control file-input" accept=".pdf,.jpg,.jpeg,.png" style="padding:3px 6px;font-size:11px;border-radius:6px;width:100%;border:1px solid #ddd;background:#fff;min-height:28px;">
                            <small class="small-text" style="color:#999;font-size:9px;display:block;margin-top:1px;">PDF, JPG, PNG. Maks 5MB</small>
                        </div>
                        <div class="btn-wrap">
                            <button type="submit" class="btn btn-primary btn-submit" style="padding:5px 14px;font-size:13px;border-radius:6px;height:34px;background:#1a1a1a;color:#fff;border:none;cursor:pointer;font-weight:600;white-space:nowrap;width:100%;">
                                <i class="ri-send-plane-line" style="font-size:13px;"></i> Kirim
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- BOTTOM ROW -->
        <div class="dashboard-bottom">
            <div class="card" style="padding:16px;">
                <h3 style="font-size:15px;font-weight:700;margin-bottom:10px;">Aktivitas Terbaru</h3>
                <div class="activity-list">
                    <?php if (empty($recentActivities)): ?>
                        <div style="text-align:center;padding:16px;color:#888;font-size:13px;">
                            <i class="ri-inbox-line" style="font-size:28px;display:block;margin-bottom:4px;color:#ddd;"></i>
                            Belum ada aktivitas
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentActivities as $activity): ?>
                            <?php if (!is_array($activity)) continue; ?>
                            <?php $status = $activity['status'] ?? 'Menunggu'; ?>
                            <div style="display:flex;align-items:flex-start;gap:8px;padding:8px 0;border-bottom:1px solid #f0f0f0;">
                                <div style="width:28px;height:28px;border-radius:4px;background:#f5f5f0;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="ri-file-list-3-line" style="font-size:14px;color:#888;"></i>
                                </div>
                                <div style="flex:1;min-width:0;">
                                    <div style="font-size:13px;font-weight:600;"><?= escape($activity['jenis_cuti'] ?? 'Cuti') ?></div>
                                    <div style="font-size:11px;color:#888;">
                                        Status: <span style="font-weight:600;<?= $status === 'Menunggu' ? 'color:#B8860B;' : ($status === 'Disetujui' ? 'color:#2D7A4F;' : 'color:#C0392B;') ?>"><?= $status ?></span>
                                        <span style="color:#999;">(<?= $activity['durasi'] ?? 0 ?> hari)</span>
                                    </div>
                                    <?php if (!empty($activity['catatan_admin'])): ?>
                                        <div style="background:#f8f5f0;padding:3px 8px;border-radius:4px;margin-top:2px;font-size:10px;border-left:2px solid #B8860B;">
                                            <strong style="color:#888;">Catatan:</strong> <?= escape($activity['catatan_admin']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($activity['dokumen'])): ?>
                                        <div style="margin-top:1px;">
                                            <a href="<?= escape($activity['dokumen']) ?>" target="_blank" style="font-size:10px;color:#B8860B;text-decoration:underline;">Lihat Dokumen</a>
                                        </div>
                                    <?php endif; ?>
                                    <div style="font-size:9px;color:#aaa;margin-top:1px;"><?= formatTanggalWaktu($activity['created_at'] ?? '') ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div>
                <div class="card" style="padding:12px 16px;text-align:center;background:#f8f8f8;border:1px solid #eee;">
                    <i class="ri-information-line" style="font-size:24px;color:#B8860B;display:block;margin-bottom:4px;"></i>
                    <p style="font-size:12px;color:#888;margin:0;">Sistem manajemen cuti terintegrasi</p>
                    <div style="margin-top:4px;display:flex;justify-content:center;gap:10px;flex-wrap:wrap;">
                        <span style="font-size:10px;color:#888;"><span style="display:inline-block;width:6px;height:6px;background:#2D7A4F;border-radius:50%;margin-right:4px;"></span> Online</span>
                        <span style="font-size:10px;color:#888;">v2.4.0</span>
                    </div>
                </div>
                <div class="card" style="padding:10px 14px;margin-top:8px;">
                    <div style="font-size:9px;color:#888;text-transform:uppercase;letter-spacing:0.5px;">Informasi Sistem</div>
                    <div style="display:flex;align-items:center;gap:4px;margin-top:3px;font-size:11px;color:#888;">
                        <span style="display:inline-block;width:6px;height:6px;background:#2D7A4F;border-radius:50%;"></span> Server Operasional
                    </div>
                    <div style="font-size:10px;color:#aaa;padding-left:10px;">Sisa Cuti: <?= $sisaCuti ?> hari</div>
                </div>
            </div>
        </div>

       
    </main>
</div>

<!-- =====================================================
     MOBILE DASHBOARD VIEW
     ===================================================== -->
<div id="mobile-dashboard-view" class="page-with-mobile-nav" style="display:none;">
    <div class="mobile-dashboard-header">
        <div class="mobile-greet">
            <small style="font-size:10px;color:#888;display:block;">Selamat Datang,</small>
            <h2 style="font-size:17px;font-weight:700;margin:0;"><?= escape($user['name'] ?? 'User') ?></h2>
            <div style="font-size:10px;color:#888;"><?= escape($user['jabatan'] ?? '-') ?> · <?= escape($user['departemen'] ?? '-') ?></div>
        </div>
        <div style="display:flex;align-items:center;gap:4px;">
            <div style="width:30px;height:30px;border-radius:50%;background:#B8860B;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:11px;cursor:pointer;" onclick="window.location.href='profile.php'">
                <?= strtoupper(substr($user['name'] ?? 'U', 0, 2)) ?>
            </div>
        </div>
    </div>
    
    <div class="mobile-search" style="display:flex;align-items:center;gap:6px;background:#f5f5f0;border:1px solid #eee;border-radius:6px;padding:6px 10px;margin:0 10px 8px;">
        <i class="ri-search-line" style="color:#888;font-size:13px;"></i>
        <input type="text" placeholder="Cari pengajuan..." style="border:none;background:none;flex:1;font-size:12px;outline:none;">
    </div>
    
    <div style="padding:0 10px;margin-bottom:4px;">
        <span style="font-size:9px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:0.3px;">Aksi Cepat</span>
    </div>
    
    <div class="mobile-main-action" onclick="document.getElementById('ajukan-cuti-mobile').scrollIntoView()" style="background:#1a1a1a;border-radius:10px;padding:12px;margin:0 10px 6px;display:flex;justify-content:space-between;align-items:flex-end;min-height:70px;cursor:pointer;">
        <div>
            <div style="width:24px;height:24px;border-radius:50%;border:2px solid rgba(184,134,11,0.4);display:flex;align-items:center;justify-content:center;color:#B8860B;font-size:13px;margin-bottom:3px;">
                <i class="ri-add-line"></i>
            </div>
            <h3 style="font-size:14px;font-weight:700;color:#fff;margin:0;">Ajukan Cuti</h3>
            <p style="font-size:10px;color:rgba(255,255,255,0.4);margin:0;">Sisa: <?= $sisaCuti ?> hari</p>
        </div>
        <i class="ri-file-text-line" style="font-size:40px;color:rgba(255,255,255,0.05);"></i>
    </div>
    
    <div class="mobile-sub-actions" style="display:grid;grid-template-columns:1fr 1fr;gap:6px;padding:0 10px;margin-bottom:8px;">
        <div style="background:#fff;border:1px solid #eee;border-radius:8px;padding:8px;text-align:center;cursor:pointer;" onclick="window.location.href='riwayat.php'">
            <i class="ri-history-line" style="font-size:16px;color:#B8860B;"></i>
            <span style="display:block;font-size:10px;font-weight:600;margin-top:1px;">Riwayat</span>
        </div>
        <div style="background:#fff;border:1px solid #eee;border-radius:8px;padding:8px;text-align:center;cursor:pointer;" onclick="showToast('Panduan sedang diperbarui')">
            <i class="ri-question-line" style="font-size:16px;color:#B8860B;"></i>
            <span style="display:block;font-size:10px;font-weight:600;margin-top:1px;">Panduan</span>
        </div>
    </div>
    
    <div style="padding:0 10px;margin-bottom:4px;">
        <span style="font-size:9px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:0.3px;">Status Cuti</span>
    </div>
    
    <div class="mobile-status-row" style="display:flex;flex-direction:column;gap:6px;padding:0 10px;margin-bottom:8px;">
        <div style="background:#1a1a1a;border-radius:8px;padding:10px;display:flex;justify-content:space-between;align-items:center;">
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:28px;height:28px;border-radius:6px;background:rgba(184,134,11,0.15);display:flex;align-items:center;justify-content:center;color:#B8860B;font-size:14px;">
                    <i class="ri-calendar-2-line"></i>
                </div>
                <div>
                    <div style="font-size:9px;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:0.3px;">Sisa Cuti</div>
                    <div style="font-size:18px;font-weight:700;color:#fff;"><?= $sisaCuti ?></div>
                    <div style="font-size:9px;color:rgba(255,255,255,0.3);">Hari</div>
                </div>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;">
            <div style="background:#fff;border:1px solid #eee;border-radius:8px;padding:8px;text-align:center;border-left:2px solid #2D7A4F;">
                <div style="font-size:18px;font-weight:800;color:#2D7A4F;"><?= $stats['disetujui'] ?></div>
                <div style="font-size:8px;color:#888;text-transform:uppercase;letter-spacing:0.2px;">Disetujui</div>
            </div>
            <div style="background:#fff;border:1px solid #eee;border-radius:8px;padding:8px;text-align:center;border-left:2px solid #C0392B;">
                <div style="font-size:18px;font-weight:800;color:#C0392B;"><?= $stats['ditolak'] ?></div>
                <div style="font-size:8px;color:#888;text-transform:uppercase;letter-spacing:0.2px;">Ditolak</div>
            </div>
        </div>
    </div>
    
    <!-- MOBILE AJUKAN CUTI -->
    <div id="ajukan-cuti-mobile" style="padding:0 10px;margin-bottom:10px;scroll-margin-top:60px;">
        <div style="padding:0 0 4px 0;">
            <span style="font-size:9px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:0.3px;">Ajukan Cuti</span>
        </div>
        <div class="card card-form form-cuti-mobile" style="padding:10px 12px;border-radius:8px;border:1px solid #eee;background:#fff;">
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="ajukan_cuti" value="1">
                
                <div class="form-group" style="margin-bottom:4px;">
                    <label class="form-label" style="font-size:9px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:0.2px;display:block;margin-bottom:1px;">Jenis Cuti <span style="color:red;">*</span></label>
                    <select name="jenis_cuti" class="form-control" style="padding:4px 6px;font-size:12px;border-radius:4px;width:100%;border:1px solid #ddd;background:#fff;min-height:26px;" required>
                        <option value="">Pilih</option>
                        <?php foreach ($jenisCutiList as $value => $label): ?>
                            <option value="<?= escape($value) ?>"><?= escape($value) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="date-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:4px;margin-bottom:4px;">
                    <div class="form-group">
                        <label class="form-label" style="font-size:9px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:0.2px;display:block;margin-bottom:1px;">Mulai <span style="color:red;">*</span></label>
                        <input type="date" name="tanggal_mulai" class="form-control" style="padding:3px 4px;font-size:10px;border-radius:4px;width:100%;border:1px solid #ddd;background:#fff;min-height:24px;" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size:9px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:0.2px;display:block;margin-bottom:1px;">Selesai <span style="color:red;">*</span></label>
                        <input type="date" name="tanggal_selesai" class="form-control" style="padding:3px 4px;font-size:10px;border-radius:4px;width:100%;border:1px solid #ddd;background:#fff;min-height:24px;" required>
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom:4px;">
                    <label class="form-label" style="font-size:9px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:0.2px;display:block;margin-bottom:1px;">Alasan <span style="color:red;">*</span></label>
                    <textarea name="alasan" class="form-control" rows="2" placeholder="Jelaskan alasan..." style="padding:3px 6px;font-size:11px;border-radius:4px;width:100%;border:1px solid #ddd;resize:vertical;font-family:inherit;min-height:34px;" required></textarea>
                </div>
                
                <div class="row-dokumen" style="display:grid;grid-template-columns:2fr 1fr;gap:4px;align-items:end;">
                    <div class="form-group">
                        <label class="form-label" style="font-size:9px;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:0.2px;display:block;margin-bottom:1px;">Dokumen</label>
                        <input type="file" name="dokumen" class="form-control file-input" accept=".pdf,.jpg,.jpeg,.png" style="padding:2px 4px;font-size:9px;border-radius:4px;width:100%;border:1px solid #ddd;background:#fff;min-height:24px;">
                        <small style="font-size:7px;color:#999;display:block;margin-top:0px;">PDF, JPG, PNG</small>
                    </div>
                    <div class="btn-wrap">
                        <button type="submit" class="btn btn-primary btn-submit" style="padding:3px 8px;font-size:11px;border-radius:4px;height:24px;min-height:24px;background:#1a1a1a;color:#fff;border:none;cursor:pointer;font-weight:600;white-space:nowrap;width:100%;">
                            <i class="ri-send-plane-line" style="font-size:11px;"></i> Kirim
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Aktivitas Terbaru Mobile -->
    <div class="mobile-activity-list" style="padding:0 10px;margin-bottom:8px;">
        <div class="mobile-activity-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
            <span style="font-size:12px;font-weight:700;">Aktivitas</span>
            <a href="riwayat.php" style="font-size:10px;color:#B8860B;text-decoration:none;">Lihat Semua</a>
        </div>
        
        <?php if (empty($recentActivities)): ?>
            <div style="background:#fff;border:1px solid #eee;border-radius:8px;padding:12px;text-align:center;color:#888;">
                <i class="ri-inbox-line" style="font-size:24px;display:block;margin-bottom:4px;color:#ddd;"></i>
                <span style="font-size:11px;">Belum ada aktivitas</span>
            </div>
        <?php else: ?>
            <?php foreach ($recentActivities as $activity): ?>
                <?php if (!is_array($activity)) continue; ?>
                <div style="background:#fff;border:1px solid #eee;border-radius:8px;padding:8px 10px;margin-bottom:4px;display:flex;align-items:center;gap:8px;">
                    <div style="width:24px;height:24px;border-radius:4px;background:#f5f5f0;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="ri-file-copy-2-line" style="font-size:12px;color:#888;"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:11px;font-weight:600;"><?= escape($activity['jenis_cuti'] ?? 'Cuti') ?></div>
                        <div style="font-size:9px;color:#888;">
                            Status: <span style="font-weight:600;<?= ($activity['status'] ?? 'Menunggu') === 'Menunggu' ? 'color:#B8860B;' : (($activity['status'] ?? '') === 'Disetujui' ? 'color:#2D7A4F;' : 'color:#C0392B;') ?>"><?= $activity['status'] ?? 'Menunggu' ?></span>
                        </div>
                        <?php if (!empty($activity['catatan_admin'])): ?>
                            <div style="background:#f8f5f0;padding:2px 6px;border-radius:3px;margin-top:1px;font-size:9px;border-left:2px solid #B8860B;">
                                <strong style="color:#888;">Catatan:</strong> <?= escape($activity['catatan_admin']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:8px;color:#aaa;flex-shrink:0;"><?= formatTanggal($activity['created_at'] ?? '') ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div style="height:60px;"></div>
</div>

<!-- Mobile Nav -->
<nav class="mobile-nav-bar">
    <button class="mobile-nav-item active" onclick="window.location.href='home.php'"><i class="ri-dashboard-line"></i>Dashboard</button>
   
    <button class="mobile-nav-item" onclick="window.location.href='riwayat.php'"><i class="ri-history-line"></i>Riwayat</button>
    <button class="mobile-nav-item" onclick="window.location.href='profile.php'"><i class="ri-user-line"></i>Profil</button>
</nav>

<!-- Global Toast -->
<div id="global-toast" class="toast-notif" style="display:none;"></div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

