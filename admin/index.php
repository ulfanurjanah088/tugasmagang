<?php
/**
 * =====================================================
 * FILE: admin/index.php
 * FUNGSI: Admin Panel - Review Cuti + Kelola User
 * VERSION: FINAL
 * =====================================================
 */

// 🔥 FIX PATH
require_once __DIR__ . '/../config/firebase.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notifikasi.php';

// Cek login
if (!$auth->isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

// Cek role admin
if (!$auth->isAdmin()) {
    header('Location: ../user/index.php');
    exit;
}

$user = $auth->getCurrentUser();
$uid = $auth->getCurrentUid();
$database = FirebaseConfig::getDatabase();

// =====================================================
// PROSES REVIEW CUTI
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_cuti'])) {
    $id = $_POST['id'] ?? '';
    $status = $_POST['status'] ?? '';
    $catatan = trim($_POST['catatan'] ?? '');
    
    if ($id && $status) {
        try {
            $cutiData = $database->getReference('permohonan/' . $id)->getValue();
            
            $updateData = [
                'status' => $status,
                'reviewed_by' => $user['name'] ?? 'Admin',
                'reviewed_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            if (!empty($catatan)) $updateData['catatan_admin'] = $catatan;
            
            $database->getReference('permohonan/' . $id)->update($updateData);
            
            if ($status === 'Disetujui') {
                $durasi = (int)($cutiData['durasi'] ?? 0);
                $userId = $cutiData['user_id'] ?? '';
                if ($userId && $durasi > 0) {
                    updateSisaCuti($userId, $durasi, $database);
                }
            }
            
            NotifikasiManager::notifikasiStatusBerubah($database, $cutiData['user_id'] ?? '', $cutiData, $status, $catatan);
            $_SESSION['flash_message'] = 'Pengajuan cuti berhasil di-' . strtolower($status) . '!';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } catch (Exception $e) {
            $error = 'Gagal update data: ' . $e->getMessage();
        }
    }
}

// =====================================================
// PROSES TAMBAH SISA CUTI
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_cuti'])) {
    $userId = $_POST['user_id'] ?? '';
    $tambah = (int)($_POST['tambah'] ?? 0);
    
    if ($userId && $tambah > 0) {
        $sisaBaru = tambahSisaCuti($userId, $tambah, $database);
        if ($sisaBaru !== false) {
            $_SESSION['flash_message'] = "✅ Sisa cuti berhasil ditambah $tambah hari!";
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error = 'Pilih karyawan dan masukkan jumlah hari';
    }
}

// =====================================================
// AMBIL DATA
// =====================================================

$allRequests = $database->getReference('permohonan')->getValue();

$pendingRequests = [];
if (is_array($allRequests) && !empty($allRequests)) {
    foreach ($allRequests as $key => $request) {
        if (!is_array($request)) continue;
        if (($request['status'] ?? '') === 'Menunggu') {
            $pendingRequests[$key] = $request;
        }
    }
}

$stats = ['total' => 0, 'menunggu' => 0, 'disetujui' => 0, 'ditolak' => 0, 'selesai' => 0];
if (is_array($allRequests) && !empty($allRequests)) {
    foreach ($allRequests as $request) {
        if (!is_array($request)) continue;
        $stats['total']++;
        $status = $request['status'] ?? '';
        switch ($status) {
            case 'Menunggu': $stats['menunggu']++; break;
            case 'Disetujui': $stats['disetujui']++; break;
            case 'Ditolak': $stats['ditolak']++; break;
            case 'Selesai': $stats['selesai']++; break;
        }
    }
}

$allUsers = $database->getReference('users')->getValue();
if (!is_array($allUsers)) $allUsers = [];

$currentPage = 'admin';

// 🔥 FIX: Path ke header
include __DIR__ . '/../includes/header.php';
?>

<style>
@media (max-width: 768px) {
    .admin-stats-grid { grid-template-columns: 1fr 1fr !important; gap: 8px !important; }
    .admin-stats-grid .card { padding: 12px !important; }
    .panel-header-card { padding: 16px !important; }
    .panel-header-card h2 { font-size: 20px !important; }
    .panel-stats-row { flex-wrap: wrap; gap: 8px; }
    .panel-stat { min-width: 80px; padding: 8px 12px; }
    .data-table { font-size: 12px; }
    .data-table th, .data-table td { padding: 6px 8px !important; }
    .kelola-form { grid-template-columns: 1fr !important; }
    .modal-box { max-width: 100% !important; margin: 10px !important; }
    .modal-footer { flex-direction: column !important; }
}
</style>

<div class="layout-with-sidebar page-with-mobile-nav">
    <aside class="sidebar">
        <div class="sidebar-logo">
            sistem-perizinan<span>.cuti</span>
            <br><small style="font-size:11px;font-weight:400;color:rgba(255,255,255,.4);">Admin Panel</small>
        </div>
        
        <!-- 🔥 MENU ADMIN -->
        <div class="sidebar-item active"><i class="ri-file-search-line"></i> Review Cuti</div>
        <div class="sidebar-item" onclick="window.location.href='riwayat.php'"><i class="ri-history-line"></i> Riwayat</div>
        <div class="sidebar-item" onclick="window.location.href='laporan.php'"><i class="ri-file-chart-line"></i> Laporan</div>
        
        <!-- 🔥 KELOLA USER - Menu terpisah -->
        <div class="sidebar-item" onclick="document.getElementById('kelola-user').scrollIntoView()">
            <i class="ri-user-settings-line"></i> Kelola User
        </div>
        
        <div class="sidebar-item" onclick="window.location.href='../user/profile.php'"><i class="ri-user-line"></i> Profil</div>
        <div class="sidebar-bottom">
            <div class="sidebar-pdf-btn" onclick="window.location.href='cetak_pdf.php'"><i class="ri-printer-line"></i> Cetak Laporan PDF</div>
           
            <div class="sidebar-item" onclick="window.location.href='../auth/logout.php'"><i class="ri-logout-box-line"></i> Keluar</div>
        </div>
    </aside>

    <main class="main-content">
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div style="background:#E8F5EE;border:1px solid #2D7A4F;border-radius:var(--r-md);padding:12px 16px;margin-bottom:16px;color:#2D7A4F;font-size:13px;">
                <i class="ri-checkbox-circle-line"></i> <?= $_SESSION['flash_message']; unset($_SESSION['flash_message']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div style="background:#FDECEA;border:1px solid #C0392B;border-radius:var(--r-md);padding:12px 16px;margin-bottom:16px;color:#C0392B;font-size:13px;">
                <i class="ri-error-warning-line"></i> <?= escape($error) ?>
            </div>
        <?php endif; ?>

        <!-- =====================================================
             SECTION 1: REVIEW CUTI
             ===================================================== -->
        <div class="panel-header-card">
            <div style="display:grid;grid-template-columns:1fr auto auto;gap:16px;align-items:start;flex-wrap:wrap;">
                <div>
                    <h2>Panel Review Cuti</h2>
                    <p>Tinjau dan verifikasi pengajuan cuti karyawan</p>
                    <div class="panel-stats-row">
                        <div class="panel-stat"><div class="panel-stat-label">Menunggu</div><div class="panel-stat-value"><?= $stats['menunggu'] ?></div></div>
                        <div class="panel-stat"><div class="panel-stat-label">Total</div><div class="panel-stat-value"><?= $stats['total'] ?></div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-card">
            <div class="section-card-header">
                <div><h3>Daftar Pengajuan Menunggu</h3><p>Membutuhkan tindakan verifikasi segera</p></div>
            </div>
            <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
            <table class="data-table">
                <thead><tr><th>Pemohon</th><th>Jenis Cuti</th><th>Tanggal</th><th>Durasi</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php if (empty($pendingRequests)): ?>
                        <tr><td colspan="5" style="text-align:center;padding:30px;color:#999;">Tidak ada pengajuan menunggu</td></tr>
                    <?php else: ?>
                        <?php foreach ($pendingRequests as $key => $request): ?>
                            <?php if (!is_array($request)) continue; ?>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <div style="width:32px;height:32px;border-radius:6px;background:#B8860B;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;"><?= strtoupper(substr($request['user_name'] ?? 'U', 0, 2)) ?></div>
                                        <div><div style="font-weight:600;"><?= escape($request['user_name'] ?? '-') ?></div><div style="font-size:11px;color:#999;">NIP: <?= escape($request['nip'] ?? '-') ?></div></div>
                                    </div>
                                </td>
                                <td><span class="badge badge-warning"><?= escape($request['jenis_cuti'] ?? '-') ?></span></td>
                                <td><?= formatTanggal($request['created_at'] ?? '') ?></td>
                                <td><?= $request['durasi'] ?? 0 ?> hari</td>
                                <td><button class="btn btn-outline btn-sm" onclick="openReviewModal('<?= $key ?>')">Tinjau</button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
</div>
        <!-- =====================================================
             SECTION 2: KELOLA USER - TAMBAH SISA CUTI
             ===================================================== -->
        <div id="kelola-user" style="margin-top:40px;padding-top:24px;border-top:2px solid var(--clr-border);scroll-margin-top:80px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
                <div>
                    <h2 style="font-family:var(--font-display);font-size:24px;font-weight:700;"> Kelola Sisa Cuti</h2>
                    <p style="color:var(--clr-muted);font-size:13px;">Tambah sisa cuti untuk karyawan tertentu</p>
                </div>
                <span style="font-size:12px;color:var(--clr-muted);">Total karyawan: <?= count($allUsers) ?></span>
            </div>

            <!-- Form Tambah Sisa Cuti -->
            <div class="card" style="padding:24px;margin-bottom:24px;background:var(--clr-bg);border:2px solid var(--clr-border);">
                <form method="POST" action="">
                    <input type="hidden" name="tambah_cuti" value="1">
                    <div class="kelola-form" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
                        <div class="form-group">
                            <label class="form-label">Pilih Karyawan <span style="color:red;">*</span></label>
                            <select name="user_id" class="form-control" required>
                                <option value="">-- Pilih Karyawan --</option>
                                <?php foreach ($allUsers as $uidUser => $dataUser): ?>
                                    <?php if (!is_array($dataUser)) continue; ?>
                                    <option value="<?= $uidUser ?>">
                                        <?= escape($dataUser['name'] ?? 'Unknown') ?> 
                                        (NIP: <?= escape($dataUser['nip'] ?? '-') ?>) 
                                        - Sisa: <?= $dataUser['sisa_cuti'] ?? 12 ?> hari
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tambah Hari <span style="color:red;">*</span></label>
                            <input type="number" name="tambah" class="form-control" placeholder="Contoh: 5" min="1" max="30" required>
                        </div>
                        <div style="display:flex;align-items:flex-end;">
                            <button type="submit" class="btn btn-gold btn-full">
                                <i class="ri-add-line"></i> Tambah Sisa Cuti
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Data Karyawan -->
            <div class="section-card">
                <div class="section-card-header">
                    <div>
                        <h3> Data Karyawan</h3>
                        <p>Semua karyawan dan sisa cuti mereka</p>
                    </div>
                </div>
                <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>NIP</th>
                            <th>Jabatan</th>
                            <th>Departemen</th>
                            <th>Sisa Cuti</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($allUsers)): ?>
                            <tr><td colspan="5" style="text-align:center;padding:30px;color:#999;">Belum ada data karyawan</td></tr>
                        <?php else: ?>
                            <?php foreach ($allUsers as $uidUser => $dataUser): ?>
                                <?php if (!is_array($dataUser)) continue; ?>
                                <tr>
                                    <td><strong><?= escape($dataUser['name'] ?? '-') ?></strong></td>
                                    <td><?= escape($dataUser['nip'] ?? '-') ?></td>
                                    <td><?= escape($dataUser['jabatan'] ?? '-') ?></td>
                                    <td><?= escape($dataUser['departemen'] ?? '-') ?></td>
                                    <td style="font-weight:700;color:var(--clr-primary);">
                                        <?= $dataUser['sisa_cuti'] ?? 12 ?> hari
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
     REVIEW MODAL
     ===================================================== -->
<div id="review-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#fff;border-radius:16px;max-width:560px;width:100%;max-height:90vh;overflow-y:auto;padding:0;">
        <div style="padding:18px 24px;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;">
            <h3 style="font-size:18px;font-weight:700;margin:0;"> Tinjau Pengajuan</h3>
            <button onclick="closeReviewModal()" style="background:none;border:none;font-size:24px;cursor:pointer;color:#999;">&times;</button>
        </div>
        <form method="POST" action="" id="review-form">
            <input type="hidden" name="review_cuti" value="1">
            <input type="hidden" name="id" id="review-id" value="">
            <div style="padding:24px;" id="review-content"><div style="text-align:center;padding:20px;color:#999;">Loading...</div></div>
            <div style="padding:16px 24px;border-top:1px solid #eee;display:flex;gap:12px;background:#fafafa;border-radius:0 0 16px 16px;flex-wrap:wrap;">
                <button type="button" onclick="closeReviewModal()" style="flex:1;padding:10px;border:1px solid #ddd;border-radius:8px;background:transparent;cursor:pointer;font-weight:600;">Batal</button>
                <button type="submit" name="status" value="Ditolak" style="flex:1;padding:10px;border:none;border-radius:8px;background:#C0392B;color:#fff;cursor:pointer;font-weight:600;">Tolak</button>
                <button type="submit" name="status" value="Disetujui" style="flex:1;padding:10px;border:none;border-radius:8px;background:#2D7A4F;color:#fff;cursor:pointer;font-weight:600;">Setujui</button>
            </div>
        </form>
    </div>
</div>

<script>
const pendingData = <?= json_encode($pendingRequests) ?>;

function openReviewModal(id) {
    const data = pendingData[id];
    if (!data) { alert('Data tidak ditemukan!'); return; }
    document.getElementById('review-id').value = id;
    let html = '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:13px;">';
    [['Nama', data.user_name || '-'], ['NIP', data.nip || '-'], ['Jenis Cuti', data.jenis_cuti || '-'], ['Durasi', (data.durasi || 0) + ' hari'], ['Tanggal', (data.tanggal_mulai || '-') + ' s/d ' + (data.tanggal_selesai || '-')], ['Alasan', data.alasan || '-']].forEach(f => {
        html += `<div ${f[0] === 'Alasan' ? 'style="grid-column:span 2;"' : ''}><div style="font-size:10px;color:#999;text-transform:uppercase;">${f[0]}</div><div style="font-weight:600;">${f[1]}</div></div>`;
    });
    if (data.dokumen) {
        html += `<div style="grid-column:span 2;margin-top:4px;"><a href="${data.dokumen}" target="_blank" style="color:#B8860B;text-decoration:underline;"> Lihat Dokumen</a></div>`;
    }
    html += `<div style="grid-column:span 2;margin-top:8px;"><div style="font-size:10px;color:#999;text-transform:uppercase;">Catatan Review <span style="color:red;">*</span></div><textarea name="catatan" id="review-catatan" rows="3" style="width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;font-family:inherit;resize:vertical;margin-top:4px;" placeholder="Masukkan catatan..." required></textarea></div>`;
    html += '</div>';
    document.getElementById('review-content').innerHTML = html;
    document.getElementById('review-modal').style.display = 'flex';
}

function closeReviewModal() { document.getElementById('review-modal').style.display = 'none'; }
document.addEventListener('click', function(e) { const modal = document.getElementById('review-modal'); if (modal && e.target === modal) closeReviewModal(); });
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') closeReviewModal(); });
document.getElementById('review-form').addEventListener('submit', function(e) {
    const catatan = document.getElementById('review-catatan');
    if (catatan && !catatan.value.trim()) { e.preventDefault(); alert('Mohon isi catatan review!'); catatan.focus(); }
});
</script>

<!-- 🔥 FIX: Path ke footer -->
<?php include __DIR__ . '/../includes/footer.php'; ?>