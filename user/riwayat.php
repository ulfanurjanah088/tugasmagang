<?php
/**
 * =====================================================
 * FILE: user/riwayat.php
 * FUNGSI: Riwayat Pengajuan Cuti
 * VERSION: FINAL - Fixed Path
 * =====================================================
 */

// 🔥 FIX PATH
require_once __DIR__ . '/../config/firebase.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin($auth);

$user = $auth->getCurrentUser();
$uid = $auth->getCurrentUid();
$database = FirebaseConfig::getDatabase();

// =====================================================
// AMBIL DATA DARI FIREBASE
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

// =====================================================
// FILTER TANGGAL
// =====================================================
$filterTanggal = $_GET['tanggal'] ?? '';
$filterBulan = $_GET['bulan'] ?? '';

$displayData = [];
foreach ($permohonan as $key => $izin) {
    if (!is_array($izin)) continue;
    
    $created = substr($izin['created_at'] ?? '', 0, 10);
    $bulan = substr($izin['created_at'] ?? '', 0, 7);
    
    $match = true;
    if (!empty($filterTanggal) && $created !== $filterTanggal) $match = false;
    if (!empty($filterBulan) && $bulan !== $filterBulan) $match = false;
    
    if ($match) $displayData[$key] = $izin;
}

if (empty($filterTanggal) && empty($filterBulan)) {
    $displayData = $permohonan;
}

$stats = ['total' => 0, 'menunggu' => 0, 'disetujui' => 0, 'ditolak' => 0, 'selesai' => 0];

if (is_array($displayData) && !empty($displayData)) {
    foreach ($displayData as $izin) {
        if (!is_array($izin)) continue;
        $stats['total']++;
        $status = $izin['status'] ?? '';
        switch ($status) {
            case 'Menunggu': $stats['menunggu']++; break;
            case 'Disetujui': $stats['disetujui']++; break;
            case 'Ditolak': $stats['ditolak']++; break;
            case 'Selesai': $stats['selesai']++; break;
        }
    }
}

$sisaCuti = getSisaCuti($uid, $database);

$currentPage = 'riwayat';

// 🔥 FIX: Path ke header
include __DIR__ . '/../includes/header.php';
?>

