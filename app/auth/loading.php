<?php
session_start();

// Debug: Log session status
error_log("Loading.php - Session ID: " . session_id());
error_log("Loading.php - SESSION: " . json_encode($_SESSION));

if (!isset($_SESSION['role']) || empty($_SESSION['role'])) {
    error_log("Loading.php - No role found, redirecting to login");
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];

// Tentukan URL destination berdasarkan role
if ($role == 'penjual') {
    $tujuan = "/kantin/app/penjual/dashboard_penjual.php";
} elseif ($role == 'admin') {
    $tujuan = "/kantin/app/admin/dashboard_admin.php";
} else {
    $tujuan = "/kantin/app/pembeli/dashboard.php";
}

error_log("Loading.php - Redirecting to: $tujuan");

// Redirect dengan HTTP header (paling reliable) 
header("Location: " . $tujuan, true, 303);
exit();
