<?php
/**
 * =====================================================
 * FILE: admin_laporan.php
 * FUNGSI: Laporan Admin - Simpel + Filter Tanggal
 * VERSION: FINAL
 * =====================================================
 */

require_once __DIR__ . '/../config/firebase.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (!$auth->isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}
if (!$auth->isAdmin()) {
    header('Location: ../user/index.php');
    exit;
}
requireAdmin($auth);

$database = FirebaseConfig::getDatabase();

// 🔥 FILTER TANGGAL
$filterTanggal = $_GET['tanggal'] ?? '';
$filterBulan = $_GET['bulan'] ?? '';

// Ambil semua data
$allPermohonan = $database->getReference('permohonan')->getValue();
$allUsers = $database->getReference('users')->getValue();

$data = [];
if (is_array($allPermohonan) && !empty($allPermohonan)) {
    foreach ($allPermohonan as $key => $izin) {
        if (!is_array($izin)) continue;
        $izin['_key'] = $key;
        
        // Filter tanggal
        $created = substr($izin['created_at'] ?? '', 0, 10);
        $bulan = substr($izin['created_at'] ?? '', 0, 7);
        
        if (!empty($filterTanggal) && $created !== $filterTanggal) continue;
        if (!empty($filterBulan) && $bulan !== $filterBulan) continue;
        
        $data[] = $izin;
    }
}

// Urutkan dari terbaru
usort($data, function($a, $b) {
    return strtotime($b['created_at'] ?? '1970-01-01') - strtotime($a['created_at'] ?? '1970-01-01');
});

// Statistik
$totalCuti = count($data);
$statusCount = ['Menunggu' => 0, 'Disetujui' => 0, 'Ditolak' => 0, 'Selesai' => 0];
$jenisCutiCount = [];
$userCutiCount = [];

foreach ($data as $izin) {
    $status = $izin['status'] ?? 'Menunggu';
    if (isset($statusCount[$status])) $statusCount[$status]++;
    
    $jenis = $izin['jenis_cuti'] ?? 'Lainnya';
    if (!isset($jenisCutiCount[$jenis])) $jenisCutiCount[$jenis] = 0;
    $jenisCutiCount[$jenis]++;
    
    $userId = $izin['user_id'] ?? 'unknown';
    if (!isset($userCutiCount[$userId])) {
        $userCutiCount[$userId] = [
            'name' => $izin['user_name'] ?? 'Unknown',
            'nip' => $izin['nip'] ?? '-',
            'total' => 0
        ];
    }
    $userCutiCount[$userId]['total']++;
}

// Sorting
arsort($jenisCutiCount);
uasort($userCutiCount, function($a, $b) {
    return $b['total'] - $a['total'];
});

$currentPage = 'admin-laporan';
include __DIR__ . '/../includes/header.php';
?>

<style>
@media (max-width: 768px) {
    .laporan-grid { grid-template-columns: 1fr !important; }
    .laporan-stat { grid-template-columns: 1fr 1fr !important; }
    .filter-date-laporan { flex-direction: column; align-items: stretch !important; }
    .filter-date-laporan form { flex-direction: column; }
}
</style>