<style>
/* ===== RESPONSIVE ===== */
@media (min-width: 769px) { #mobile-history-view { display: none !important; } }
@media (max-width: 768px) { #desktop-history-view { display: none !important; } }

.filter-date-container {
    background: #f5f5f0;
    border: 1px solid #e0e0dc;
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.filter-date-container input[type="date"],
.filter-date-container input[type="month"] {
    padding: 6px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 13px;
    background: #fff;
}
.filter-date-container .btn-filter {
    padding: 6px 16px;
    background: #B8860B;
    color: #fff;
    border: none;
    border-radius: 4px;
    font-size: 13px;
    cursor: pointer;
}
.filter-date-container .btn-filter:hover { background: #8B6508; }
.filter-date-container .btn-reset {
    padding: 6px 16px;
    background: #f0f0f0;
    color: #333;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 13px;
    cursor: pointer;
}
.filter-date-container .btn-reset:hover { background: #e0e0e0; }

@media (max-width: 768px) {
    .filter-date-container { margin: 0 12px 14px; padding: 10px 12px; }
    .filter-date-container input { font-size: 12px; padding: 5px 8px; width: 100%; }
    .filter-date-container .btn-filter,
    .filter-date-container .btn-reset { font-size: 12px; padding: 5px 12px; }
}

/* ===== MOBILE ===== */
@media (max-width: 768px) {
    #mobile-history-view {
        display: block !important;
        padding-bottom: 80px;
    }
    .mobile-riwayat-header {
        padding: 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }
    .mobile-riwayat-header h2 { font-size: 20px; font-weight: 700; margin: 0; }
    .mobile-riwayat-header .sub-info { font-size: 12px; color: #888; }
    .mobile-stats-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        padding: 0 12px;
        margin-bottom: 12px;
    }
    .mobile-stat-card {
        background: #fff;
        border: 1px solid #e5e5e5;
        border-radius: 8px;
        padding: 10px 12px;
        text-align: center;
    }
    .mobile-stat-card .stat-val { font-size: 20px; font-weight: 800; }
    .mobile-stat-card .stat-lbl { font-size: 10px; color: #888; }
    .mobile-stat-card.primary .stat-val { color: #B8860B; }
    .mobile-stat-card.success .stat-val { color: #2D7A4F; }
    .mobile-stat-card.danger .stat-val { color: #C0392B; }
    .mobile-ajukan-btn {
        display: block;
        width: calc(100% - 24px);
        margin: 0 12px 12px;
        padding: 10px;
        background: #B8860B;
        color: #fff;
        text-align: center;
        border-radius: 8px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        font-size: 14px;
    }
    .mobile-search-riwayat {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #f5f5f0;
        border: 1px solid #e5e5e5;
        border-radius: 8px;
        padding: 8px 12px;
        margin: 0 12px 12px;
    }
    .mobile-search-riwayat input {
        border: none;
        background: none;
        flex: 1;
        font-size: 14px;
        outline: none;
    }
    .mobile-section-title {
        padding: 0 12px;
        font-size: 12px;
        font-weight: 700;
        color: #888;
        text-transform: uppercase;
        margin-bottom: 6px;
    }
    .mobile-riwayat-container { padding: 0 12px; }
    .mobile-hist-card {
        background: #fff;
        border: 1px solid #e5e5e5;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 8px;
        cursor: pointer;
    }
    .mobile-hist-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 4px;
    }
    .mobile-hist-id { font-size: 11px; font-weight: 700; color: #888; font-family: monospace; }
    .mobile-hist-header .badge { font-size: 9px; padding: 2px 8px; }
    .mobile-hist-title { font-size: 14px; font-weight: 700; margin-bottom: 4px; }
    .mobile-hist-meta { display: flex; gap: 12px; flex-wrap: wrap; }
    .mobile-hist-meta span { font-size: 11px; color: #888; display: flex; align-items: center; gap: 3px; }
    .admin-note-mobile {
        margin-top: 6px;
        font-size: 11px;
        background: #f5f5f0;
        padding: 6px 10px;
        border-radius: 4px;
        border-left: 2px solid #B8860B;
    }
    .doc-link-mobile {
        margin-top: 4px;
        display: inline-block;
        font-size: 12px;
        color: #B8860B;
        text-decoration: underline;
    }
    .empty-state-mobile {
        text-align: center;
        padding: 40px 20px;
        color: #888;
    }
    .empty-state-mobile i { font-size: 48px; display: block; margin-bottom: 12px; color: #ddd; }
    .empty-state-mobile h4 { font-size: 16px; font-weight: 600; color: #1a1a1a; }
    .mobile-footer-txt { text-align: center; padding: 8px 16px 30px; font-size: 11px; color: #888; }
}

/* ===== MODAL ===== */
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(4px);
    z-index: 999;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.modal-overlay.active { display: flex; }
.modal-box {
    background: #fff;
    border-radius: 12px;
    max-width: 500px;
    width: 100%;
    max-height: 80vh;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}
.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #eee;
    background: #fff;
    position: sticky;
    top: 0;
    z-index: 10;
}
.modal-header h3 { font-size: 18px; font-weight: 700; margin: 0; }
.modal-close-btn {
    width: 32px; height: 32px;
    border-radius: 50%;
    border: none;
    background: #f5f5f0;
    color: #888;
    font-size: 18px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}
.modal-close-btn:hover { background: #C0392B; color: #fff; }
.modal-body { padding: 20px; overflow-y: auto; max-height: calc(80vh - 70px); }
.modal-body .field-group { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.modal-body .field-full { grid-column: span 2; }
.modal-body .field-label { font-size: 10px; color: #999; text-transform: uppercase; font-weight: 600; }
.modal-body .field-value { font-size: 14px; font-weight: 600; color: #1a1a1a; margin-top: 2px; }
.modal-body .note-box {
    grid-column: span 2;
    background: #f8f5f0;
    padding: 10px 14px;
    border-radius: 6px;
    border-left: 3px solid #B8860B;
}
</style>

<!-- =====================================================
     DESKTOP VIEW
     ===================================================== -->
<div id="desktop-history-view" class="layout-with-sidebar page-with-mobile-nav">
    <aside class="sidebar">
        <div class="sidebar-logo">Magang<span>.usg</span><br><small style="font-size:11px;font-weight:400;color:rgba(255,255,255,.4);">Cuti Karyawan</small></div>
        <div class="sidebar-item" onclick="window.location.href='../user/index.php'"><i class="ri-dashboard-line"></i> Dashboard</div>
        <div class="sidebar-item" onclick="window.location.href='../user/index.php#ajukan-cuti'"><i class="ri-add-circle-line"></i> Ajukan Cuti</div>
        <div class="sidebar-item active"><i class="ri-history-line"></i> Riwayat</div>
        <div class="sidebar-item" onclick="window.location.href='../user/profile.php'"><i class="ri-user-line"></i> Profil</div>
        <div class="sidebar-bottom">
            <div class="sidebar-item" onclick="window.location.href='../auth/logout.php'"><i class="ri-logout-box-line"></i> Keluar</div>
        </div>
    </aside>

    <main class="main-content">
        <div style="margin-bottom:16px;">
            <h1 style="font-size:24px;font-weight:800;margin-bottom:2px;">Riwayat Cuti</h1>
            <p style="color:#888;font-size:13px;">Riwayat pengajuan cuti Anda</p>
            <div style="display:flex;gap:10px;margin-top:6px;flex-wrap:wrap;">
                <span style="background:#f5f5f0;padding:3px 10px;border-radius:4px;font-size:12px;">Sisa: <strong><?= $sisaCuti ?></strong> hari</span>
                <span style="background:#f5f5f0;padding:3px 10px;border-radius:4px;font-size:12px;">Total: <strong><?= $stats['total'] ?></strong></span>
                <?php if (!empty($filterTanggal)): ?>
                    <span style="background:rgba(184,134,11,.15);padding:3px 10px;border-radius:4px;font-size:12px;color:#B8860B;"><?= formatTanggal($filterTanggal) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- FILTER -->
        <div class="filter-date-container">
            <label style="font-size:12px;color:#888;font-weight:600;">Filter:</label>
            <form method="GET" action="" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <input type="date" name="tanggal" value="<?= $filterTanggal ?>">
                <span style="color:#888;font-size:13px;">atau</span>
                <input type="month" name="bulan" value="<?= $filterBulan ?>">
                <button type="submit" class="btn-filter">Terapkan</button>
                <a href="?" class="btn-reset">Reset</a>
            </form>
        </div>

        <div class="history-layout" style="display:grid;grid-template-columns:240px 1fr;gap:20px;">
            <div>
                <div style="background:#fff;border:1px solid #e5e5e5;border-radius:8px;padding:16px;margin-bottom:12px;">
                    <h4 style="font-size:10px;text-transform:uppercase;color:#888;margin-bottom:8px;">Statistik</h4>
                    <div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid #eee;"><span>Total</span><strong><?= $stats['total'] ?></strong></div>
                    <div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid #eee;"><span>Menunggu</span><strong style="color:#B8860B;"><?= $stats['menunggu'] ?></strong></div>
                    <div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid #eee;"><span>Disetujui</span><strong style="color:#2D7A4F;"><?= $stats['disetujui'] ?></strong></div>
                    <div style="display:flex;justify-content:space-between;padding:4px 0;"><span>Ditolak</span><strong style="color:#C0392B;"><?= $stats['ditolak'] ?></strong></div>
                </div>
                <button class="btn btn-primary btn-full" onclick="window.location.href='../user/index.php#ajukan-cuti'"><i class="ri-add-line"></i> Ajukan Cuti</button>
            </div>

            <div style="background:#fff;border:1px solid #e5e5e5;border-radius:8px;overflow:hidden;">
                <div style="padding:10px 14px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;flex-wrap:wrap;gap:6px;">
                    <span style="font-size:14px;font-weight:700;">Daftar Pengajuan</span>
                    <div style="display:flex;gap:4px;flex-wrap:wrap;">
                        <button class="filter-tab active" data-filter="all" style="padding:3px 10px;border:1px solid #ddd;border-radius:4px;font-size:11px;cursor:pointer;background:transparent;">Semua</button>
                        <button class="filter-tab" data-filter="Menunggu" style="padding:3px 10px;border:1px solid #ddd;border-radius:4px;font-size:11px;cursor:pointer;background:transparent;">Menunggu</button>
                        <button class="filter-tab" data-filter="Disetujui" style="padding:3px 10px;border:1px solid #ddd;border-radius:4px;font-size:11px;cursor:pointer;background:transparent;">Disetujui</button>
                        <button class="filter-tab" data-filter="Ditolak" style="padding:3px 10px;border:1px solid #ddd;border-radius:4px;font-size:11px;cursor:pointer;background:transparent;">Ditolak</button>
                    </div>
                </div>
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:13px;">
                        <thead>
                            <tr>
                                <th style="padding:6px 10px;text-align:left;background:#f5f5f0;border-bottom:2px solid #ddd;">No</th>
                                <th style="padding:6px 10px;text-align:left;background:#f5f5f0;border-bottom:2px solid #ddd;">Jenis</th>
                                <th style="padding:6px 10px;text-align:left;background:#f5f5f0;border-bottom:2px solid #ddd;">Tanggal</th>
                                <th style="padding:6px 10px;text-align:left;background:#f5f5f0;border-bottom:2px solid #ddd;">Durasi</th>
                                <th style="padding:6px 10px;text-align:left;background:#f5f5f0;border-bottom:2px solid #ddd;">Status</th>
                                <th style="padding:6px 10px;text-align:left;background:#f5f5f0;border-bottom:2px solid #ddd;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($displayData)): ?>
                                <tr><td colspan="6" style="text-align:center;padding:20px;color:#999;">Tidak ada data</td></tr>
                            <?php else: ?>
                                <?php $no = 1; foreach (array_reverse($displayData) as $key => $izin): ?>
                                    <?php if (!is_array($izin)) continue; ?>
                                    <tr class="history-row" data-status="<?= $izin['status'] ?? 'Menunggu' ?>">
                                        <td style="padding:4px 10px;border-bottom:1px solid #eee;"><?= $no++ ?></td>
                                        <td style="padding:4px 10px;border-bottom:1px solid #eee;"><?= escape($izin['jenis_cuti'] ?? '-') ?></td>
                                        <td style="padding:4px 10px;border-bottom:1px solid #eee;font-size:12px;"><?= formatTanggal($izin['created_at'] ?? '') ?></td>
                                        <td style="padding:4px 10px;border-bottom:1px solid #eee;"><?= $izin['durasi'] ?? 0 ?> hari</td>
                                        <td style="padding:4px 10px;border-bottom:1px solid #eee;"><?= getStatusBadge($izin['status'] ?? 'Menunggu') ?></td>
                                        <td style="padding:4px 10px;border-bottom:1px solid #eee;">
                                            <button class="btn btn-outline btn-sm" onclick="showDetail('<?= $key ?>')"><i class="ri-eye-line"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- =====================================================
     MOBILE VIEW
     ===================================================== -->
<div id="mobile-history-view">

    <div class="filter-date-container" style="margin:0 12px 14px;">
        <form method="GET" action="" style="display:flex;flex-direction:column;gap:6px;width:100%;">
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <input type="date" name="tanggal" value="<?= $filterTanggal ?>" style="flex:1;min-width:100px;">
                <input type="month" name="bulan" value="<?= $filterBulan ?>" style="flex:1;min-width:100px;">
            </div>
            <div style="display:flex;gap:6px;">
                <button type="submit" class="btn-filter" style="flex:1;">Terapkan</button>
                <a href="?" class="btn-reset" style="flex:1;text-align:center;text-decoration:none;">Reset</a>
            </div>
        </form>
    </div>

    <div class="mobile-stats-grid">
        <div class="mobile-stat-card"><div class="stat-val"><?= $stats['total'] ?></div><div class="stat-lbl">Total</div></div>
        <div class="mobile-stat-card primary"><div class="stat-val"><?= $stats['menunggu'] ?></div><div class="stat-lbl">Menunggu</div></div>
        <div class="mobile-stat-card success"><div class="stat-val"><?= $stats['disetujui'] ?></div><div class="stat-lbl">Disetujui</div></div>
        <div class="mobile-stat-card danger"><div class="stat-val"><?= $stats['ditolak'] ?></div><div class="stat-lbl">Ditolak</div></div>
    </div>

    <button class="mobile-ajukan-btn" onclick="window.location.href='../user/index.php#ajukan-cuti'">+ Ajukan Cuti</button>

    <div class="mobile-search-riwayat">
        <i class="ri-search-line"></i>
        <input type="text" placeholder="Cari..." id="mobile-search-history">
    </div>

    <div class="mobile-section-title">Daftar Pengajuan</div>
    <div class="mobile-riwayat-container">
        <?php if (empty($displayData)): ?>
            <div class="empty-state-mobile"><i class="ri-inbox-line"></i><h4>Belum Ada Data</h4></div>
        <?php else: ?>
            <?php foreach (array_reverse($displayData) as $key => $izin): ?>
                <?php if (!is_array($izin)) continue; ?>
                <div class="mobile-hist-card" onclick="showDetail('<?= $key ?>')">
                    <div class="mobile-hist-header">
                        <span class="mobile-hist-id">#<?= substr($key, -4) ?></span>
                        <?= getStatusBadge($izin['status'] ?? 'Menunggu') ?>
                    </div>
                    <div class="mobile-hist-title"><?= escape($izin['jenis_cuti'] ?? 'Cuti') ?></div>
                    <div class="mobile-hist-meta">
                        <span><i class="ri-calendar-line"></i> <?= formatTanggal($izin['created_at'] ?? '') ?></span>
                        <span><i class="ri-time-line"></i> <?= $izin['durasi'] ?? 0 ?> hari</span>
                    </div>
                    <?php if (!empty($izin['catatan_admin'])): ?>
                        <div class="admin-note-mobile">Catatan: <?= escape($izin['catatan_admin']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($izin['dokumen'])): ?>
                        <a href="<?= escape($izin['dokumen']) ?>" target="_blank" class="doc-link-mobile" onclick="event.stopPropagation();">Lihat Dokumen</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="mobile-footer-txt">sistem.perizinan.cuti &copy; <?= date('Y') ?></div>
</div>

<!-- =====================================================
     MODAL DETAIL
     ===================================================== -->
<div id="detail-modal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Detail Pengajuan</h3>
            <button class="modal-close-btn" onclick="closeDetailModal()"><i class="ri-close-line"></i></button>
        </div>
        <div class="modal-body" id="detail-content">
            <p style="text-align:center;color:#999;">Loading...</p>
        </div>
    </div>
</div>

<!-- Mobile Nav -->
<nav class="mobile-nav-bar">
    <button class="mobile-nav-item" onclick="window.location.href='../user/index.php'"><i class="ri-dashboard-line"></i>Dashboard</button>
    <button class="mobile-nav-item active"><i class="ri-history-line"></i>Riwayat</button>
    <button class="mobile-nav-item" onclick="window.location.href='../user/profile.php'"><i class="ri-user-line"></i>Profil</button>
</nav>

<div id="global-toast" class="toast-notif" style="display:none;"></div>

<script>
const allData = <?= json_encode($permohonan) ?>;

function applyView() {
    const isMobile = window.innerWidth <= 768;
    document.getElementById('desktop-history-view').style.display = isMobile ? 'none' : '';
    document.getElementById('mobile-history-view').style.display  = isMobile ? 'block' : 'none';
}
applyView();
window.addEventListener('resize', applyView);

function showDetail(key) {
    const data = allData[key];
    if (!data) { showToast('Data tidak ditemukan'); return; }
    
    let html = '<div class="field-group">';
    const fields = [
        ['ID', data.id || '-'],
        ['Status', data.status || '-'],
        ['Nama', data.user_name || '-'],
        ['NIP', data.nip || '-'],
        ['Jenis', data.jenis_cuti || '-'],
        ['Durasi', (data.durasi || 0) + ' hari'],
        ['Tanggal', (data.tanggal_mulai || '-') + ' s/d ' + (data.tanggal_selesai || '-')],
        ['Alasan', data.alasan || '-']
    ];
    fields.forEach(f => {
        html += `<div ${f[0] === 'Alasan' || f[0] === 'Tanggal' ? 'class="field-full"' : ''}>
            <div class="field-label">${f[0]}</div>
            <div class="field-value">${f[1]}</div>
        </div>`;
    });
    if (data.catatan_admin) {
        html += `<div class="field-full note-box"><div class="field-label">Catatan Admin</div><div>${data.catatan_admin}</div></div>`;
    }
    if (data.dokumen) {
        html += `<div class="field-full"><a href="${data.dokumen}" target="_blank" style="color:#B8860B;text-decoration:underline;">Lihat Dokumen</a></div>`;
    }
    html += '</div>';
    
    document.getElementById('detail-content').innerHTML = html;
    document.getElementById('detail-modal').classList.add('active');
}

function closeDetailModal() {
    document.getElementById('detail-modal').classList.remove('active');
}

document.addEventListener('click', function(e) {
    if (e.target === document.getElementById('detail-modal')) closeDetailModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeDetailModal();
});

document.querySelectorAll('.filter-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        const filter = this.dataset.filter;
        document.querySelectorAll('.history-row').forEach(row => {
            row.style.display = (filter === 'all' || row.dataset.status === filter) ? '' : 'none';
        });
    });
});

document.getElementById('mobile-search-history')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.mobile-hist-card').forEach(card => {
        const title = card.querySelector('.mobile-hist-title')?.textContent.toLowerCase() || '';
        const id = card.querySelector('.mobile-hist-id')?.textContent.toLowerCase() || '';
        card.style.display = (title.includes(q) || id.includes(q)) ? '' : 'none';
    });
});

function showToast(msg, icon) {
    if (typeof icon === 'undefined') icon = 'ri-information-line';
    const t = document.getElementById('global-toast');
    if (!t) return;
    t.innerHTML = `<i class="${icon}"></i> ${msg}`;
    t.style.display = 'flex';
    if (window.toastTimer) clearTimeout(window.toastTimer);
    window.toastTimer = setTimeout(() => { t.style.display = 'none'; }, 3000);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
