<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (isset($_GET['id']) && isset($_SESSION['id_user'])) {
    $id_user = $_SESSION['id_user'];
    $id_menu = mysqli_real_escape_string($koneksi, $_GET['id']);

    // Cek jumlah saat ini
    $cek = mysqli_query($koneksi, "SELECT qty FROM keranjang WHERE id_user = '$id_user' AND id_menu = '$id_menu'");
    $data = mysqli_fetch_assoc($cek);

    if ($data['qty'] > 1) {
        // Jika lebih dari 1, kurangi saja
        mysqli_query($koneksi, "UPDATE keranjang SET qty = qty - 1 WHERE id_user = '$id_user' AND id_menu = '$id_menu'");
    } else {
        // Jika sisa 1 lalu dikurangi, maka hapus dari keranjang
        mysqli_query($koneksi, "DELETE FROM keranjang WHERE id_user = '$id_user' AND id_menu = '$id_menu'");
    }
}

header("Location: keranjang.php");
exit();