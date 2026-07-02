<?php
/**
 * =====================================================
 * FILE: config/supabase.php
 * FUNGSI: Upload file ke Supabase Storage
 * VERSION: 8.0 - Removed curl_close() for PHP 8.5+
 * =====================================================
 */

class SupabaseConfig {
    private static $projectUrl = null;
    private static $apiKey = null;
    private static $bucket = null;
    private static $lastError = null;

    /**
     * Get Project URL dari environment variable
     */
    private static function getProjectUrl() {
        if (self::$projectUrl === null) {
            // 🔥 Ambil dari environment variable
            self::$projectUrl = getenv('SUPABASE_URL');
            
            // Fallback: dari $_ENV
            if (!self::$projectUrl) {
                self::$projectUrl = $_ENV['SUPABASE_URL'] ?? null;
            }
            
            // Kalo masih kosong, throw error
            if (!self::$projectUrl) {
                throw new Exception('SUPABASE_URL not set in environment variables');
            }
            
            self::$projectUrl = rtrim(self::$projectUrl, '/');
        }
        return self::$projectUrl;
    }

    /**
     * Get API Key dari environment variable
     */
    private static function getApiKey() {
        if (self::$apiKey === null) {
            // 🔥 Ambil dari environment variable
            self::$apiKey = getenv('SUPABASE_ANON_KEY');
            
            // Fallback: dari $_ENV
            if (!self::$apiKey) {
                self::$apiKey = $_ENV['SUPABASE_ANON_KEY'] ?? null;
            }
            
            // Kalo masih kosong, throw error
            if (!self::$apiKey) {
                throw new Exception('SUPABASE_ANON_KEY not set in environment variables');
            }
        }
        return self::$apiKey;
    }

    /**
     * Get Bucket name dari environment variable
     */
    private static function getBucket() {
        if (self::$bucket === null) {
            // 🔥 Ambil dari environment variable
            self::$bucket = getenv('SUPABASE_BUCKET');
            
            // Fallback: dari $_ENV
            if (!self::$bucket) {
                self::$bucket = $_ENV['SUPABASE_BUCKET'] ?? null;
            }
            
            // Default kalo gak ada
            if (!self::$bucket) {
                self::$bucket = 'documents';
            }
        }
        return self::$bucket;
    }

    /**
     * Get last error message
     */
    public static function getLastError() {
        return self::$lastError;
    }

    /**
     * Send HTTP Request - Tanpa curl_close()
     */
    private static function sendRequest($url, $method = 'GET', $data = null, $headers = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        // ✅ HAPUS curl_close($ch) - DEPRECATED di PHP 8.5+
        
        return [
            'body' => $response,
            'httpCode' => $httpCode,
            'error' => $curlError
        ];
    }

