<?php
/**
 * =====================================================
 * FILE: profile.php
 * FUNGSI: Halaman Profil User
 * VERSION: FINAL - All Features ACTIVE
 * =====================================================
 */

require_once __DIR__ . '/../config/firebase.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (!$auth->isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

requireLogin($auth);

$user = $auth->getCurrentUser();
$uid = $auth->getCurrentUid();
$database = FirebaseConfig::getDatabase();

$isAdmin = isset($user['role']) && $user['role'] === 'admin';

if ($isAdmin && isset($_SESSION['user'])) {
    $_SESSION['user']['role'] = 'admin';
}

$sisaCuti = getSisaCuti($uid, $database);

$currentPage = 'profile';
include __DIR__ . '/../includes/header.php';
?>

<style>
.profile-container { max-width: 800px; margin: 0 auto; }
.profile-card {
    background: #fff;
    border: 1px solid #eee;
    border-radius: 12px;
    padding: 32px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: #B8860B;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 36px;
    margin: 0 auto 16px;
    border: 4px solid #eee;
}
.profile-name { text-align: center; font-size: 24px; font-weight: 700; margin-bottom: 4px; }
.profile-role { text-align: center; color: #888; font-size: 14px; margin-bottom: 24px; }
.profile-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
.profile-info .info-item {
    padding: 12px 16px;
    background: #f8f8f8;
    border-radius: 8px;
}
.profile-info .info-item .label {
    font-size: 11px;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}
.profile-info .info-item .value {
    font-size: 16px;
    font-weight: 600;
    margin-top: 2px;
    color: #1a1a1a;
}
.profile-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
    flex-wrap: wrap;
}
.profile-actions .btn { flex: 1; min-width: 120px; }

