<?php
/**
 * =====================================================
 * FILE: auth/lupa_password.php
 * FUNGSI: Halaman Lupa Password - Reset via Email
 * =====================================================
 */

require_once __DIR__ . '/../config/firebase.php';

$error = '';
$success = '';

// Proses reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Email harus diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } else {
        try {
            // Kirim reset password via Firebase API
            $url = "https://identitytoolkit.googleapis.com/v1/accounts:sendOobCode?key=" . FirebaseConfig::getApiKey();
            
            $data = [
                'requestType' => 'PASSWORD_RESET',
                'email' => $email
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $success = '✅ Link reset password telah dikirim ke email Anda. Cek inbox atau folder spam!';
            } else {
                $errorData = json_decode($response, true);
                $message = $errorData['error']['message'] ?? 'Gagal mengirim reset password';
                
                // Pesan error yang lebih user-friendly
                $friendlyMessages = [
                    'EMAIL_NOT_FOUND' => 'Email tidak ditemukan! Pastikan email sudah terdaftar.',
                    'INVALID_EMAIL' => 'Format email tidak valid!',
                    'TOO_MANY_ATTEMPTS_TRY_LATER' => 'Terlalu banyak percobaan. Coba lagi nanti.',
                    'USER_NOT_FOUND' => 'User tidak ditemukan!'
                ];
                
                $message = $friendlyMessages[$message] ?? $message;
                $error = '❌ ' . $message;
            }
            
        } catch (Exception $e) {
            $error = '❌ Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Magang.usg</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 40px;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        }
        .logo {
            text-align: center;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .logo span { color: #B8860B; }
        .subtitle {
            text-align: center;
            color: #888;
            font-size: 14px;
            margin-bottom: 24px;
        }
        .form-group { 
            margin-bottom: 16px; 
        }
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #555;
            margin-bottom: 4px;
        }
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: #B8860B;
            box-shadow: 0 0 0 3px rgba(184,134,11,0.1);
        }
        .btn-submit {
            width: 100%;
            padding: 10px;
            background: #B8860B;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-submit:hover { 
            background: #a0750a; 
        }
        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        .alert-success { 
            background: #E8F5EE; 
            border: 1px solid #2D7A4F; 
            color: #2D7A4F; 
        }
        .alert-danger { 
            background: #FDECEA; 
            border: 1px solid #C0392B; 
            color: #C0392B; 
        }
        .link-back {
            display: block;
            text-align: center;
            margin-top: 16px;
            color: #888;
            font-size: 13px;
            text-decoration: none;
        }
        .link-back:hover { 
            color: #B8860B; 
        }
        .icon-input {
            position: relative;
        }
        .icon-input i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            font-size: 18px;
        }
        .icon-input input {
            padding-left: 40px;
        }
        .info-text {
            color: #888;
            font-size: 13px;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .info-text i {
            color: #B8860B;
        }
        
        @media (max-width: 480px) {
            .card {
                padding: 24px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="card">
        <!-- Logo -->
        <div class="logo">
            Magang<span>.usg</span>
        </div>
        <div class="subtitle">Reset Password</div>
        
        <!-- Info -->
        <div class="info-text">
            <i class="ri-information-line"></i> Masukkan alamat email yang terdaftar, kami akan mengirimkan link untuk mereset password Anda.
        </div>
        
        <!-- Alert -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="ri-checkbox-circle-line"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="ri-error-warning-line"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <!-- Form -->
        <?php if (!$success): ?>
        <form method="POST" action="" id="form-reset">
            <div class="form-group">
                <label class="form-label">Alamat Email</label>
                <div class="icon-input">
                    <i class="ri-mail-line"></i>
                    <input type="email" name="email" class="form-control" placeholder="email@contoh.com" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
            </div>
            
            <button type="submit" name="reset_password" class="btn-submit" id="btn-reset">
                <i class="ri-mail-send-line"></i> Kirim Link Reset
            </button>
        </form>
        <?php endif; ?>
        
        <!-- Back to Login -->
        <a href="login.php" class="link-back">
            <i class="ri-arrow-left-line"></i> Kembali ke Login
        </a>
        
        <!-- Footer -->
        <div style="text-align:center;margin-top:20px;font-size:11px;color:#ccc;border-top:1px solid #eee;padding-top:16px;">
            &copy; <?= date('Y') ?> Magang.usg - Sistem Manajemen Cuti
        </div>
    </div>
    
    <script>
        // Prevent double submit
        document.getElementById('form-reset')?.addEventListener('submit', function() {
            const btn = document.getElementById('btn-reset');
            btn.disabled = true;
            btn.innerHTML = '<i class="ri-loader-4-line ri-spin"></i> Mengirim...';
        });
    </script>
</body>
</html>