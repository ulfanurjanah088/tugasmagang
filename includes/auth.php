<?php
/**
 * =====================================================
 * FILE: includes/auth.php
 * FUNGSI: Autentikasi dengan Firebase
 * VERSION: FINAL
 * =====================================================
 */

// 🔥 JANGAN PANGGIL session_start() DI SINI!
// Session sudah di-start di api/index.php

require_once __DIR__ . '/../config/firebase.php';

class AuthManager {
    private $auth;
    private $database;

    public function __construct() {
        try {
            $this->auth = FirebaseConfig::getAuth();
            $this->database = FirebaseConfig::getDatabase();
        } catch (Exception $e) {
            die('Error Firebase: ' . $e->getMessage());
        }
    }

    public function login($email, $password) {
        try {
            $user = $this->auth->signInWithEmailAndPassword($email, $password);
            
            if (isset($user->uid)) {
                $uid = $user->uid;
            } elseif (isset($user->firebaseUserId)) {
                $uid = $user->firebaseUserId;
            } elseif (property_exists($user, 'localId')) {
                $uid = $user->localId;
            } else {
                throw new Exception('UID tidak ditemukan');
            }
            
            if (isset($user->idToken)) {
                $_SESSION['idToken'] = $user->idToken;
            }
            
            $userData = $this->database
                ->getReference('users/' . $uid)
                ->getValue();
            
            if (!$userData || !is_array($userData)) {
                $userData = [
                    'name' => $user->displayName ?? explode('@', $email)[0],
                    'email' => $email,
                    'role' => 'user',
                    'sisa_cuti' => 12,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                $this->database
                    ->getReference('users/' . $uid)
                    ->set($userData);
            }
            
            $_SESSION['uid'] = $uid;
            $_SESSION['user'] = $userData;
            $_SESSION['user_email'] = $email;
            
            // Cookie fallback untuk Vercel
            setcookie('uid', $uid, time() + 86400 * 7, '/', '', false, true);
            setcookie('user_email', $email, time() + 86400 * 7, '/', '', false, true);
            setcookie('user_data', json_encode($userData), time() + 86400 * 7, '/', '', false, true);
            
            return $userData;
            
        } catch (Exception $e) {
            error_log('Login Error: ' . $e->getMessage());
            return null;
        }
    }

    public function isLoggedIn() {
        if (isset($_SESSION['uid']) && !empty($_SESSION['uid'])) {
            return true;
        }
        
        // 🔥 FALLBACK: Cek cookie
        if (isset($_COOKIE['uid']) && !empty($_COOKIE['uid'])) {
            $_SESSION['uid'] = $_COOKIE['uid'];
            if (isset($_COOKIE['user_data'])) {
                $_SESSION['user'] = json_decode($_COOKIE['user_data'], true);
            }
            if (isset($_COOKIE['user_email'])) {
                $_SESSION['user_email'] = $_COOKIE['user_email'];
            }
            return true;
        }
        
        return false;
    }

    public function getCurrentUser() {
        return $_SESSION['user'] ?? null;
    }

    public function getCurrentUid() {
        return $_SESSION['uid'] ?? null;
    }

    public function isAdmin() {
        $user = $this->getCurrentUser();
        return $user && isset($user['role']) && $user['role'] === 'admin';
    }

    public function register($email, $password, $name, $nip, $jabatan, $departemen) {
        try {
            if (strlen($password) < 6) {
                return 'Kata sandi minimal 6 karakter';
            }
            
            $user = $this->auth->createUserWithEmailAndPassword($email, $password);
            
            if (isset($user->uid)) {
                $uid = $user->uid;
            } elseif (isset($user->firebaseUserId)) {
                $uid = $user->firebaseUserId;
            } elseif (property_exists($user, 'localId')) {
                $uid = $user->localId;
            } else {
                $uid = 'user_' . rand(1000, 9999);
            }
            
            $userData = [
                'name' => $name,
                'email' => $email,
                'nip' => $nip,
                'jabatan' => $jabatan,
                'departemen' => $departemen,
                'role' => 'user',
                'status' => 'aktif',
                'sisa_cuti' => 12,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $this->database
                ->getReference('users/' . $uid)
                ->set($userData);
            
            return true;
            
        } catch (Exception $e) {
            return 'Registrasi gagal: ' . $e->getMessage();
        }
    }
}

$auth = new AuthManager();
?>
