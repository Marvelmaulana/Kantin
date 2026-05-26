<?php
/**
 * Quick Login Test Script
 * Untuk test login tanpa perlu UI interaksi
 */

session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');

// Set POST data
$_POST['login_btn'] = 1;
$_POST['role'] = 'pembeli';
$_POST['user_input'] = 'testuser';
$_POST['password'] = 'password123';
$_POST['tipe_pengguna'] = 'siswa';
$_POST['kelas'] = '10';

// Include proses.php to test login
include('proses.php');
?>
