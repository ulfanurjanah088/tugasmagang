<?php
/**
 * =====================================================
 * FILE: auth/login.php
 * FUNGSI: Halaman Login dan Registrasi
 * VERSION: 3.1 - Forgot Password ACTIVE
 * =====================================================
 */

// 🔥 FIX PATH - Naik satu level ke root
require_once __DIR__ . '/../config/firebase.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Jika sudah login, redirect sesuai role
if ($auth->isLoggedIn()) {
    if ($auth->isAdmin()) {
        header('Location: ../admin/index.php');
        exit;
    } else {
        header('Location: ../user/index.php');
        exit;
    }
}

// Inisialisasi variabel
$error = '';
$success = '';

// =====================================================
// PROSES LOGIN
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Mohon isi email dan password';
    } else {
        $userData = $auth->login($email, $password);
        
        if ($userData) {
            if ($userData['role'] === 'admin') {
                header('Location: ../admin/index.php');
                exit;
            } else {
                header('Location: ../user/index.php');
                exit;
            }
        } else {
            $error = 'Email atau password salah!';
        }
    }
}

// =====================================================
// PROSES REGISTRASI
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = trim($_POST['reg_name'] ?? '');
    $email = trim($_POST['reg_email'] ?? '');
    $password = $_POST['reg_password'] ?? '';
    $password_confirm = $_POST['reg_password_confirm'] ?? '';
    $nip = trim($_POST['reg_nip'] ?? '');
    $jabatan = trim($_POST['reg_jabatan'] ?? '');
    $departemen = trim($_POST['reg_departemen'] ?? '');
    
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Mohon isi semua field yang wajib';
    } elseif ($password !== $password_confirm) {
        $error = 'Konfirmasi password tidak cocok';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter';
    } else {
        $result = $auth->register($email, $password, $name, $nip, $jabatan, $departemen);
        
        if ($result === true) {
            $success = 'Registrasi berhasil! Silakan login.';
        } else {
            $error = $result;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>sistem.perizinan.cuti — Manajemen Cuti Karyawan</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.min.css">
    <style>
        /* ✅ Link Lupa Password - Style */
        .link-forgot {
            color: #B8860B;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: 0.2s;
        }
        .link-forgot:hover {
            text-decoration: underline;
            color: #a0750a;
        }
        .form-row-between {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .link-muted {
            color: #888;
            font-size: 13px;
            text-decoration: none;
        }
        .link-muted:hover {
            color: #B8860B;
            text-decoration: underline;
        }
    </style>
</head>
<body>

<!-- =====================================================
     PAGE: LOGIN
     ===================================================== -->
<div id="page-login" class="page active">
    <!-- DESKTOP HERO SIDE -->
    <div class="login-hero">
        <nav class="login-nav">
            <span style="font-family:var(--font-display);font-weight:700;font-size:16px;color:#fff">
                cuti<span style="color:var(--clr-primary)">modern</span>
            </span>
        </nav>
        <div class="login-hero-content">
            <span class="login-eyebrow">Edisi 2026</span>
            <h1>Sistem Perizinan<br><span class="brand">Cuti Karyawan</span></h1>
            <p>Sistem perizinan cuti karyawan yang modern, terintegrasi, dan sepenuhnya transparan untuk mendukung produktivitas tim Anda.</p>
            <div class="login-features">
                <div class="login-feature">
                    <div class="login-feature-icon"><i class="ri-shield-check-line"></i></div>
                    <div class="login-feature-text">
                        <h4>Verifikasi Otomatis</h4>
                        <p>Pengecekan dokumen cuti berbasis AI untuk efisiensi maksimal.</p>
                    </div>
                </div>
                <div class="login-feature">
                    <div class="login-feature-icon"><i class="ri-lock-2-line"></i></div>
                    <div class="login-feature-text">
                        <h4>Keamanan Terjamin</h4>
                        <p>Enkripsi data tingkat tinggi untuk semua dokumen sensitif.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT PANEL (desktop login card) -->
    <div class="login-panel">
        <!-- DESKTOP VERSION -->
        <div class="desktop-login-inner" style="display:flex;flex-direction:column;">
            <div style="margin-bottom:32px;">
                <h2 style="font-family:var(--font-display);font-size:26px;font-weight:700;margin-bottom:6px;">
                    Selamat datang kembali
                </h2>
                <p style="font-size:14px;color:var(--clr-muted);">Masuk ke dashboard manajemen cuti Anda</p>
            </div>
            
            <!-- Tabs -->
            <div class="login-panel-tabs">
                <button class="tab-btn active" onclick="switchTab('masuk')">Masuk</button>
                <button class="tab-btn" onclick="switchTab('daftar')">Daftar</button>
            </div>
            
            <!-- Notifikasi -->
            <?php if ($error): ?>
                <div style="background:#FDECEA;border:1px solid #C0392B;border-radius:var(--r-md);padding:10px 14px;margin-bottom:16px;color:#C0392B;font-size:13px;">
                    <i class="ri-error-warning-line"></i> <?= escape($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div style="background:#E8F5EE;border:1px solid #2D7A4F;border-radius:var(--r-md);padding:10px 14px;margin-bottom:16px;color:#2D7A4F;font-size:13px;">
                    <i class="ri-checkbox-circle-line"></i> <?= escape($success) ?>
                </div>
            <?php endif; ?>
            
            <!-- ===========================================
                 FORM MASUK
                 =========================================== -->
            <div id="form-masuk" class="login-form">
                <form method="POST" action="">
                    <input type="hidden" name="login" value="1">
                    
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="nama@perusahaan.id" value="<?= escape($_POST['email'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-row-between">
                            <label class="form-label">Kata Sandi</label>
                            <!-- ✅ LUPA SANDI - LANGSUNG KE lupa_password.php -->
                            <a href="lupa_password.php" class="link-forgot">
                                <i class="ri-lock-line"></i> Lupa sandi?
                            </a>
                        </div>
                        <div style="position:relative;">
                            <input type="password" name="password" class="form-control" placeholder="••••••••" id="desktop-pass" required>
                            <button type="button" onclick="togglePwd('desktop-pass')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--clr-muted);font-size:16px;">
                                <i class="ri-eye-off-line" id="desktop-pass-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-full">
                        Masuk <i class="ri-arrow-right-line"></i>
                    </button>
                </form>
            </div>
            
            <!-- ===========================================
                 FORM DAFTAR
                 =========================================== -->
            <div id="form-daftar" class="register-form" style="display:none;flex-direction:column;gap:16px;">
                <form method="POST" action="">
                    <input type="hidden" name="register" value="1">
                    
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap <span style="color:red;">*</span></label>
                        <input type="text" name="reg_name" class="form-control" placeholder="Nama sesuai identitas" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email <span style="color:red;">*</span></label>
                        <input type="email" name="reg_email" class="form-control" placeholder="email@domain.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">NIP / NIK <span style="color:red;">*</span></label>
                        <input type="text" name="reg_nip" class="form-control" placeholder="Nomor Induk Pegawai" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Jabatan <span style="color:red;">*</span></label>
                        <input type="text" name="reg_jabatan" class="form-control" placeholder="Staff / Manager / dll" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Departemen <span style="color:red;">*</span></label>
                        <input type="text" name="reg_departemen" class="form-control" placeholder="IT / HRD / Marketing / dll" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Kata Sandi <span style="color:red;">*</span></label>
                        <input type="password" name="reg_password" class="form-control" placeholder="Min. 6 karakter" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Konfirmasi Kata Sandi <span style="color:red;">*</span></label>
                        <input type="password" name="reg_password_confirm" class="form-control" placeholder="Ulangi kata sandi" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-full">
                        Daftar Sekarang <i class="ri-arrow-right-line"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- =====================================================
             MOBILE VERSION
             ===================================================== -->
        <div class="mobile-login-show" style="display:none;flex-direction:column;width:100%;min-height:100vh;">
            <div class="mobile-login-wrap" style="display:flex;flex-direction:column;min-height:100vh;">
                <div class="mobile-app-header">
                    <div class="mobile-app-icon"><i class="ri-building-2-line"></i></div>
                    <div class="mobile-app-name">cuti<span style="color:var(--clr-primary)">modern</span></div>
                    <div class="mobile-app-sub">sistem-perizinan</div>
                </div>
                
                <div class="mobile-login-card">
                    <h3>Masuk ke Sistem</h3>
                    <p>Silakan masukkan kredensial Anda untuk melanjutkan ke dashboard.</p>
                    
                    <?php if ($error): ?>
                        <div style="background:#FDECEA;border:1px solid #C0392B;border-radius:var(--r-md);padding:10px 14px;margin-bottom:16px;color:#C0392B;font-size:13px;">
                            <i class="ri-error-warning-line"></i> <?= escape($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- MOBILE LOGIN -->
                    <form method="POST" action="">
                        <input type="hidden" name="login" value="1">
                        <div class="login-form">
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <div class="input-icon-wrap">
                                    <i class="ri-at-line icon"></i>
                                    <input type="email" name="email" class="form-control" placeholder="contoh@domain.com" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <div class="form-row-between">
                                    <label class="form-label">Kata Sandi</label>
                                    <!-- ✅ MOBILE LUPA SANDI -->
                                    <a href="lupa_password.php" class="link-forgot">
                                        Lupa sandi?
                                    </a>
                                </div>
                                <div style="position:relative;">
                                    <i class="ri-lock-line" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--clr-muted);font-size:16px;"></i>
                                    <input type="password" name="password" class="form-control" placeholder="•••••••" id="mobile-pass" style="padding-left:40px;" required>
                                    <button type="button" onclick="togglePwd('mobile-pass')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--clr-muted);font-size:16px;">
                                        <i class="ri-eye-off-line" id="mobile-pass-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-full" style="border-radius:var(--r-md);letter-spacing:.5px;font-size:13px;padding:14px;">
                                MASUK <i class="ri-login-box-line"></i>
                            </button>
                        </div>
                    </form>
                    
                    <p class="login-footer-text" style="margin-top:16px;">
                        Belum memiliki akun? 
                        <a href="#" class="link-muted" onclick="document.querySelector('.tab-btn:last-child').click();return false;">
                            Daftar Sekarang
                        </a>
                    </p>
                </div>
                
                <div class="mobile-footer-links">
                    <a href="#">Bantuan</a>
                    <a href="#">Privasi</a>
                    <a href="#">Kontak</a>
                </div>
                <div style="text-align:center;padding:8px;font-size:11px;color:var(--clr-muted);background:#F2F2F2;">
                    © <?= date('Y') ?> sistem.perizinan.cuti &nbsp;·&nbsp; v2.5.0
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="global-toast" class="toast-notif" style="display:none;"></div>

<script>
// ============================================
// SWITCH TAB (Login / Register)
// ============================================
function switchTab(tab) {
    const btns = document.querySelectorAll('.tab-btn');
    btns.forEach(btn => btn.classList.remove('active'));
    
    if (tab === 'masuk') {
        document.querySelector('.tab-btn:first-child').classList.add('active');
        document.getElementById('form-masuk').style.display = 'block';
        document.getElementById('form-daftar').style.display = 'none';
    } else {
        document.querySelector('.tab-btn:last-child').classList.add('active');
        document.getElementById('form-masuk').style.display = 'none';
        document.getElementById('form-daftar').style.display = 'flex';
    }
}

// ============================================
// TOGGLE PASSWORD VISIBILITY
// ============================================
function togglePwd(id) {
    const input = document.getElementById(id);
    const eye = document.getElementById(id + '-eye');
    if (input.type === 'password') {
        input.type = 'text';
        eye.className = 'ri-eye-line';
    } else {
        input.type = 'password';
        eye.className = 'ri-eye-off-line';
    }
}
</script>

<script src="../assets/js/app.js"></script>
</body>
</html>
