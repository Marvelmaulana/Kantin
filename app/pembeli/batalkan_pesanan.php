<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit();
}

$id_user   = $_SESSION['id_user'];
$id_pesanan = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_pesanan <= 0) {
    header("Location: pesanan.php");
    exit();
}

$id_pesanan = mysqli_real_escape_string($koneksi, $id_pesanan);

$cek = mysqli_query($koneksi, "
    SELECT * FROM pesanan
    WHERE id_pesanan = '$id_pesanan'
    AND id_user = '$id_user'
    AND status = 'Pending'
");

if (mysqli_num_rows($cek) > 0) {
    mysqli_query($koneksi, "UPDATE pesanan SET status = 'Dibatalkan' WHERE id_pesanan = '$id_pesanan'");
    $msg = 'Pesanan berhasil dibatalkan.';
} else {
    $msg = 'Pesanan tidak dapat dibatalkan.';
}

header("Location: pesanan.php?msg=" . urlencode($msg));
exit();
