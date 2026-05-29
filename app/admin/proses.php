<?php
/**
 * Admin Login Processing
 * Proses login admin dengan security checks
 */

// Set session timeout 1 jam
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => '',
    'secure' => true,  // Hanya HTTPS
    'httponly' => true,  // Tidak bisa diakses via JavaScript
    'samesite' => 'Strict'  // CSRF protection
]);

session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/auth_helpers.php');

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

// Check login button is set
if (!isset($_POST['login_btn'])) {
    header("Location: login.php");
    exit();
}

// Sanitize inputs
$user_input_raw = isset($_POST['user_input']) ? trim($_POST['user_input']) : '';
$password_raw = isset($_POST['password']) ? trim($_POST['password']) : '';

// Validate inputs not empty
if (empty($user_input_raw) || empty($password_raw)) {
    echo "<script>alert('Username/Email dan Password tidak boleh kosong!'); window.location='login.php';</script>";
    exit();
}

// SECURITY: Use prepared statement to prevent SQL injection
$user_input_escaped = mysqli_real_escape_string($koneksi, $user_input_raw);

$query = "SELECT id_user, username, password, role, bahasa FROM users 
          WHERE (username = ? OR email = ?) AND role = 'admin' LIMIT 1";

$stmt = mysqli_prepare($koneksi, $query);
if (!$stmt) {
    echo "<script>alert('Kesalahan database. Silakan coba lagi.'); window.location='login.php';</script>";
    exit();
}

// Bind parameters (ss = string, string)
mysqli_stmt_bind_param($stmt, "ss", $user_input_escaped, $user_input_escaped);

// Execute
if (!mysqli_stmt_execute($stmt)) {
    echo "<script>alert('Kesalahan database. Silakan coba lagi.'); window.location='login.php';</script>";
    mysqli_stmt_close($stmt);
    exit();
}

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Check user exists
if (!$data) {
    echo "<script>alert('User tidak ditemukan atau bukan admin!'); window.location='login.php';</script>";
    exit();
}

// Verify password using password_verify (already hashed in database)
if (!password_verify($password_raw, $data['password'])) {
    echo "<script>alert('Password salah!'); window.location='login.php';</script>";
    exit();
}

// SECURITY: Regenerate session ID after successful login
session_regenerate_id(true);

// Set session variables
$_SESSION['id_user'] = $data['id_user'];
$_SESSION['username'] = $data['username'];
$_SESSION['role'] = $data['role'];
$_SESSION['status'] = 'login';
$_SESSION['login_time'] = time();

// Set language from user profile
$bahasa = $data['bahasa'] ?? 'id';
$_SESSION['lang'] = $bahasa;
$_SESSION['bahasa'] = $bahasa;

// Success redirect to dashboard
header('Location: dashboard_admin.php');
exit();
