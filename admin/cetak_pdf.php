<?php
/**
 * =====================================================
 * FILE: admin_cetak_pdf.php
 * FUNGSI: Cetak Laporan PDF - Versi Profesional
 * VERSION: 2.0 - Executive Report Style
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

// =====================================================
// AMBIL DATA
// =====================================================

$allPermohonan = $database->getReference('permohonan')->getValue();
$allUsers = $database->getReference('users')->getValue();

$data = [];
$statusCount = ['Menunggu' => 0, 'Disetujui' => 0, 'Ditolak' => 0, 'Selesai' => 0];
$jenisCutiCount = [];
$bulanan = [];
$departemenCount = [];
$totalDurasi = 0;

if (is_array($allPermohonan) && !empty($allPermohonan)) {
    foreach ($allPermohonan as $key => $izin) {
        if (!is_array($izin)) continue;
        $izin['_key'] = $key;
        $data[] = $izin;
        
        $status = $izin['status'] ?? 'Menunggu';
        if (isset($statusCount[$status])) $statusCount[$status]++;
        
        $jenis = $izin['jenis_cuti'] ?? 'Lainnya';
        if (!isset($jenisCutiCount[$jenis])) $jenisCutiCount[$jenis] = 0;
        $jenisCutiCount[$jenis]++;
        
        $bulan = date('Y-m', strtotime($izin['created_at'] ?? 'now'));
        if (!isset($bulanan[$bulan])) $bulanan[$bulan] = 0;
        $bulanan[$bulan]++;
        
        $dep = $izin['departemen'] ?? 'Umum';
        if (!isset($departemenCount[$dep])) $departemenCount[$dep] = 0;
        $departemenCount[$dep]++;
        
        $totalDurasi += (int)($izin['durasi'] ?? 0);
    }
}

// Urutkan dari yang terbaru
usort($data, function($a, $b) {
    return strtotime($b['created_at'] ?? '1970-01-01') - strtotime($a['created_at'] ?? '1970-01-01');
});

$totalCuti = count($data);
$persentaseDisetujui = $totalCuti > 0 ? round(($statusCount['Disetujui'] / $totalCuti) * 100, 1) : 0;
$persentaseDitolak = $totalCuti > 0 ? round(($statusCount['Ditolak'] / $totalCuti) * 100, 1) : 0;
$persentaseMenunggu = $totalCuti > 0 ? round(($statusCount['Menunggu'] / $totalCuti) * 100, 1) : 0;
$avgDurasi = $totalCuti > 0 ? round($totalDurasi / $totalCuti, 1) : 0;
$totalUsers = is_array($allUsers) ? count($allUsers) : 0;

// Ranking jenis cuti
arsort($jenisCutiCount);
$topJenis = array_slice($jenisCutiCount, 0, 3);

// Ranking departemen
arsort($departemenCount);
$topDepartemen = array_slice($departemenCount, 0, 3);

// =====================================================
// BUAT HTML UNTUK PDF
// =====================================================

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan Eksekutif Cuti - sistem.perizinan.cuti</title>
    <style>
        /* ========== RESET ========== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: "Segoe UI", Arial, sans-serif; 
            font-size: 11px; 
            color: #1A1A1A; 
            background: #fff;
            padding: 30px;
            line-height: 1.5;
        }
        
        /* ========== COVER ========== */
        .cover-page {
            page-break-after: always;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 40px;
            border-bottom: 3px solid #B8860B;
        }
        .cover-page .logo {
            font-size: 42px;
            font-weight: 800;
            letter-spacing: -1px;
            color: #1A1A1A;
        }
        .cover-page .logo span { color: #B8860B; }
        .cover-page .subtitle {
            font-size: 18px;
            color: #6B6B6B;
            margin-top: 8px;
            font-weight: 300;
        }
        .cover-page .divider {
            width: 80px;
            height: 3px;
            background: #B8860B;
            margin: 24px auto;
            border-radius: 2px;
        }
        .cover-page .report-title {
            font-size: 28px;
            font-weight: 700;
            margin-top: 16px;
        }
        .cover-page .report-meta {
            margin-top: 32px;
            font-size: 13px;
            color: #6B6B6B;
        }
        .cover-page .report-meta .meta-item {
            margin: 4px 0;
        }
        .cover-page .footer-text {
            margin-top: 48px;
            font-size: 11px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 16px;
            width: 100%;
        }
        
        /* ========== HEADER ========== */
        .report-header {
            border-bottom: 2px solid #B8860B;
            padding-bottom: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .report-header .left .title {
            font-size: 18px;
            font-weight: 700;
            color: #1A1A1A;
        }
        .report-header .left .title span { color: #B8860B; }
        .report-header .left .sub {
            font-size: 11px;
            color: #6B6B6B;
            margin-top: 2px;
        }
        .report-header .right {
            text-align: right;
            font-size: 10px;
            color: #6B6B6B;
        }
        .report-header .right .date {
            font-weight: 600;
            color: #1A1A1A;
        }
        
        /* ========== SECTION ========== */
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #1A1A1A;
            margin-bottom: 10px;
            padding-bottom: 4px;
            border-bottom: 1px solid #eee;
        }
        .section-title .icon { margin-right: 6px; }
        
        /* ========== STATS GRID ========== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }
        .stat-box {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 14px 16px;
            text-align: center;
            border: 1px solid #eee;
        }
        .stat-box .number {
            font-size: 24px;
            font-weight: 800;
            color: #1A1A1A;
            line-height: 1.2;
        }
        .stat-box .number.gold { color: #B8860B; }
        .stat-box .number.green { color: #2D7A4F; }
        .stat-box .number.red { color: #C0392B; }
        .stat-box .number.blue { color: #3498DB; }
        .stat-box .label {
            font-size: 10px;
            color: #6B6B6B;
            margin-top: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-box .sub-label {
            font-size: 9px;
            color: #999;
            margin-top: 2px;
        }
        
        /* ========== TABLE ========== */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0 12px 0;
            font-size: 10px;
        }
        table th {
            background: #f0f0f0;
            padding: 8px 10px;
            text-align: left;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.5px;
            color: #1A1A1A;
            border-bottom: 2px solid #ddd;
        }
        table td {
            padding: 6px 10px;
            border-bottom: 1px solid #eee;
        }
        table tr:last-child td { border-bottom: none; }
        table .badge {
            display: inline-block;
            padding: 1px 10px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: 600;
        }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-dark { background: #e2e3e5; color: #383d41; }
        
        /* ========== CHARTS ========== */
        .chart-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 12px 0;
        }
        .chart-container {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 14px 16px;
            border: 1px solid #eee;
        }
        .chart-container .chart-title {
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1A1A1A;
        }
        .bar-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 5px;
        }
        .bar-item .bar-label {
            font-size: 10px;
            min-width: 70px;
            color: #333;
        }
        .bar-item .bar-track {
            flex: 1;
            height: 16px;
            background: #e9ecef;
            border-radius: 8px;
            overflow: hidden;
        }
        .bar-item .bar-track .bar-fill {
            height: 100%;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 6px;
            font-size: 8px;
            font-weight: 600;
            color: #fff;
        }
        .bar-item .bar-value {
            font-size: 10px;
            font-weight: 600;
            min-width: 30px;
            text-align: right;
        }
        
        /* ========== INSIGHT ========== */
        .insight-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin: 12px 0;
        }
        .insight-item {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 12px 14px;
            text-align: center;
            border: 1px solid #eee;
        }
        .insight-item .big-number {
            font-size: 22px;
            font-weight: 800;
        }
        .insight-item .big-number.gold { color: #B8860B; }
        .insight-item .big-number.green { color: #2D7A4F; }
        .insight-item .big-number.blue { color: #3498DB; }
        .insight-item .insight-label {
            font-size: 10px;
            color: #6B6B6B;
            margin-top: 2px;
        }
        
        /* ========== FOOTER ========== */
        .report-footer {
            margin-top: 24px;
            padding-top: 12px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            color: #999;
        }
        
        /* ========== PRINT ========== */
        @page {
            margin: 20px;
            size: A4 portrait;
        }
        .no-print { display: none; }
        .page-break { page-break-after: always; }
        
        /* ========== RESPONSIVE ========== */
        @media print {
            body { padding: 0; }
            .cover-page { min-height: 80vh; }
            .stats-grid { grid-template-columns: repeat(4, 1fr); }
            .chart-row { grid-template-columns: 1fr 1fr; }
            .insight-grid { grid-template-columns: repeat(3, 1fr); }
        }
    </style>
</head>
<body>';

// =====================================================
// COVER PAGE
// =====================================================
$html .= '
<!-- ========== COVER PAGE ========== -->
<div class="cover-page">
    <div class="logo">Magang<span>.usg</span></div>
    <div class="subtitle">Sistem Manajemen Cuti Karyawan</div>
    <div class="divider"></div>
    <div class="report-title">LAPORAN EKSEKUTIF</div>
    <div style="font-size:15px;color:#555;margin-top:4px;">Periode ' . date('F Y') . '</div>
    <div class="report-meta">
        <div class="meta-item"><strong>Tanggal Cetak:</strong> ' . date('d F Y H:i:s') . ' WIB</div>
        <div class="meta-item"><strong>Total Pengajuan:</strong> ' . $totalCuti . '</div>
        <div class="meta-item"><strong>Total Karyawan:</strong> ' . $totalUsers . '</div>
        <div class="meta-item"><strong>Status:</strong> ' . $statusCount['Disetujui'] . ' Disetujui · ' . $statusCount['Ditolak'] . ' Ditolak · ' . $statusCount['Menunggu'] . ' Menunggu</div>
    </div>
    <div class="footer-text">
        Laporan ini dibuat secara otomatis oleh sistem sistem.perizinan.cuti<br>
        © ' . date('Y') . ' sistem.perizinan.cuti - All Rights Reserved
    </div>
</div>';

// =====================================================
// PAGE 2 - EXECUTIVE SUMMARY
// =====================================================
$html .= '
<!-- ========== PAGE 2: EXECUTIVE SUMMARY ========== -->
<div class="page-break"></div>

<div class="report-header">
    <div class="left">
        <div class="title"> <span>Executive</span> Summary</div>
        <div class="sub">Ringkasan eksekutif pengajuan cuti karyawan</div>
    </div>
    <div class="right">
        <div class="date">' . date('d F Y') . '</div>
        <div>Halaman 2</div>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-box">
        <div class="number gold">' . $totalCuti . '</div>
        <div class="label">Total Pengajuan</div>
        <div class="sub-label">Semua waktu</div>
    </div>
    <div class="stat-box">
        <div class="number green">' . $statusCount['Disetujui'] . '</div>
        <div class="label">Disetujui</div>
        <div class="sub-label">' . $persentaseDisetujui . '% dari total</div>
    </div>
    <div class="stat-box">
        <div class="number red">' . $statusCount['Ditolak'] . '</div>
        <div class="label">Ditolak</div>
        <div class="sub-label">' . $persentaseDitolak . '% dari total</div>
    </div>
    <div class="stat-box">
        <div class="number blue">' . $avgDurasi . '</div>
        <div class="label">Rata-rata Durasi</div>
        <div class="sub-label">Hari per pengajuan</div>
    </div>
</div>

<!-- Insight Grid -->
<div class="insight-grid">
    <div class="insight-item">
        <div class="big-number gold">' . ($topJenis ? array_key_first($topJenis) : '-') . '</div>
        <div class="insight-label">Jenis Cuti Terbanyak</div>
        <div style="font-size:9px;color:#999;">' . ($topJenis ? current($topJenis) . ' pengajuan' : '') . '</div>
    </div>
    <div class="insight-item">
        <div class="big-number green">' . ($topDepartemen ? array_key_first($topDepartemen) : '-') . '</div>
        <div class="insight-label">Departemen Teraktif</div>
        <div style="font-size:9px;color:#999;">' . ($topDepartemen ? current($topDepartemen) . ' pengajuan' : '') . '</div>
    </div>
    <div class="insight-item">
        <div class="big-number blue">' . ($totalUsers > 0 ? round($totalCuti / $totalUsers, 1) : 0) . '</div>
        <div class="insight-label">Rata-rata per Karyawan</div>
        <div style="font-size:9px;color:#999;">Pengajuan per orang</div>
    </div>
</div>

<!-- Chart Row -->
<div class="chart-row">
    <div class="chart-container">
        <div class="chart-title"> Jenis Cuti Terbanyak</div>';

$maxJenis = max($jenisCutiCount) ?: 1;
$colors = ['#B8860B', '#2D7A4F', '#3498DB', '#8E44AD', '#E67E22', '#1ABC9C'];
$i = 0;
foreach ($jenisCutiCount as $jenis => $count):
    $pct = round(($count / $maxJenis) * 100, 1);
    $color = $colors[$i % count($colors)];
    $html .= '
        <div class="bar-item">
            <span class="bar-label">' . htmlspecialchars($jenis) . '</span>
            <div class="bar-track">
                <div class="bar-fill" style="width:' . $pct . '%;background:' . $color . ';">' . $count . '</div>
            </div>
            <span class="bar-value">' . $count . '</span>
        </div>';
    $i++;
endforeach;

$html .= '
    </div>
    <div class="chart-container">
        <div class="chart-title"> Departemen Teraktif</div>';

$maxDep = max($departemenCount) ?: 1;
$depColors = ['#B8860B', '#2D7A4F', '#3498DB', '#8E44AD', '#E67E22', '#1ABC9C'];
$i = 0;
foreach ($departemenCount as $dep => $count):
    $pct = round(($count / $maxDep) * 100, 1);
    $color = $depColors[$i % count($depColors)];
    $html .= '
        <div class="bar-item">
            <span class="bar-label">' . htmlspecialchars($dep) . '</span>
            <div class="bar-track">
                <div class="bar-fill" style="width:' . $pct . '%;background:' . $color . ';">' . $count . '</div>
            </div>
            <span class="bar-value">' . $count . '</span>
        </div>';
    $i++;
endforeach;

$html .= '
    </div>
</div>';

// =====================================================
// PAGE 3 - DETAIL DATA
// =====================================================
$html .= '
<!-- ========== PAGE 3: DETAIL DATA ========== -->
<div class="page-break"></div>

<div class="report-header">
    <div class="left">
        <div class="title"> <span>Detail</span> Pengajuan</div>
        <div class="sub">Daftar lengkap semua pengajuan cuti</div>
    </div>
    <div class="right">
        <div class="date">' . date('d F Y') . '</div>
        <div>Halaman 3</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:30px;">No</th>
            <th style="width:120px;">Pemohon</th>
            <th style="width:80px;">NIP</th>
            <th style="width:100px;">Jenis Cuti</th>
            <th style="width:80px;">Departemen</th>
            <th style="width:80px;">Tanggal</th>
            <th style="width:50px;">Durasi</th>
            <th style="width:80px;">Status</th>
            <th style="width:80px;">Reviewer</th>
        </tr>
    </thead>
    <tbody>';

if (empty($data)) {
    $html .= '<tr><td colspan="9" style="text-align:center;padding:20px;color:#999;">Belum ada data</td></tr>';
} else {
    $no = 1;
    $maxRows = 25;
    foreach ($data as $izin):
        if ($no > $maxRows) break;
        $status = $izin['status'] ?? 'Menunggu';
        $badgeClass = 'badge-warning';
        if ($status === 'Disetujui') $badgeClass = 'badge-success';
        elseif ($status === 'Ditolak') $badgeClass = 'badge-danger';
        elseif ($status === 'Selesai') $badgeClass = 'badge-dark';
        
        $html .= '
        <tr>
            <td>' . $no++ . '</td>
            <td>' . htmlspecialchars($izin['user_name'] ?? '-') . '</td>
            <td>' . htmlspecialchars($izin['nip'] ?? '-') . '</td>
            <td>' . htmlspecialchars($izin['jenis_cuti'] ?? '-') . '</td>
            <td>' . htmlspecialchars($izin['departemen'] ?? '-') . '</td>
            <td>' . ($izin['tanggal_mulai'] ?? '-') . '</td>
            <td style="text-align:center;">' . ($izin['durasi'] ?? 0) . ' h</td>
            <td><span class="badge ' . $badgeClass . '">' . $status . '</span></td>
            <td>' . htmlspecialchars($izin['reviewed_by'] ?? '-') . '</td>
        </tr>';
    endforeach;
    
    if (count($data) > $maxRows) {
        $html .= '<tr><td colspan="9" style="text-align:center;font-style:italic;color:#999;">... dan ' . (count($data) - $maxRows) . ' pengajuan lainnya</td></tr>';
    }
}

$html .= '
    </tbody>
</table>

<div style="font-size:9px;color:#999;text-align:right;margin-top:4px;">
    Menampilkan ' . min($maxRows, $totalCuti) . ' dari ' . $totalCuti . ' pengajuan
</div>';

// =====================================================
// PAGE 4 - STATUS DISTRIBUTION
// =====================================================
$html .= '
<!-- ========== PAGE 4: STATUS DISTRIBUTION ========== -->
<div class="page-break"></div>

<div class="report-header">
    <div class="left">
        <div class="title"> <span>Distribusi</span> Status</div>
        <div class="sub">Analisis persebaran status pengajuan</div>
    </div>
    <div class="right">
        <div class="date">' . date('d F Y') . '</div>
        <div>Halaman 4</div>
    </div>
</div>

<div class="chart-row">
    <div class="chart-container">
        <div class="chart-title">📊 Status Cuti</div>';

$statusColors = [
    'Disetujui' => '#2D7A4F',
    'Menunggu' => '#B8860B',
    'Ditolak' => '#C0392B',
    'Selesai' => '#6B6B6B'
];
$maxStatus = max($statusCount) ?: 1;
foreach ($statusCount as $status => $count):
    $pct = round(($count / $maxStatus) * 100, 1);
    $color = $statusColors[$status] ?? '#6B6B6B';
    $icon = $status === 'Disetujui' ? '✅' : ($status === 'Menunggu' ? '⏳' : ($status === 'Ditolak' ? '❌' : '📋'));
    $html .= '
    <div class="bar-item">
        <span class="bar-label">' . $icon . ' ' . $status . '</span>
        <div class="bar-track">
            <div class="bar-fill" style="width:' . $pct . '%;background:' . $color . ';">' . $count . '</div>
        </div>
        <span class="bar-value">' . $count . '</span>
    </div>';
endforeach;

$html .= '
    </div>
    <div class="chart-container">
        <div class="chart-title">📅 Tren Bulanan</div>';

$maxBulan = max($bulanan) ?: 1;
$bulanColors = ['#B8860B', '#2D7A4F', '#3498DB', '#8E44AD', '#E67E22', '#1ABC9C'];
$i = 0;
ksort($bulanan);
$bulanan = array_slice($bulanan, -6, 6, true);
foreach ($bulanan as $bulan => $count):
    $pct = round(($count / $maxBulan) * 100, 1);
    $color = $bulanColors[$i % count($bulanColors)];
    $html .= '
    <div class="bar-item">
        <span class="bar-label">' . date('M Y', strtotime($bulan . '-01')) . '</span>
        <div class="bar-track">
            <div class="bar-fill" style="width:' . $pct . '%;background:' . $color . ';">' . $count . '</div>
        </div>
        <span class="bar-value">' . $count . '</span>
    </div>';
    $i++;
endforeach;

$html .= '
    </div>
</div>

<!-- Summary Footer -->
<div style="margin-top:20px;padding:12px 16px;background:#f8f9fa;border-radius:6px;border:1px solid #eee;">
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;font-size:10px;">
        <div><strong>✅ Disetujui:</strong> ' . $statusCount['Disetujui'] . ' (' . $persentaseDisetujui . '%)</div>
        <div><strong>⏳ Menunggu:</strong> ' . $statusCount['Menunggu'] . ' (' . $persentaseMenunggu . '%)</div>
        <div><strong>❌ Ditolak:</strong> ' . $statusCount['Ditolak'] . ' (' . $persentaseDitolak . '%)</div>
        <div><strong>📋 Selesai:</strong> ' . $statusCount['Selesai'] . '</div>
    </div>
</div>';

// =====================================================
// FOOTER
// =====================================================
$html .= '
<!-- ========== FOOTER ========== -->
<div class="report-footer">
    <span>© ' . date('Y') . ' sistem.perizinan.cuti - Sistem Manajemen Cuti</span>
    <span>Laporan dibuat otomatis oleh sistem | ' . date('d/m/Y H:i:s') . '</span>
</div>

</body>
</html>';

// =====================================================
// TAMPILKAN HTML
// =====================================================

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan PDF - sistem.perizinan.cuti</title>
    <style>
        body { 
            font-family: "Segoe UI", Arial, sans-serif; 
            padding: 20px; 
            background: #f0f0f0;
        }
        .toolbar {
            position: sticky;
            top: 0;
            background: #fff;
            padding: 12px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.10);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            z-index: 100;
            border: 1px solid #e0e0e0;
        }
        .toolbar .title {
            font-family: "Segoe UI", Arial, sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: #1A1A1A;
        }
        .toolbar .title span { color: #B8860B; }
        .toolbar .btn-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .toolbar .btn {
            padding: 8px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .toolbar .btn-primary {
            background: #B8860B;
            color: #fff;
        }
        .toolbar .btn-primary:hover { background: #8B6508; transform: translateY(-1px); }
        .toolbar .btn-secondary {
            background: #6c757d;
            color: #fff;
        }
        .toolbar .btn-secondary:hover { background: #5a6268; }
        .toolbar .btn-success {
            background: #2D7A4F;
            color: #fff;
        }
        .toolbar .btn-success:hover { background: #1a5a3a; }
        .toolbar .btn-danger {
            background: #C0392B;
            color: #fff;
        }
        .toolbar .btn-danger:hover { background: #922B21; }
        .toolbar .info {
            font-size: 12px;
            color: #6B6B6B;
        }
        .preview-container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            padding: 10px;
            border: 1px solid #e0e0e0;
        }
        .preview-container iframe {
            width: 100%;
            height: 90vh;
            border: none;
            border-radius: 6px;
        }
        @media print {
            .toolbar { display: none; }
            body { background: #fff; padding: 0; }
            .preview-container { box-shadow: none; border: none; padding: 0; }
        }
        @media (max-width: 768px) {
            .toolbar { flex-direction: column; align-items: stretch; }
            .toolbar .btn-group { justify-content: center; }
        }
    </style>
</head>
<body>

<div class="toolbar">
    <div class="title">📄 <span>Cetak</span> Laporan PDF</div>
    <div class="btn-group">
        <button class="btn btn-primary" onclick="window.print()">
            🖨️ Cetak / Save as PDF
        </button>
        <button class="btn btn-secondary" onclick="window.location.href='laporan.php'">
            ⬅️ Kembali
        </button>
        <button class="btn btn-success" onclick="window.location.reload()">
            🔄 Refresh
        </button>
    </div>
    <div class="info">
        <i class="ri-information-line"></i> Klik tombol cetak untuk menyimpan sebagai PDF
    </div>
</div>

<div class="preview-container">
    <iframe srcdoc="<?= htmlspecialchars($html) ?>"></iframe>
</div>

<script>
// Auto print setelah 2 detik (opsional)
// setTimeout(function() { window.print(); }, 2000);
</script>

</body>
</html>

