<?php
/**
 * =====================================================
 * FILE: config/firebase.php
 * FUNGSI: Koneksi ke Firebase via REST API
 * VERSION: 7.0 - Fixed Environment Variables + curl_close
 * =====================================================
 */

class FirebaseConfig {
    private static $auth = null;
    private static $database = null;
    private static $apiKey = null;
    private static $databaseUrl = null;
    
    /**
     * Get API Key dari environment variable
     */
    public static function getApiKey() {
        if (self::$apiKey === null) {
            // 🔥 Ambil dari environment variable (Vercel / Render / Local)
            self::$apiKey = getenv('FIREBASE_API_KEY');
            
            // Fallback: dari $_ENV
            if (!self::$apiKey) {
                self::$apiKey = $_ENV['FIREBASE_API_KEY'] ?? null;
            }
            
            // Kalo masih kosong, throw error
            if (!self::$apiKey) {
                throw new Exception('FIREBASE_API_KEY not set in environment variables');
            }
        }
        return self::$apiKey;
    }
    
    /**
     * Get Database URL dari environment variable
     */
    public static function getDatabaseUrl() {
        if (self::$databaseUrl === null) {
            // 🔥 Ambil dari environment variable (Vercel / Render / Local)
            self::$databaseUrl = getenv('FIREBASE_DATABASE_URL');
            
            // Fallback: dari $_ENV
            if (!self::$databaseUrl) {
                self::$databaseUrl = $_ENV['FIREBASE_DATABASE_URL'] ?? null;
            }
            
            // Kalo masih kosong, throw error
            if (!self::$databaseUrl) {
                throw new Exception('FIREBASE_DATABASE_URL not set in environment variables');
            }
        }
        return self::$databaseUrl;
    }

    public static function getAuth() {
        if (self::$auth === null) {
            self::$auth = new RestAuth(self::getApiKey());
        }
        return self::$auth;
    }

    public static function getDatabase() {
        if (self::$database === null) {
            self::$database = new RestDatabase(self::getDatabaseUrl(), self::getApiKey());
        }
        return self::$database;
    }
}

// ============================================
// REST AUTH CLASS
// ============================================
class RestAuth {
    private $apiKey;
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    /**
     * Login dengan email dan password
     */
    public function signInWithEmailAndPassword($email, $password) {
        $url = "https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key=" . $this->apiKey;
        
        $data = [
            'email' => $email,
            'password' => $password,
            'returnSecureToken' => true
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
            $result = json_decode($response);
            return (object)[
                'firebaseUserId' => $result->localId,
                'uid' => $result->localId,
                'idToken' => $result->idToken,
                'email' => $result->email,
                'refreshToken' => $result->refreshToken ?? null,
                'expiresIn' => $result->expiresIn ?? null
            ];
        } else {
            $error = json_decode($response);
            $message = $error->error->message ?? 'Unknown error';
            throw new Exception('Login gagal: ' . $message);
        }
    }
    
    /**
     * Register user baru
     */
    public function createUserWithEmailAndPassword($email, $password) {
        $url = "https://identitytoolkit.googleapis.com/v1/accounts:signUp?key=" . $this->apiKey;
        
        $data = [
            'email' => $email,
            'password' => $password,
            'returnSecureToken' => true
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
            $result = json_decode($response);
            return (object)[
                'firebaseUserId' => $result->localId,
                'uid' => $result->localId,
                'idToken' => $result->idToken,
                'email' => $result->email
            ];
        } else {
            $error = json_decode($response);
            $message = $error->error->message ?? 'Unknown error';
            throw new Exception('Registrasi gagal: ' . $message);
        }
    }
}

// ============================================
// REST DATABASE CLASS
// ============================================
class RestDatabase {
    private $baseUrl;
    private $apiKey;
    
    public function __construct($baseUrl, $apiKey) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
    }
    
    public function getReference($path) {
        return new RestReference($this->baseUrl, $path, $this->apiKey);
    }
}

// ============================================
// REST REFERENCE CLASS
// ============================================
class RestReference {
    private $baseUrl;
    private $path;
    private $apiKey;
    private $orderBy = null;
    private $equalTo = null;
    private $limitToFirst = null;
    private $limitToLast = null;
    
    public function __construct($baseUrl, $path, $apiKey) {
        $this->baseUrl = $baseUrl;
        $this->path = ltrim($path, '/');
        $this->apiKey = $apiKey;
    }
    
    private function buildUrl() {
        $url = $this->baseUrl . '/' . $this->path . '.json';
        $params = ['auth=' . $this->apiKey];
        
        if ($this->orderBy) {
            $params[] = 'orderBy="' . $this->orderBy . '"';
        }
        if ($this->equalTo !== null) {
            $params[] = 'equalTo="' . $this->equalTo . '"';
        }
        if ($this->limitToFirst !== null) {
            $params[] = 'limitToFirst=' . $this->limitToFirst;
        }
        if ($this->limitToLast !== null) {
            $params[] = 'limitToLast=' . $this->limitToLast;
        }
        
        return $url . '?' . implode('&', $params);
    }
    
    private function sendRequest($url, $method = 'GET', $data = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'body' => $response,
            'httpCode' => $httpCode
        ];
    }
    
    public function getValue() {
        $url = $this->buildUrl();
        $response = $this->sendRequest($url);
        return json_decode($response['body'], true);
    }
    
    public function set($value) {
        $url = $this->baseUrl . '/' . $this->path . '.json?auth=' . $this->apiKey;
        $response = $this->sendRequest($url, 'PUT', $value);
        return json_decode($response['body'], true);
    }
    
    public function push($value) {
        $url = $this->baseUrl . '/' . $this->path . '.json?auth=' . $this->apiKey;
        $response = $this->sendRequest($url, 'POST', $value);
        $result = json_decode($response['body'], true);
        $key = $result['name'] ?? null;
        
        return (object)[
            'getKey' => function() use ($key) { return $key; }
        ];
    }
    
    public function update($value) {
        $url = $this->baseUrl . '/' . $this->path . '.json?auth=' . $this->apiKey;
        $response = $this->sendRequest($url, 'PATCH', $value);
        return json_decode($response['body'], true);
    }
    
    public function delete() {
        $url = $this->baseUrl . '/' . $this->path . '.json?auth=' . $this->apiKey;
        $response = $this->sendRequest($url, 'DELETE');
        return json_decode($response['body'], true);
    }
    
    public function orderByChild($field) {
        $this->orderBy = $field;
        return $this;
    }
    
    public function equalTo($value) {
        $this->equalTo = $value;
        return $this;
    }
    
    public function limitToFirst($limit) {
        $this->limitToFirst = $limit;
        return $this;
    }
    
    public function limitToLast($limit) {
        $this->limitToLast = $limit;
        return $this;
    }
    
    public function getSnapshot() {
        $data = $this->getValue();
        return (object)[
            'exists' => $data !== null,
            'numChildren' => is_array($data) ? count($data) : 0,
            'getValue' => function() use ($data) { return $data; }
        ];
    }
}

// ============================================
// ✅ INISIALISASI (Panggil di file yang butuh)
// ============================================
// HAPUS baris ini! Panggil di file yang butuh aja:
// $auth = FirebaseConfig::getAuth();
// $database = FirebaseConfig::getDatabase();
?>