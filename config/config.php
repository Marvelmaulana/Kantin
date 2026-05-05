<?php
// BASE PATH (biar gampang include dari mana saja)
define('BASE_PATH', dirname(__DIR__));

// konfigurasi database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_kantin";

// koneksi database (pakai SATU variabel saja)
$koneksi = mysqli_connect($host, $user, $pass, $db);

// cek koneksi
if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// timezone
date_default_timezone_set("Asia/Jakarta");
?>