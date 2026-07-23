<?php
/**
 * =====================================================
 * FILE: auth/lupa_password.php
 * VERSION: DEBUG MODE
 * =====================================================
 */

// 🔥 TAMPILKAN SEMUA ERROR
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/firebase.php';

$error = '';
$success = '';
$debug = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $email = trim($_POST['email'] ?? '');
    
    $debug .= "📧 Email: " . $email . "\n";
    
    if (empty($email)) {
        $error = 'Email harus diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } else {
        try {
            // 🔥 CEK API KEY
            $apiKey = FirebaseConfig::getApiKey();
            $debug .= "🔑 API Key: " . substr($apiKey, 0, 10) . "...\n";
            
            $url = "https://identitytoolkit.googleapis.com/v1/accounts:sendOobCode?key=" . $apiKey;
            $debug .= "🌐 URL: " . $url . "\n";
            
            $data = [
                'requestType' => 'PASSWORD_RESET',
                'email' => $email
            ];
            $debug .= "📦 Data: " . json_encode($data) . "\n";
            
            // 🔥 CEK CURL
            if (!function_exists('curl_init')) {
                throw new Exception('CURL tidak aktif!');
            }
            $debug .= "✅ CURL aktif\n";
            
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
            $curlError = curl_error($ch);
            curl_close($ch);
            
            $debug .= "📡 HTTP Code: " . $httpCode . "\n";
            $debug .= "📄 Response: " . $response . "\n";
            if ($curlError) {
                $debug .= "⚠️ CURL Error: " . $curlError . "\n";
            }
            
            if ($httpCode === 200) {
                $success = '✅ Link reset password telah dikirim ke ' . htmlspecialchars($email);
            } else {
                $errorData = json_decode($response, true);
                $message = $errorData['error']['message'] ?? 'Gagal mengirim reset password';
                $error = '❌ ' . $message;
            }
            
        } catch (Exception $e) {
            $error = '❌ ' . $e->getMessage();
            $debug .= "⚠️ Exception: " . $e->getMessage() . "\n";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
            max-width: 500px;
            width: 100%;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        }
        .logo { text-align: center; font-size: 24px; font-weight: 700; margin-bottom: 4px; }
        .logo span { color: #B8860B; }
        .subtitle { text-align: center; color: #888; font-size: 14px; margin-bottom: 24px; }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 13px; font-weight: 600; color: #555; margin-bottom: 4px; }
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: 0.2s;
        }
        .form-control:focus { outline: none; border-color: #B8860B; box-shadow: 0 0 0 3px rgba(184,134,11,0.1); }
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
        .btn-submit:hover { background: #a0750a; }
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; }
        .alert-success { background: #E8F5EE; border: 1px solid #2D7A4F; color: #2D7A4F; }
        .alert-danger { background: #FDECEA; border: 1px solid #C0392B; color: #C0392B; }
        .link-back { display: block; text-align: center; margin-top: 16px; color: #888; font-size: 13px; text-decoration: none; }
        .link-back:hover { color: #B8860B; }
        .icon-input { position: relative; }
        .icon-input i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #aaa; font-size: 18px; }
        .icon-input input { padding-left: 40px; }
        
        /* Debug box */
        .debug-box {
            background: #1a1a1a;
            color: #0f0;
            border-radius: 6px;
            padding: 12px;
            margin-top: 16px;
            font-family: monospace;
            font-size: 11px;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 250px;
            overflow: auto;
        }
        .debug-box strong { color: #fff; }
        .debug-box .error { color: #f00; }
        .debug-box .success { color: #0f0; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">Sistem Perizinan<span>Cuti</span></div>
        <div class="subtitle">Reset Password - Debug Mode</div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Alamat Email</label>
                <div class="icon-input">
                    <i class="ri-mail-line"></i>
                    <input type="email" name="email" class="form-control" placeholder="email@contoh.com" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
            </div>
            
            <button type="submit" name="reset_password" class="btn-submit">
                <i class="ri-mail-send-line"></i> Kirim Link Reset
            </button>
        </form>
        <?php endif; ?>
        
        <a href="login.php" class="link-back">← Kembali ke Login</a>
        
        <!-- 🔥 DEBUG INFO -->
        <?php if ($debug): ?>
        <div class="debug-box">
            <strong>🔍 DEBUG INFO:</strong><br>
            <?= htmlspecialchars($debug) ?>
        </div>
        <?php endif; ?>
        
        <div style="text-align:center;margin-top:16px;font-size:11px;color:#ccc;border-top:1px solid #eee;padding-top:12px;">
            &copy; <?= date('Y') ?> sistem.perizinan.cuti
        </div>
    </div>
</body>
</html>
