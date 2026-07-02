<?php
/**
 * FILE: test_db.php
 * FUNGSI: Test Koneksi & Write ke Firebase Database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/firebase.php';

echo "<h2>🔥 TEST FIREBASE REALTIME DATABASE</h2>";

try {
    $database = FirebaseConfig::getDatabase();
    
    // TEST 1: BACA DATA
    echo "<h3>1. Test Baca Data</h3>";
    $data = $database->getReference('/')->getValue();
    echo "✅ Data: " . print_r($data, true) . "<br>";
    
    // TEST 2: TULIS DATA
    echo "<h3>2. Test Tulis Data</h3>";
    $testKey = 'test_' . time();
    $result = $database->getReference('test/' . $testKey)->set([
        'message' => 'Test koneksi database',
        'time' => date('Y-m-d H:i:s')
    ]);
    echo "✅ Hasil write: " . print_r($result, true) . "<br>";
    
    // TEST 3: BACA DATA YANG BARU DITULIS
    echo "<h3>3. Test Baca Data yang Baru Ditulis</h3>";
    $testData = $database->getReference('test/' . $testKey)->getValue();
    echo "✅ Data yang ditulis: " . print_r($testData, true) . "<br>";
    
    echo "<hr>";
    echo "<p style='color:green;font-weight:bold;'>🎉 DATABASE BERHASIL!</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;font-weight:bold;'>❌ ERROR: " . $e->getMessage() . "</p>";
}
?>