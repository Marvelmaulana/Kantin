<?php
/**
 * Debug Login Test
 */
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$db = "db_kantin";

$koneksi = mysqli_connect($host, $user, $pass, $db);
if (!$koneksi) {
    die("❌ Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($koneksi, 'utf8mb4');

echo "=== DEBUG LOGIN ===\n\n";

// Check akun test yang sudah ada
echo "📋 AKUN TEST YANG ADA:\n";
$result = mysqli_query($koneksi, "SELECT id_user, username, email, role FROM users LIMIT 5");
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "- {$row['username']} ({$row['email']}) - Role: {$row['role']}\n";
    }
}
echo "\n";

// Cek session sekarang
echo "📌 SESSION STATUS:\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'NOT ACTIVE') . "\n";
echo "SESSION array: " . json_encode($_SESSION) . "\n\n";

// Test membuat user pembeli baru
echo "➕ MEMBUAT USER TEST PEMBELI:\n";

$test_user = 'pembeli_test_' . date('His');
$test_email = 'pembeli' . time() . '@test.com';
$test_pass = password_hash('Test@123', PASSWORD_BCRYPT);

$insert = mysqli_query($koneksi, "
    INSERT INTO users (username, email, password, role, bahasa) 
    VALUES ('$test_user', '$test_email', '$test_pass', 'pembeli', 'id')
");

if ($insert) {
    $user_id = mysqli_insert_id($koneksi);
    echo "✅ User dibuat!\n";
    echo "  Username: $test_user\n";
    echo "  Email: $test_email\n";
    echo "  Password: Test@123\n";
    echo "  ID: $user_id\n\n";
    
    // Verify user exist
    $verify = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = $user_id");
    if ($verify && mysqli_num_rows($verify) > 0) {
        $user = mysqli_fetch_assoc($verify);
        echo "✅ Verifikasi: User ditemukan di database\n";
        echo "  Stored data: " . json_encode($user) . "\n";
    }
} else {
    echo "❌ Gagal membuat user: " . mysqli_error($koneksi) . "\n";
}

mysqli_close($koneksi);
?>
