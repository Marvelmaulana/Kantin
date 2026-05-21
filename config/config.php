<?php

// timezone
date_default_timezone_set("Asia/Jakarta");

// base path
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_kantin";

// koneksi
$koneksi = mysqli_connect($host, $user, $pass, $db);

// cek koneksi
if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// include helpers dan run auto-migrate
include(__DIR__ . '/../includes/pembeli_helpers.php');
if (function_exists('kk_ensure_buyer_schema')) {
    kk_ensure_buyer_schema($koneksi);
}

// include language helper
include(__DIR__ . '/../includes/language_helper.php');

?>