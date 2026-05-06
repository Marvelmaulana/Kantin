<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

if (isset($_GET['ids'])) {
    // Sanitasi: ambil hanya angka, pisahkan koma
    $raw = $_GET['ids'];
    $ids = array_filter(array_map('intval', explode(',', $raw)));

    if (!empty($ids)) {
        $ids_str = implode(',', $ids);
        mysqli_query($koneksi, "DELETE FROM keranjang WHERE id_keranjang IN ($ids_str) AND id_user = '$id_user'");
    }
}

header("Location: keranjang.php");
exit();
?>