    /**
     * Upload file ke Supabase Storage
     */
    public static function uploadFile($localPath, $destinationPath, $options = []) {
        self::$lastError = null;
        
        try {
            $projectUrl = self::getProjectUrl();
            $apiKey = self::getApiKey();
            $bucket = self::getBucket();
        } catch (Exception $e) {
            self::$lastError = $e->getMessage();
            return null;
        }

        // Validasi file
        if (!file_exists($localPath)) {
            self::$lastError = 'File tidak ditemukan: ' . $localPath;
            return null;
        }

        if (!function_exists('curl_init')) {
            self::$lastError = 'CURL tidak aktif di server';
            return null;
        }

        // Baca file
        $fileData = file_get_contents($localPath);
        if ($fileData === false) {
            self::$lastError = 'Gagal membaca file: ' . $localPath;
            return null;
        }

        // Informasi file
        $mimeType = mime_content_type($localPath) ?: 'application/octet-stream';
        $fileSize = filesize($localPath);

        // Validasi ukuran (default 5MB)
        $maxSize = $options['maxSize'] ?? 5 * 1024 * 1024;
        if ($fileSize > $maxSize) {
            self::$lastError = 'File terlalu besar (max ' . ($maxSize / 1024 / 1024) . 'MB)';
            return null;
        }

        // Validasi tipe file
        if (!empty($options['allowedMimeTypes'])) {
            if (!in_array($mimeType, $options['allowedMimeTypes'])) {
                self::$lastError = 'Tipe file tidak diizinkan: ' . $mimeType;
                return null;
            }
        }

        // Build URL
        $url = $projectUrl . '/storage/v1/object/' . $bucket . '/' . ltrim($destinationPath, '/');

        // Headers
        $headers = [
            'Content-Type: ' . $mimeType,
            'Authorization: Bearer ' . $apiKey,
            'Content-Length: ' . $fileSize
        ];

        // Upload
        $response = self::sendRequest($url, 'POST', $fileData, $headers);

        // Handle response
        if ($response['httpCode'] === 200 || $response['httpCode'] === 201) {
            return $projectUrl . '/storage/v1/object/public/' . $bucket . '/' . ltrim($destinationPath, '/');
        }

        // Handle error
        if ($response['httpCode'] === 401 || $response['httpCode'] === 403) {
            self::$lastError = 'API Key tidak valid atau tidak punya akses!';
        } elseif ($response['httpCode'] === 404) {
            self::$lastError = 'Bucket "' . $bucket . '" tidak ditemukan!';
        } elseif ($response['httpCode'] === 413) {
            self::$lastError = 'File terlalu besar untuk bucket ini!';
        } elseif ($response['error']) {
            self::$lastError = 'CURL Error: ' . $response['error'];
        } else {
            self::$lastError = 'HTTP ' . $response['httpCode'] . ': ' . substr($response['body'], 0, 100);
        }

        return null;
    }

    /**
     * Hapus file dari Supabase Storage
     */
    public static function deleteFile($path) {
        self::$lastError = null;
        
        try {
            $projectUrl = self::getProjectUrl();
            $apiKey = self::getApiKey();
            $bucket = self::getBucket();
        } catch (Exception $e) {
            self::$lastError = $e->getMessage();
            return false;
        }

        $url = $projectUrl . '/storage/v1/object/' . $bucket . '/' . ltrim($path, '/');
        
        $headers = [
            'Authorization: Bearer ' . $apiKey
        ];

        $response = self::sendRequest($url, 'DELETE', null, $headers);

        if ($response['httpCode'] === 200 || $response['httpCode'] === 204) {
            return true;
        }

        self::$lastError = 'Gagal hapus file: HTTP ' . $response['httpCode'];
        return false;
    }

    /**
     * Test koneksi ke Supabase
     */
    public static function testConnection() {
        try {
            $projectUrl = self::getProjectUrl();
            $apiKey = self::getApiKey();
            $bucket = self::getBucket();
        } catch (Exception $e) {
            return ['success' => false, 'message' => '❌ ' . $e->getMessage()];
        }

        $url = $projectUrl . '/storage/v1/bucket/' . $bucket;
        
        $headers = [
            'Authorization: Bearer ' . $apiKey
        ];

        $response = self::sendRequest($url, 'GET', null, $headers);

        if ($response['httpCode'] === 200) {
            return ['success' => true, 'message' => '✅ Koneksi ke Supabase berhasil!'];
        } elseif ($response['httpCode'] === 404) {
            return ['success' => false, 'message' => '❌ Bucket "' . $bucket . '" tidak ditemukan!'];
        } else {
            return ['success' => false, 'message' => '❌ HTTP ' . $response['httpCode'] . ': ' . substr($response['body'], 0, 100)];
        }
    }
}

// ============================================
// HELPER FUNCTIONS
// ============================================

/**
 * Helper function untuk upload file
 */
function supabase_upload($localPath, $destinationPath, $options = []) {
    return SupabaseConfig::uploadFile($localPath, $destinationPath, $options);
}

/**
 * Helper function untuk hapus file
 */
function supabase_delete($path) {
    return SupabaseConfig::deleteFile($path);
}

/**
 * Helper function untuk test koneksi
 */
function supabase_test() {
    return SupabaseConfig::testConnection();
}
?>
