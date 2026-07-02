<?php
/**
 * =====================================================
 * FILE: config/supabase.php
 * FUNGSI: Upload file ke Supabase Storage
 * VERSION: FINAL - Fixed Environment Variables
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
            // 🔥 Ambil dari environment variable (Vercel / Render)
            self::$projectUrl = getenv('SUPABASE_URL');
            
            // Fallback: dari $_ENV (buat local)
            if (!self::$projectUrl) {
                self::$projectUrl = $_ENV['SUPABASE_URL'] ?? null;
            }
            
            // Fallback: dari define (kalo ada)
            if (!self::$projectUrl && defined('SUPABASE_URL')) {
                self::$projectUrl = SUPABASE_URL;
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
            // 🔥 Ambil dari environment variable (Vercel / Render)
            self::$apiKey = getenv('SUPABASE_ANON_KEY');
            
            // Fallback: dari $_ENV (buat local)
            if (!self::$apiKey) {
                self::$apiKey = $_ENV['SUPABASE_ANON_KEY'] ?? null;
            }
            
            // Fallback: dari define (kalo ada)
            if (!self::$apiKey && defined('SUPABASE_ANON_KEY')) {
                self::$apiKey = SUPABASE_ANON_KEY;
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
            
            // Fallback: dari define
            if (!self::$bucket && defined('SUPABASE_BUCKET')) {
                self::$bucket = SUPABASE_BUCKET;
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
     * Upload file ke Supabase Storage
     * 
     * @param string $localPath Path file lokal
     * @param string $destinationPath Path tujuan di bucket
     * @param array $options Opsi tambahan
     * @return string|null URL publik file atau null jika gagal
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

        // Validasi tipe file (opsional)
        if (!empty($options['allowedMimeTypes'])) {
            if (!in_array($mimeType, $options['allowedMimeTypes'])) {
                self::$lastError = 'Tipe file tidak diizinkan: ' . $mimeType;
                return null;
            }
        }

        // Build URL
        $url = $projectUrl . '/storage/v1/object/' . $bucket . '/' . ltrim($destinationPath, '/');

        // Siapkan CURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: ' . $mimeType,
            'Authorization: Bearer ' . $apiKey,
            'Content-Length: ' . $fileSize
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Handle response
        if ($httpCode === 200 || $httpCode === 201) {
            // Upload sukses - return public URL
            return $projectUrl . '/storage/v1/object/public/' . $bucket . '/' . ltrim($destinationPath, '/');
        }

        // Handle error
        if ($httpCode === 401 || $httpCode === 403) {
            self::$lastError = 'API Key tidak valid atau tidak punya akses!';
        } elseif ($httpCode === 404) {
            self::$lastError = 'Bucket "' . $bucket . '" tidak ditemukan!';
        } elseif ($httpCode === 413) {
            self::$lastError = 'File terlalu besar untuk bucket ini!';
        } elseif ($curlError) {
            self::$lastError = 'CURL Error: ' . $curlError;
        } else {
            self::$lastError = 'HTTP ' . $httpCode . ': ' . substr($response, 0, 100);
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

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 || $httpCode === 204) {
            return true;
        }

        self::$lastError = 'Gagal hapus file: HTTP ' . $httpCode;
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

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return ['success' => true, 'message' => '✅ Koneksi ke Supabase berhasil!'];
        } elseif ($httpCode === 404) {
            return ['success' => false, 'message' => '❌ Bucket "' . $bucket . '" tidak ditemukan!'];
        } else {
            return ['success' => false, 'message' => '❌ HTTP ' . $httpCode . ': ' . substr($response, 0, 100)];
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