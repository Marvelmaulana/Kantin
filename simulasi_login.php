<?php
/**
 * Simulasi Login Flow - Debug
 */
session_start();
error_log("=== SIMULASI LOGIN START ===");

$host = "localhost";
$user = "root";
$pass = "";
$db = "db_kantin";

$koneksi = mysqli_connect($host, $user, $pass, $db);
if (!$koneksi) {
    die("❌ Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($koneksi, 'utf8mb4');

// Include helpers
include(__DIR__ . '/config/config.php');
include(__DIR__ . '/includes/auth_helpers.php');
include(__DIR__ . '/includes/pembeli_helpers.php');

echo "<h2>Simulasi Login Flow</h2>";

// Get test user
$result = mysqli_query($koneksi, "SELECT * FROM users WHERE username='pembeli_test_023059' LIMIT 1");
if (!$result || mysqli_num_rows($result) === 0) {
    die("User tidak ditemukan");
}

$user_data = mysqli_fetch_assoc($result);
echo "<pre>";
echo "User Data:\n";
print_r($user_data);
echo "\n";

// Coba verifikasi password
echo "Testing password verification...\n";
$password_input = 'Test@123';
$is_valid = password_verify($password_input, $user_data['password']);
echo "Password valid: " . ($is_valid ? "YES" : "NO") . "\n\n";

if ($is_valid) {
    // Coba buat session
    echo "Creating session...\n";
    $result = create_user_session($user_data);
    echo "Session creation result: " . ($result ? "SUCCESS" : "FAILED") . "\n\n";
    
    echo "SESSION content:\n";
    print_r($_SESSION);
    echo "\n";
    
    // Check apakah role ter-set
    echo "Role in SESSION: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";
    echo "ID User in SESSION: " . ($_SESSION['id_user'] ?? 'NOT SET') . "\n";
}

echo "</pre>";

// Check error logs
echo "<h2>Error Log (Last 20 lines)</h2>";
echo "<pre>";
$log_path = 'C:\\xampp\\apache\\logs\\error.log';
if (file_exists($log_path)) {
    $lines = file($log_path);
    $last_lines = array_slice($lines, -20);
    echo implode('', $last_lines);
} else {
    echo "Error log not found at: $log_path";
}
echo "</pre>";
?>
