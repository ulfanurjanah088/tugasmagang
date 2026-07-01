<?php
/**
 * =====================================================
 * FILE: includes/functions.php
 * FUNGSI: Helper functions
 * VERSION: FINAL - Fix header error
 * =====================================================
 */

// 🔥 JANGAN ADA ECHO/OUTPUT DI ATAS FUNGSI INI!

function showToast($message, $type = 'info') {
    $icon = 'ri-information-line';
    switch ($type) {
        case 'success': $icon = 'ri-checkbox-circle-line'; break;
        case 'error': $icon = 'ri-close-circle-line'; break;
        case 'warning': $icon = 'ri-alert-line'; break;
    }
    echo "<script>showToast('" . addslashes($message) . "', '" . $icon . "');</script>";
}

function getStatusBadge($status) {
    $class = '';
    switch ($status) {
        case 'Menunggu': $class = 'badge-warning'; break;
        case 'Disetujui': $class = 'badge-success'; break;
        case 'Ditolak': $class = 'badge-danger'; break;
        case 'Selesai': $class = 'badge-dark'; break;
        default: $class = 'badge-warning';
    }
    return '<span class="badge ' . $class . '">' . $status . '</span>';
}

function formatTanggal($datetime) {
    if (empty($datetime)) return '-';
    $bulan = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',
              7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];
    $date = date_create($datetime);
    if (!$date) return $datetime;
    return date_format($date, 'd') . ' ' . $bulan[(int)date_format($date, 'n')] . ' ' . date_format($date, 'Y');
}

function formatTanggalWaktu($datetime) {
    if (empty($datetime)) return '-';
    return formatTanggal($datetime) . ', ' . date('H:i', strtotime($datetime));
}

function getJenisCuti() {
    return [
        'Cuti Tahunan' => 'Cuti tahunan (12 hari kerja)',
        'Cuti Sakit' => 'Cuti karena sakit (dengan surat dokter)',
        'Cuti Melahirkan' => 'Cuti melahirkan (90 hari)',
        'Cuti Penting' => 'Cuti untuk keperluan penting',
        'Cuti Ibadah' => 'Cuti untuk keperluan ibadah',
        'Cuti Pernikahan' => 'Cuti pernikahan (3 hari)',
        'Cuti Duka' => 'Cuti duka cita (2 hari)'
    ];
}

function getSisaCuti($uid, $database) {
    $userData = $database->getReference('users/' . $uid)->getValue();
    $totalCuti = (is_array($userData) && isset($userData['sisa_cuti'])) ? (int)$userData['sisa_cuti'] : 12;
    
    $allPermohonan = $database->getReference('permohonan')->getValue();
    $used = 0;
    
    if (is_array($allPermohonan) && !empty($allPermohonan)) {
        foreach ($allPermohonan as $izin) {
            if (!is_array($izin)) continue;
            if (($izin['user_id'] ?? '') !== $uid) continue;
            
            $status = $izin['status'] ?? '';
            if ($status === 'Disetujui' || $status === 'Selesai') {
                $startDate = $izin['tanggal_mulai'] ?? null;
                $endDate = $izin['tanggal_selesai'] ?? null;
                if ($startDate && $endDate) {
                    try {
                        $start = new DateTime($startDate);
                        $end = new DateTime($endDate);
                        $diff = $start->diff($end);
                        $used += $diff->days + 1;
                    } catch (Exception $e) { continue; }
                }
            }
        }
    }
    return max(0, $totalCuti - $used);
}

function updateSisaCuti($uid, $durasi, $database) {
    $userData = $database->getReference('users/' . $uid)->getValue();
    if (!is_array($userData)) return false;
    
    $sisaCuti = (int)($userData['sisa_cuti'] ?? 12);
    $sisaCutiBaru = max(0, $sisaCuti - $durasi);
    
    $database->getReference('users/' . $uid . '/sisa_cuti')->set($sisaCutiBaru);
    return $sisaCutiBaru;
}

function tambahSisaCuti($uid, $tambah, $database) {
    $userData = $database->getReference('users/' . $uid)->getValue();
    if (!is_array($userData)) return false;
    
    $sisaCuti = (int)($userData['sisa_cuti'] ?? 12);
    $sisaCutiBaru = $sisaCuti + $tambah;
    
    $database->getReference('users/' . $uid . '/sisa_cuti')->set($sisaCutiBaru);
    return $sisaCutiBaru;
}

function getAllUsers($database) {
    $users = $database->getReference('users')->getValue();
    return is_array($users) ? $users : [];
}

function generateCutiId() {
    return 'CUT-' . date('Y') . '-' . strtoupper(substr(uniqid(), -4));
}

/**
 * 🔥 REDIRECT - Pastikan tidak ada output sebelumnya
 */
function redirect($url) {
    // 🔥 Cek jika header sudah dikirim
    if (!headers_sent()) {
        header('Location: ' . $url);
        exit;
    } else {
        // Fallback jika header sudah terkirim
        echo '<script>window.location.href="' . $url . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . $url . '"></noscript>';
        exit;
    }
}

function requireLogin($auth) {
    if (!$auth->isLoggedIn()) {
        redirect('../auth/login.php');
    }
}

function requireAdmin($auth) {
    requireLogin($auth);
    if (!$auth->isAdmin()) {
        redirect('../user/index.php');
    }
}

function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function base_url($path = '') {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $isLocal = strpos($host, 'localhost') !== false || 
               strpos($host, '127.0.0.1') !== false ||
               strpos($host, '192.168.') !== false;
    
    if ($isLocal) {
        $base = '/izin';
    } else {
        $base = '';
    }
    
    $path = ltrim($path, '/');
    return $base . '/' . $path;
}
?>
