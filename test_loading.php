<?php
/**
 * Test Loading.php dengan session yang ter-set
 */

// Simulate session yang sudah ter-set dari login
session_start();
$_SESSION['role'] = 'pembeli';
$_SESSION['id_user'] = '86';
$_SESSION['username'] = 'pembeli_test_023059';
$_SESSION['email'] = 'pembeli1780014659@test.com';

echo "<h2>Testing Loading.php Logic</h2>";
echo "<pre>";

error_log("Test_loading - Session ID: " . session_id());
error_log("Test_loading - SESSION: " . json_encode($_SESSION));

if (!isset($_SESSION['role']) || empty($_SESSION['role'])) {
    error_log("Test_loading - No role found, redirecting to login");
    echo "❌ No role, would redirect to login\n";
} else {
    echo "✅ Role found: " . $_SESSION['role'] . "\n";
    
    $role = $_SESSION['role'];
    
    // Gunakan path absolut sesuai struktur aplikasi di localhost
    if ($role == 'penjual') {
        $tujuan = "/kantin/app/penjual/dashboard_penjual.php";
    } elseif ($role == 'admin') {
        $tujuan = "/kantin/app/admin/dashboard_admin.php";
    } else {
        $tujuan = "/kantin/app/pembeli/dashboard.php";
    }
    
    echo "✅ Redirect target: " . $tujuan . "\n";
    error_log("Test_loading - Redirecting to: $tujuan");
}

echo "</pre>";

// Check apakah file accessible
echo "<h2>File Accessibility Check</h2>";
echo "<pre>";

$files_to_check = [
    "/kantin/app/penjual/dashboard_penjual.php",
    "/kantin/app/admin/dashboard_admin.php",
    "/kantin/app/pembeli/dashboard.php"
];

foreach ($files_to_check as $file) {
    // Convert web path to file path
    $file_path = 'C:\\xampp\\htdocs' . $file;
    $exists = file_exists($file_path);
    echo ($exists ? "✅" : "❌") . " " . $file . " (" . $file_path . ")\n";
}

echo "</pre>";

// Test direct file access via web
echo "<h2>Direct Web Access Test</h2>";
echo "<pre>";

$test_urls = [
    "http://localhost/kantin/app/penjual/dashboard_penjual.php",
    "http://localhost/kantin/app/admin/dashboard_admin.php",
    "http://localhost/kantin/app/pembeli/dashboard.php"
];

foreach ($test_urls as $url) {
    $response = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 2]]));
    if ($response !== false) {
        echo "✅ " . $url . " (accessible)\n";
        echo "  First 100 chars: " . substr($response, 0, 100) . "...\n";
    } else {
        echo "❌ " . $url . " (NOT accessible)\n";
    }
}

echo "</pre>";

// Check error logs
echo "<h2>Error Log (Last 20 lines)</h2>";
echo "<pre>";
$log_path = 'C:\\xampp\\apache\\logs\\error.log';
if (file_exists($log_path)) {
    $lines = file($log_path);
    $last_lines = array_slice($lines, -20);
    foreach ($last_lines as $line) {
        echo htmlspecialchars($line);
    }
} else {
    echo "Error log not found at: $log_path";
}
echo "</pre>";
?>
