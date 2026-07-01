<?php
/**
 * =====================================================
 * FILE: config/supabase.php
 * FUNGSI: Upload file ke Supabase Storage
 * VERSION: FINAL - Class SupabaseConfig
 * =====================================================
 */

// 🔥 GANTI DENGAN DATA DARI SUPABASE CONSOLE
define('SUPABASE_URL', 'https://vfuxygadxbmvrwcptkqo.supabase.co');
define('SUPABASE_ANON_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InZmdXh5Z2FkeGJtdnJ3Y3B0a3FvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODI2NDAwOTQsImV4cCI6MjA5ODIxNjA5NH0.3nOSmCU3hQWCWF9ixwis0di4BwymRtDm-5QCYY_QP8I');
define('SUPABASE_BUCKET', 'documents');

/**
 * Class SupabaseConfig - Untuk upload file
 */
class SupabaseConfig {
    private static $projectUrl;
    private static $apiKey;
    private static $bucket;
    private static $lastError = null;

    public static function init() {
        self::$projectUrl = rtrim(SUPABASE_URL, '/');
        self::$apiKey = SUPABASE_ANON_KEY;
        self::$bucket = SUPABASE_BUCKET;
    }

    public static function getProjectUrl() {
        self::init();
        return self::$projectUrl;
    }

    public static function getApiKey() {
        self::init();
        return self::$apiKey;
    }

    public static function getBucket() {
        self::init();
        return self::$bucket;
    }

    public static function getLastError() {
        return self::$lastError;
    }

    /**
     * Upload file ke Supabase Storage
     */
    public static function uploadFile($localPath, $destinationPath) {
        self::init();
        self::$lastError = null;

        if (!file_exists($localPath)) {
            self::$lastError = 'File tidak ditemukan';
            return null;
        }

        if (!function_exists('curl_init')) {
            self::$lastError = 'CURL tidak aktif';
            return null;
        }

        $fileData = file_get_contents($localPath);
        if ($fileData === false) {
            self::$lastError = 'Gagal membaca file';
            return null;
        }

        $mimeType = mime_content_type($localPath);
        $fileSize = filesize($localPath);

        if ($fileSize > 5 * 1024 * 1024) {
            self::$lastError = 'File terlalu besar (max 5MB)';
            return null;
        }

        $url = self::$projectUrl . '/storage/v1/object/' . self::$bucket . '/' . $destinationPath;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: ' . $mimeType,
            'Authorization: Bearer ' . self::$apiKey,
            'Content-Length: ' . $fileSize
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        // 🔥 HAPUS curl_close (deprecated di PHP 8.5)

        if ($httpCode === 200 || $httpCode === 201) {
            return self::$projectUrl . '/storage/v1/object/public/' . self::$bucket . '/' . $destinationPath;
        }

        if ($httpCode === 401 || $httpCode === 403) {
            self::$lastError = 'API Key tidak valid!';
        } elseif ($httpCode === 404) {
            self::$lastError = 'Bucket "' . self::$bucket . '" tidak ditemukan!';
        } else {
            self::$lastError = 'HTTP ' . $httpCode . ': ' . $response;
        }

        return null;
    }

    public static function testConnection() {
        self::init();
        
        $url = self::$projectUrl . '/storage/v1/bucket/' . self::$bucket;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . self::$apiKey
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode === 200) {
            return ['success' => true, 'message' => '✅ Koneksi ke Supabase berhasil!'];
        } elseif ($httpCode === 404) {
            return ['success' => false, 'message' => '❌ Bucket "' . self::$bucket . '" tidak ditemukan!'];
        } else {
            return ['success' => false, 'message' => '❌ HTTP ' . $httpCode . ': ' . $response];
        }
    }
}
?>
