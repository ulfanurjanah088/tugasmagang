<?php
/**
 * =====================================================
 * FILE: admin/riwayat.php
 * FUNGSI: Riwayat Admin - Mobile Fixed
 * VERSION: FINAL - Fixed Path
 * =====================================================
 */

// 🔥 FIX PATH
require_once __DIR__ . '/../config/firebase.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// 🔥 Cek login & admin
if (!$auth->isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}
if (!$auth->isAdmin()) {
    header('Location: ../user/index.php');
    exit;
}

$database = FirebaseConfig::getDatabase();

$allPermohonan = $database->getReference('permohonan')->getValue();
$data = [];
if (is_array($allPermohonan) && !empty($allPermohonan)) {
    foreach ($allPermohonan as $key => $izin) {
        if (!is_array($izin)) continue;
        $izin['_key'] = $key;
        $data[] = $izin;
    }
}

usort($data, function($a, $b) {
    return strtotime($b['created_at'] ?? '1970-01-01') - strtotime($a['created_at'] ?? '1970-01-01');
});

$total = count($data);
$stats = ['total' => $total, 'menunggu' => 0, 'disetujui' => 0, 'ditolak' => 0, 'selesai' => 0];
foreach ($data as $izin) {
    $status = $izin['status'] ?? '';
    switch ($status) {
        case 'Menunggu': $stats['menunggu']++; break;
        case 'Disetujui': $stats['disetujui']++; break;
        case 'Ditolak': $stats['ditolak']++; break;
        case 'Selesai': $stats['selesai']++; break;
    }
}

$filterStatus = $_GET['status'] ?? 'all';
$filteredData = [];
if ($filterStatus !== 'all') {
    foreach ($data as $izin) {
        if (($izin['status'] ?? '') === $filterStatus) $filteredData[] = $izin;
    }
} else {
    $filteredData = $data;
}

$currentPage = 'admin-riwayat';

// 🔥 FIX: Path ke header
include __DIR__ . '/../includes/header.php';
?>

<style>
@media (max-width: 768px) {
    .admin-riwayat-grid { grid-template-columns: 1fr 1fr !important; gap: 8px !important; }
    .admin-riwayat-grid .card { padding: 10px !important; }
    .admin-riwayat-grid .stat-val { font-size: 18px !important; }
    .data-table { font-size: 11px !important; }
    .data-table th, .data-table td { padding: 4px 6px !important; }
    .filter-tabs { flex-wrap: wrap; gap: 4px; }
    .filter-tabs .filter-tab { font-size: 11px; padding: 4px 10px; }
}
</style>