.badge {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}
.badge-warning { background: rgba(184,134,11,0.15); color: #B8860B; }
.badge-success { background: rgba(45,122,79,0.15); color: #2D7A4F; }
.badge-dark { background: #1a1a1a; color: #fff; }

.btn {
    padding: 8px 16px;
    border-radius: 6px;
    border: 1px solid #ddd;
    background: #fff;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    transition: 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    justify-content: center;
}
.btn:hover { background: #f5f5f0; }
.btn-outline { border-color: #ddd; color: #333; }
.btn-danger { background: #C0392B; color: #fff; border-color: #C0392B; }
.btn-danger:hover { background: #a93226; }
.btn-primary { background: #B8860B; color: #fff; border-color: #B8860B; }
.btn-primary:hover { background: #a0750a; }

/* SIDEBAR */
.layout-with-sidebar { display: flex; min-height: 100vh; }
.sidebar {
    width: 220px;
    background: #1a1a1a;
    padding: 20px 0;
    position: fixed;
    height: 100vh;
    overflow-y: auto;
    z-index: 100;
    display: flex;
    flex-direction: column;
}
.sidebar-logo {
    color: #fff;
    font-size: 18px;
    font-weight: 700;
    padding: 0 20px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    margin-bottom: 16px;
}
.sidebar-logo span { color: #B8860B; }
.sidebar-item {
    color: rgba(255,255,255,0.5);
    padding: 10px 20px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    transition: 0.2s;
    border-left: 3px solid transparent;
}
.sidebar-item:hover { background: rgba(255,255,255,0.03); color: #fff; }
.sidebar-item.active {
    color: #fff;
    background: rgba(255,255,255,0.05);
    border-left-color: #B8860B;
}
.sidebar-item i { font-size: 18px; }
.sidebar-bottom {
    margin-top: auto;
    border-top: 1px solid rgba(255,255,255,0.05);
    padding-top: 10px;
}
.main-content {
    margin-left: 220px;
    padding: 20px 24px 80px;
    flex: 1;
    min-height: 100vh;
}

/* MOBILE NAV */
.mobile-nav-bar {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #fff;
    border-top: 1px solid #eee;
    padding: 8px 0 env(safe-area-inset-bottom);
    z-index: 999;
    justify-content: space-around;
}
.mobile-nav-item {
    background: none;
    border: none;
    display: flex;
    flex-direction: column;
    align-items: center;
    font-size: 9px;
    color: #888;
    cursor: pointer;
    padding: 4px 8px;
    font-family: inherit;
    transition: 0.2s;
    position: relative;
}
.mobile-nav-item i { font-size: 18px; margin-bottom: 1px; }
.mobile-nav-item.active { color: #B8860B; }
.mobile-nav-item.active::after {
    content: '';
    position: absolute;
    top: -1px;
    left: 50%;
    transform: translateX(-50%);
    width: 20px;
    height: 3px;
    background: #B8860B;
    border-radius: 0 0 2px 2px;
}

/* TOAST */
.toast-notif {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 12px 20px;
    border-radius: 8px;
    color: #fff;
    font-size: 13px;
    font-weight: 500;
    z-index: 9999;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    max-width: 360px;
    animation: slideIn 0.3s ease;
    display: none;
    align-items: center;
    gap: 8px;
}
.toast-notif.success { background: #2D7A4F; }
.toast-notif.error { background: #C0392B; }
.toast-notif.info { background: #B8860B; }
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

/* MODAL */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}
.modal-overlay.active { display: flex; }
.modal-box {
    background: #fff;
    border-radius: 12px;
    padding: 32px;
    max-width: 420px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}
.modal-box h2 { margin-bottom: 16px; font-size: 22px; }
.modal-box .form-group { margin-bottom: 14px; }
.modal-box .form-label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #555;
    margin-bottom: 4px;
}
.modal-box .form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}
.modal-box .form-control:focus {
    outline: none;
    border-color: #B8860B;
    box-shadow: 0 0 0 3px rgba(184,134,11,0.1);
}
.modal-box .btn-row {
    display: flex;
    gap: 12px;
    margin-top: 8px;
}
.modal-box .btn-row .btn { flex: 1; justify-content: center; }
.alert {
    padding: 10px 14px;
    border-radius: 6px;
    margin-bottom: 12px;
    font-size: 13px;
}
.alert-success { background: #E8F5EE; border: 1px solid #2D7A4F; color: #2D7A4F; }
.alert-danger { background: #FDECEA; border: 1px solid #C0392B; color: #C0392B; }

@media (max-width: 768px) {
    .sidebar { display: none; }
    .main-content {
        margin-left: 0;
        padding: 12px 12px 70px;
    }
    .mobile-nav-bar { display: flex; }
    .profile-card { padding: 20px; }
    .profile-avatar { width: 72px; height: 72px; font-size: 28px; }
    .profile-name { font-size: 20px; }
    .profile-info { grid-template-columns: 1fr; }
    .profile-actions .btn { flex: 1 1 100%; }
    .modal-box { padding: 20px; }
}
</style>

<div class="layout-with-sidebar page-with-mobile-nav">
    <aside class="sidebar">
        <div class="sidebar-logo">
            Sistem Perizinan<span>Cuti</span>
            <br><small style="font-size:11px;font-weight:400;color:rgba(255,255,255,.4);">Cuti Karyawan</small>
        </div>
        
        <?php if ($isAdmin): ?>
            <div class="sidebar-item" onclick="location.href='../admin/index.php'"><i class="ri-file-search-line"></i> Review Cuti</div>
            <div class="sidebar-item" onclick="location.href='../admin/riwayat.php'"><i class="ri-history-line"></i> Riwayat Admin</div>
            <div class="sidebar-item" onclick="location.href='../admin/laporan.php'"><i class="ri-file-chart-line"></i> Laporan</div>
            <div class="sidebar-item active"><i class="ri-user-line"></i> Profil</div>
           
            <div class="sidebar-bottom">
                <div class="sidebar-item" onclick="location.href='../auth/logout.php'"><i class="ri-logout-box-line"></i> Keluar</div>
            </div>
        <?php else: ?>
            <div class="sidebar-item" onclick="location.href='index.php'"><i class="ri-dashboard-line"></i> Dashboard</div>
            <div class="sidebar-item" onclick="location.href='index.php#ajukan-cuti'"><i class="ri-add-circle-line"></i> Ajukan Cuti</div>
            <div class="sidebar-item" onclick="location.href='riwayat.php'"><i class="ri-history-line"></i> Riwayat</div>
            <div class="sidebar-item active"><i class="ri-user-line"></i> Profil</div>
            <div class="sidebar-bottom">
                <div class="sidebar-item" onclick="location.href='../auth/logout.php'"><i class="ri-logout-box-line"></i> Keluar</div>
            </div>
        <?php endif; ?>
    </aside>

    <main class="main-content">
        <div class="profile-container">
            <h1 style="font-size:28px;font-weight:800;margin-bottom:24px;">Profil Saya</h1>

            <div class="profile-card">
                <div class="profile-avatar">
                    <?= strtoupper(substr($user['name'] ?? 'U', 0, 2)) ?>
                </div>
                
                <div class="profile-name"><?= htmlspecialchars($user['name'] ?? 'User') ?></div>
                <div class="profile-role">
                    <?= htmlspecialchars($user['jabatan'] ?? 'Karyawan') ?> · 
                    <?= htmlspecialchars($user['departemen'] ?? '-') ?>
                    <?php if ($isAdmin): ?>
                        <span class="badge badge-warning" style="margin-left:8px;">Admin</span>
                    <?php else: ?>
                        <span class="badge badge-success" style="margin-left:8px;">Aktif</span>
                    <?php endif; ?>
                </div>

                <div class="profile-info">
                    <div class="info-item"><div class="label">Nama Lengkap</div><div class="value"><?= htmlspecialchars($user['name'] ?? '-') ?></div></div>
                    <div class="info-item"><div class="label">Email</div><div class="value"><?= htmlspecialchars($user['email'] ?? '-') ?></div></div>
                    <div class="info-item"><div class="label">NIP / NIK</div><div class="value"><?= htmlspecialchars($user['nip'] ?? '-') ?></div></div>
                    <div class="info-item"><div class="label">Jabatan</div><div class="value"><?= htmlspecialchars($user['jabatan'] ?? '-') ?></div></div>
                    <div class="info-item"><div class="label">Departemen</div><div class="value"><?= htmlspecialchars($user['departemen'] ?? '-') ?></div></div>
                    <div class="info-item" style="border-left:3px solid #B8860B;">
                        <div class="label">Sisa Cuti</div>
                        <div class="value" style="color:#B8860B;"><?= $sisaCuti ?> Hari</div>
                    </div>
                    <div class="info-item"><div class="label">Role</div><div class="value"><span class="badge badge-dark"><?= $user['role'] ?? 'User' ?></span></div></div>
                    <div class="info-item"><div class="label">Bergabung</div><div class="value"><?= formatTanggal($user['created_at'] ?? '') ?></div></div>
                </div>

                <!-- ========================================== -->
                <!-- ✅ PROFILE ACTIONS - SEMUA AKTIF! -->
                <!-- ========================================== -->
                <div class="profile-actions">
                    <!-- Edit Profil - KE edit_profil.php -->
                    
                    
                    <button class="btn btn-danger" onclick="if(confirm('Yakin ingin logout?')) location.href='../auth/logout.php'">
                        <i class="ri-logout-box-line"></i> Logout
                    </button>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- =====================================================
MODAL GANTI PASSWORD
============================================= -->
<div id="modal-ganti-password" class="modal-overlay">
    <div class="modal-box">
        <h2>Ganti Password</h2>
        
        <div id="ganti-password-result"></div>
        
        <form id="form-ganti-password" onsubmit="return gantiPassword(event)">
            <div class="form-group">
                <label class="form-label">Password Lama</label>
                <input type="password" id="old_password" class="form-control" required placeholder="Masukkan password lama">
            </div>
            <div class="form-group">
                <label class="form-label">Password Baru</label>
                <input type="password" id="new_password" class="form-control" required minlength="6" placeholder="Minimal 6 karakter">
            </div>
            <div class="form-group">
                <label class="form-label">Konfirmasi Password Baru</label>
                <input type="password" id="confirm_password" class="form-control" required placeholder="Ulangi password baru">
            </div>
            
            <div class="btn-row">
                <button type="submit" class="btn btn-primary" style="border:none;padding:10px;justify-content:center;">
                    <i class="ri-save-line"></i> Ganti Password
                </button>
                <button type="button" class="btn btn-outline" onclick="closeGantiPassword()" style="justify-content:center;">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MOBILE NAV -->
<nav class="mobile-nav-bar">
    <?php if ($isAdmin): ?>
        <button class="mobile-nav-item" onclick="location.href='../admin/index.php'"><i class="ri-file-search-line"></i>Review</button>
        <button class="mobile-nav-item" onclick="location.href='../admin/riwayat.php'"><i class="ri-history-line"></i>Riwayat</button>
        <button class="mobile-nav-item" onclick="location.href='../admin/laporan.php'"><i class="ri-file-chart-line"></i>Laporan</button>
        <button class="mobile-nav-item active"><i class="ri-user-line"></i>Profil</button>
        <button class="mobile-nav-item" onclick="location.href='../auth/logout.php'"><i class="ri-logout-box-line"></i>Keluar</button>
    <?php else: ?>
        <button class="mobile-nav-item" onclick="location.href='index.php'"><i class="ri-dashboard-line"></i>Dashboard</button>
        <button class="mobile-nav-item" onclick="location.href='riwayat.php'"><i class="ri-history-line"></i>Riwayat</button>
        <button class="mobile-nav-item active"><i class="ri-user-line"></i>Profil</button>
        <button class="mobile-nav-item" onclick="location.href='../auth/logout.php'"><i class="ri-logout-box-line"></i>Keluar</button>
    <?php endif; ?>
</nav>

<!-- TOAST -->
<div id="global-toast" class="toast-notif" style="display:none;"></div>

<script>
function showToast(message, type = 'info') {
    const toast = document.getElementById('global-toast');
    if (!toast) return;
    toast.textContent = message;
    toast.className = 'toast-notif ' + type;
    toast.style.display = 'flex';
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => {
        toast.style.display = 'none';
    }, 4000);
}

// ============================================
// MODAL GANTI PASSWORD
// ============================================
function openGantiPassword() {
    document.getElementById('modal-ganti-password').classList.add('active');
    document.getElementById('ganti-password-result').innerHTML = '';
    document.getElementById('form-ganti-password').reset();
}

function closeGantiPassword() {
    document.getElementById('modal-ganti-password').classList.remove('active');
}

function gantiPassword(e) {
    e.preventDefault();
    
    const oldPassword = document.getElementById('old_password').value;
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const resultDiv = document.getElementById('ganti-password-result');
    
    if (newPassword !== confirmPassword) {
        resultDiv.innerHTML = '<div class="alert alert-danger">❌ Password baru dan konfirmasi tidak sama!</div>';
        return false;
    }
    
    if (newPassword.length < 6) {
        resultDiv.innerHTML = '<div class="alert alert-danger">❌ Password baru minimal 6 karakter!</div>';
        return false;
    }
    
    fetch('../auth/ganti_password.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            old_password: oldPassword,
            new_password: newPassword
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = '<div class="alert alert-success">✅ ' + data.message + '</div>';
            setTimeout(() => {
                closeGantiPassword();
                showToast('Password berhasil diubah!', 'success');
            }, 2000);
        } else {
            resultDiv.innerHTML = '<div class="alert alert-danger">❌ ' + data.message + '</div>';
        }
    })
    .catch(error => {
        resultDiv.innerHTML = '<div class="alert alert-danger">❌ Terjadi kesalahan: ' + error.message + '</div>';
    });
    
    return false;
}

document.getElementById('modal-ganti-password').addEventListener('click', function(e) {
    if (e.target === this) {
        closeGantiPassword();
    }
});
</script>

<?php include __DIR__ .'/../includes/footer.php'; ?>