<div class="layout-with-sidebar page-with-mobile-nav">
    <aside class="sidebar">
        <div class="sidebar-logo">Magang<span>.usg</span><br><small style="font-size:11px;font-weight:400;color:rgba(255,255,255,.4);">Admin Panel</small></div>
        <div class="sidebar-item" onclick="window.location.href='index.php'"><i class="ri-file-search-line"></i> Review</div>
        <div class="sidebar-item" onclick="window.location.href='riwayat.php'"><i class="ri-history-line"></i> Riwayat</div>
        <div class="sidebar-item active"><i class="ri-file-chart-line"></i> Laporan</div>
        <div class="sidebar-item" onclick="window.location.href='../user/profile.php'"><i class="ri-user-line"></i> Profil</div>
        <div class="sidebar-bottom">
            <div class="sidebar-pdf-btn" onclick="window.location.href='cetak_pdf.php'"><i class="ri-printer-line"></i> Cetak PDF</div>
           
            <div class="sidebar-item" onclick="window.location.href='../auth/logout.php'"><i class="ri-logout-box-line"></i> Keluar</div>
        </div>
    </aside>

    <main class="main-content">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
            <div>
                <h1 style="font-size:24px;font-weight:800;margin-bottom:2px;">Laporan Cuti</h1>
                <p style="color:#888;font-size:13px;">Data pengajuan cuti karyawan</p>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a href="?tanggal=<?= date('Y-m-d') ?>" class="btn btn-outline btn-sm">Hari Ini</a>
                <a href="?bulan=<?= date('Y-m') ?>" class="btn btn-outline btn-sm">Bulan Ini</a>
                <a href="?" class="btn btn-primary btn-sm">Semua</a>
                <button class="btn btn-gold btn-sm" onclick="window.location.href='cetak_pdf.php'"><i class="ri-printer-line"></i> PDF</button>
            </div>
        </div>

        <!-- Filter Tanggal -->
        <div class="filter-date-laporan" style="margin-bottom:16px;padding:12px 16px;background:#f8f8f8;border-radius:8px;border:1px solid #eee;">
            <form method="GET" action="" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <label style="font-size:12px;color:#888;">Filter:</label>
                <input type="date" name="tanggal" value="<?= $filterTanggal ?>" style="padding:5px 10px;border:1px solid #ddd;border-radius:4px;font-size:13px;">
                <span style="color:#888;font-size:13px;">atau</span>
                <input type="month" name="bulan" value="<?= $filterBulan ?>" style="padding:5px 10px;border:1px solid #ddd;border-radius:4px;font-size:13px;">
                <button type="submit" class="btn-filter" style="padding:5px 14px;background:#B8860B;color:#fff;border:none;border-radius:4px;font-size:13px;cursor:pointer;">Filter</button>
                <a href="?" class="btn-reset" style="padding:5px 14px;background:#f0f0f0;color:#333;border:1px solid #ddd;border-radius:4px;font-size:13px;cursor:pointer;text-decoration:none;">Reset</a>
            </form>
        </div>

        <!-- Statistik -->
        <div class="laporan-stat" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;">
            <div class="card" style="padding:14px;text-align:center;">
                <div style="font-size:24px;font-weight:800;"><?= $totalCuti ?></div>
                <div style="font-size:12px;color:#888;">Total</div>
            </div>
            <div class="card" style="padding:14px;text-align:center;border-bottom:3px solid #B8860B;">
                <div style="font-size:24px;font-weight:800;color:#B8860B;"><?= $statusCount['Menunggu'] ?></div>
                <div style="font-size:12px;color:#888;">Menunggu</div>
            </div>
            <div class="card" style="padding:14px;text-align:center;border-bottom:3px solid #2D7A4F;">
                <div style="font-size:24px;font-weight:800;color:#2D7A4F;"><?= $statusCount['Disetujui'] ?></div>
                <div style="font-size:12px;color:#888;">Disetujui</div>
            </div>
            <div class="card" style="padding:14px;text-align:center;border-bottom:3px solid #C0392B;">
                <div style="font-size:24px;font-weight:800;color:#C0392B;"><?= $statusCount['Ditolak'] ?></div>
                <div style="font-size:12px;color:#888;">Ditolak</div>
            </div>
        </div>

        <div class="laporan-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
            <!-- Jenis Cuti -->
            <div class="card" style="padding:16px;">
                <h3 style="font-size:14px;font-weight:700;margin-bottom:10px;">Jenis Cuti</h3>
                <?php if (empty($jenisCutiCount)): ?>
                    <p style="color:#999;font-size:13px;">Tidak ada data</p>
                <?php else: ?>
                    <?php foreach ($jenisCutiCount as $jenis => $count): ?>
                        <div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid #f0f0f0;font-size:13px;">
                            <span><?= escape($jenis) ?></span>
                            <span style="font-weight:600;"><?= $count ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Status Persentase -->
            <div class="card" style="padding:16px;">
                <h3 style="font-size:14px;font-weight:700;margin-bottom:10px;">Persentase</h3>
                <?php if ($totalCuti > 0): ?>
                    <div style="margin-bottom:6px;">
                        <div style="display:flex;justify-content:space-between;font-size:13px;"><span>Disetujui</span><span style="color:#2D7A4F;font-weight:700;"><?= round(($statusCount['Disetujui'] / $totalCuti) * 100, 1) ?>%</span></div>
                        <div style="background:#eee;height:6px;border-radius:3px;overflow:hidden;"><div style="background:#2D7A4F;height:100%;width:<?= round(($statusCount['Disetujui'] / $totalCuti) * 100, 1) ?>%;"></div></div>
                    </div>
                    <div style="margin-bottom:6px;">
                        <div style="display:flex;justify-content:space-between;font-size:13px;"><span>Ditolak</span><span style="color:#C0392B;font-weight:700;"><?= round(($statusCount['Ditolak'] / $totalCuti) * 100, 1) ?>%</span></div>
                        <div style="background:#eee;height:6px;border-radius:3px;overflow:hidden;"><div style="background:#C0392B;height:100%;width:<?= round(($statusCount['Ditolak'] / $totalCuti) * 100, 1) ?>%;"></div></div>
                    </div>
                    <div>
                        <div style="display:flex;justify-content:space-between;font-size:13px;"><span>Menunggu</span><span style="color:#B8860B;font-weight:700;"><?= round(($statusCount['Menunggu'] / $totalCuti) * 100, 1) ?>%</span></div>
                        <div style="background:#eee;height:6px;border-radius:3px;overflow:hidden;"><div style="background:#B8860B;height:100%;width:<?= round(($statusCount['Menunggu'] / $totalCuti) * 100, 1) ?>%;"></div></div>
                    </div>
                <?php else: ?>
                    <p style="color:#999;font-size:13px;">Tidak ada data</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- 🔥 DATA KARYAWAN AMBIL CUTI -->
        <div class="card" style="padding:16px;margin-bottom:20px;">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
                <h3 style="font-size:14px;font-weight:700;">Data Karyawan Ambil Cuti</h3>
                <span style="font-size:12px;color:#888;">Total: <?= count($userCutiCount) ?> karyawan</span>
            </div>
            <?php if (empty($userCutiCount)): ?>
                <p style="color:#999;font-size:13px;">Tidak ada data</p>
            <?php else: ?>
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:13px;">
                        <thead>
                            <tr>
                                <th style="padding:6px 10px;text-align:left;background:#f8f8f8;border-bottom:2px solid #ddd;">No</th>
                                <th style="padding:6px 10px;text-align:left;background:#f8f8f8;border-bottom:2px solid #ddd;">Nama</th>
                                <th style="padding:6px 10px;text-align:left;background:#f8f8f8;border-bottom:2px solid #ddd;">NIP</th>
                                <th style="padding:6px 10px;text-align:left;background:#f8f8f8;border-bottom:2px solid #ddd;">Total Cuti</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($userCutiCount as $uid => $userData): ?>
                                <tr>
                                    <td style="padding:4px 10px;border-bottom:1px solid #eee;"><?= $no++ ?></td>
                                    <td style="padding:4px 10px;border-bottom:1px solid #eee;"><strong><?= escape($userData['name']) ?></strong></td>
                                    <td style="padding:4px 10px;border-bottom:1px solid #eee;"><?= escape($userData['nip']) ?></td>
                                    <td style="padding:4px 10px;border-bottom:1px solid #eee;font-weight:700;color:#B8860B;"><?= $userData['total'] ?> hari</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

      
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