<div class="layout-with-sidebar page-with-mobile-nav">
    <aside class="sidebar">
        <div class="sidebar-logo">Magang<span>.usg</span><br><small style="font-size:11px;font-weight:400;color:rgba(255,255,255,.4);">Admin Panel</small></div>
        <div class="sidebar-item" onclick="window.location.href='index.php'"><i class="ri-file-search-line"></i> Review</div>
        <div class="sidebar-item active"><i class="ri-history-line"></i> Riwayat</div>
        <div class="sidebar-item" onclick="window.location.href='laporan.php'"><i class="ri-file-chart-line"></i> Laporan</div>
        <div class="sidebar-item" onclick="window.location.href='../user/profile.php'"><i class="ri-user-line"></i> Profil</div>
        <div class="sidebar-bottom">
            <div class="sidebar-pdf-btn" onclick="window.location.href='cetak_pdf.php'"><i class="ri-printer-line"></i> Cetak PDF</div>
            <div class="sidebar-item" onclick="window.location.href='../auth/logout.php'"><i class="ri-logout-box-line"></i> Keluar</div>
        </div>
    </aside>

    <main class="main-content">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
            <div>
                <h1 style="font-size:24px;font-weight:800;">Riwayat Admin</h1>
                <p style="color:#666;font-size:13px;">Semua pengajuan cuti</p>
            </div>
            <button class="btn btn-outline btn-sm" onclick="window.location.href='cetak_pdf.php'"><i class="ri-printer-line"></i> Cetak PDF</button>
        </div>

        <div class="admin-riwayat-grid" style="display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:16px;">
            <div class="card" style="padding:12px;text-align:center;"><div class="stat-val" style="font-size:22px;font-weight:800;"><?= $stats['total'] ?></div><div style="font-size:10px;color:#999;">Total</div></div>
            <div class="card" style="padding:12px;text-align:center;border-left:3px solid #B8860B;"><div class="stat-val" style="font-size:22px;font-weight:800;color:#B8860B;"><?= $stats['menunggu'] ?></div><div style="font-size:10px;color:#999;">Menunggu</div></div>
            <div class="card" style="padding:12px;text-align:center;border-left:3px solid #2D7A4F;"><div class="stat-val" style="font-size:22px;font-weight:800;color:#2D7A4F;"><?= $stats['disetujui'] ?></div><div style="font-size:10px;color:#999;">Setuju</div></div>
            <div class="card" style="padding:12px;text-align:center;border-left:3px solid #C0392B;"><div class="stat-val" style="font-size:22px;font-weight:800;color:#C0392B;"><?= $stats['ditolak'] ?></div><div style="font-size:10px;color:#999;">Tolak</div></div>
            <div class="card" style="padding:12px;text-align:center;border-left:3px solid #666;"><div class="stat-val" style="font-size:22px;font-weight:800;color:#666;"><?= $stats['selesai'] ?></div><div style="font-size:10px;color:#999;">Selesai</div></div>
        </div>

        <div class="section-card">
            <div class="section-card-header" style="flex-wrap:wrap;gap:8px;">
                <div><h3>Daftar Pengajuan</h3><p style="font-size:12px;color:#999;"><?= count($filteredData) ?> dari <?= $stats['total'] ?></p></div>
                <div class="filter-tabs" style="display:flex;gap:4px;flex-wrap:wrap;">
                    <a href="?status=all" class="filter-tab <?= $filterStatus === 'all' ? 'active' : '' ?>" style="padding:4px 12px;border:1px solid #ddd;border-radius:4px;font-size:12px;text-decoration:none;color:#333;background:<?= $filterStatus === 'all' ? '#1A1A1A' : 'transparent' ?>;color:<?= $filterStatus === 'all' ? '#fff' : '#333' ?>;">Semua</a>
                    <a href="?status=Menunggu" class="filter-tab <?= $filterStatus === 'Menunggu' ? 'active' : '' ?>" style="padding:4px 12px;border:1px solid #ddd;border-radius:4px;font-size:12px;text-decoration:none;color:#333;background:<?= $filterStatus === 'Menunggu' ? '#1A1A1A' : 'transparent' ?>;color:<?= $filterStatus === 'Menunggu' ? '#fff' : '#333' ?>;">Menunggu</a>
                    <a href="?status=Disetujui" class="filter-tab <?= $filterStatus === 'Disetujui' ? 'active' : '' ?>" style="padding:4px 12px;border:1px solid #ddd;border-radius:4px;font-size:12px;text-decoration:none;color:#333;background:<?= $filterStatus === 'Disetujui' ? '#1A1A1A' : 'transparent' ?>;color:<?= $filterStatus === 'Disetujui' ? '#fff' : '#333' ?>;">Disetujui</a>
                    <a href="?status=Ditolak" class="filter-tab <?= $filterStatus === 'Ditolak' ? 'active' : '' ?>" style="padding:4px 12px;border:1px solid #ddd;border-radius:4px;font-size:12px;text-decoration:none;color:#333;background:<?= $filterStatus === 'Ditolak' ? '#1A1A1A' : 'transparent' ?>;color:<?= $filterStatus === 'Ditolak' ? '#fff' : '#333' ?>;">Ditolak</a>
                </div>
            </div>
            <div style="overflow-x:auto;">
                <table class="data-table" style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr><th style="padding:8px 10px;text-align:left;background:#f5f5f0;border-bottom:2px solid #ddd;">No</th>
                        <th style="padding:8px 10px;text-align:left;background:#f5f5f0;border-bottom:2px solid #ddd;">Pemohon</th>
                        <th style="padding:8px 10px;text-align:left;background:#f5f5f0;border-bottom:2px solid #ddd;">Jenis</th>
                        <th style="padding:8px 10px;text-align:left;background:#f5f5f0;border-bottom:2px solid #ddd;">Tanggal</th>
                        <th style="padding:8px 10px;text-align:left;background:#f5f5f0;border-bottom:2px solid #ddd;">Durasi</th>
                        <th style="padding:8px 10px;text-align:left;background:#f5f5f0;border-bottom:2px solid #ddd;">Status</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($filteredData)): ?>
                            <tr><td colspan="6" style="text-align:center;padding:30px;color:#999;">Tidak ada data</td></tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($filteredData as $izin): ?>
                                <tr>
                                    <td style="padding:6px 10px;border-bottom:1px solid #eee;"><?= $no++ ?></td>
                                    <td style="padding:6px 10px;border-bottom:1px solid #eee;">
                                        <div style="font-weight:600;"><?= escape($izin['user_name'] ?? '-') ?></div>
                                        <div style="font-size:11px;color:#999;"><?= escape($izin['nip'] ?? '-') ?></div>
                                    </td>
                                    <td style="padding:6px 10px;border-bottom:1px solid #eee;"><?= escape($izin['jenis_cuti'] ?? '-') ?></td>
                                    <td style="padding:6px 10px;border-bottom:1px solid #eee;font-size:12px;"><?= formatTanggal($izin['created_at'] ?? '') ?></td>
                                    <td style="padding:6px 10px;border-bottom:1px solid #eee;"><?= $izin['durasi'] ?? 0 ?> hari</td>
                                    <td style="padding:6px 10px;border-bottom:1px solid #eee;"><?= getStatusBadge($izin['status'] ?? 'Menunggu') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 🔥 FIX: Path ke footer -->
      
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>