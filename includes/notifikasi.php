<?php
/**
 * =====================================================
 * FILE: includes/notifikasi.php
 * VERSION: 3.0 - Fixed Notification
 * =====================================================
 */

class NotifikasiManager {
    private $database;
    private $uid;
    private $isAdmin;

    public function __construct($database, $uid, $isAdmin = false) {
        $this->database = $database;
        $this->uid = $uid;
        $this->isAdmin = $isAdmin;
    }

    public function getUnreadCount() {
        $notifikasi = $this->getNotifikasi();
        $count = 0;
        if (is_array($notifikasi) && !empty($notifikasi)) {
            foreach ($notifikasi as $notif) {
                if (!is_array($notif)) continue;
                if (($notif['dibaca'] ?? false) === false) {
                    $count++;
                }
            }
        }
        return $count;
    }

    public function getNotifikasi() {
        $data = $this->database->getReference('notifikasi/' . $this->uid)->getValue();
        return is_array($data) ? $data : [];
    }

    public function getRecentNotifikasi($limit = 5) {
        $notifikasi = $this->getNotifikasi();
        if (empty($notifikasi)) return [];
        
        usort($notifikasi, function($a, $b) {
            return strtotime($b['created_at'] ?? '1970-01-01') - strtotime($a['created_at'] ?? '1970-01-01');
        });
        
        return array_slice($notifikasi, 0, $limit);
    }

    // 🔥 PERBAIKAN: Tandai satu notifikasi sebagai dibaca
    public function markAsRead($notifId) {
        if (!$notifId) return false;
        
        // Update di Firebase
        $this->database
            ->getReference('notifikasi/' . $this->uid . '/' . $notifId . '/dibaca')
            ->set(true);
        $this->database
            ->getReference('notifikasi/' . $this->uid . '/' . $notifId . '/read_at')
            ->set(date('Y-m-d H:i:s'));
        
        return true;
    }

    // 🔥 PERBAIKAN: Tandai semua notifikasi sebagai dibaca
    public function markAllAsRead() {
        $notifikasi = $this->getNotifikasi();
        if (empty($notifikasi)) return true;
        
        foreach ($notifikasi as $key => $notif) {
            if (!is_array($notif)) continue;
            $this->database
                ->getReference('notifikasi/' . $this->uid . '/' . $key . '/dibaca')
                ->set(true);
            $this->database
                ->getReference('notifikasi/' . $this->uid . '/' . $key . '/read_at')
                ->set(date('Y-m-d H:i:s'));
        }
        return true;
    }

    // 🔥 PERBAIKAN: Hapus notifikasi
    public function deleteNotifikasi($notifId) {
        if (!$notifId) return false;
        $this->database
            ->getReference('notifikasi/' . $this->uid . '/' . $notifId)
            ->remove();
        return true;
    }

    public static function sendNotifikasi($database, $uid, $judul, $pesan, $link = '', $type = 'info', $catatan = '') {
        $notifData = [
            'judul' => $judul,
            'pesan' => $pesan,
            'link' => $link,
            'type' => $type,
            'dibaca' => false,
            'created_at' => date('Y-m-d H:i:s'),
            'read_at' => null
        ];
        if (!empty($catatan)) {
            $notifData['catatan'] = $catatan;
        }
        return $database->getReference('notifikasi/' . $uid)->push($notifData);
    }

    public static function notifikasiPengajuanBaru($database, $cutiData) {
        $judul = '📝 Pengajuan Cuti Baru';
        $pesan = $cutiData['user_name'] . ' mengajukan ' . $cutiData['jenis_cuti'] . ' (' . $cutiData['durasi'] . ' hari)';
        $link = 'admin.php';
        $type = 'warning';
        return self::sendToAllAdmins($database, $judul, $pesan, $link, $type);
    }

    public static function notifikasiStatusBerubah($database, $uid, $cutiData, $statusBaru, $catatan = '') {
        $statusText = '';
        $type = 'info';
        switch ($statusBaru) {
            case 'Disetujui': $statusText = '✅ disetujui'; $type = 'success'; break;
            case 'Ditolak': $statusText = '❌ ditolak'; $type = 'danger'; break;
            case 'Selesai': $statusText = '📋 selesai'; $type = 'info'; break;
            default: $statusText = '📝 diupdate'; $type = 'info';
        }
        
        $judul = '📢 Status Cuti Berubah';
        $pesan = 'Pengajuan ' . $cutiData['jenis_cuti'] . ' Anda ' . $statusText;
        if (!empty($catatan)) {
            $pesan .= '. Catatan: "' . $catatan . '"';
        }
        $link = 'riwayat.php';
        return self::sendNotifikasi($database, $uid, $judul, $pesan, $link, $type, $catatan);
    }

    public static function sendToAllAdmins($database, $judul, $pesan, $link = '', $type = 'info') {
        $allUsers = $database->getReference('users')->getValue();
        if (!is_array($allUsers) || empty($allUsers)) return false;
        
        foreach ($allUsers as $uid => $user) {
            if (!is_array($user)) continue;
            if (($user['role'] ?? '') === 'admin') {
                self::sendNotifikasi($database, $uid, $judul, $pesan, $link, $type);
            }
        }
        return true;
    }
}
?>