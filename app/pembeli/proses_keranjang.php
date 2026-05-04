<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (isset($_POST['tambah_keranjang'])) {
    $id_user = $_SESSION['id_user'];
    $id_menu = $_POST['id_menu'];
    $qty     = $_POST['qty'];

    // 1. Cek apakah menu ini sudah ada di keranjang user?
    $cek = mysqli_query($koneksi, "SELECT * FROM keranjang WHERE id_user='$id_user' AND id_menu='$id_menu'");
    
    if (mysqli_num_rows($cek) > 0) {
        // Jika sudah ada, update jumlahnya saja
        mysqli_query($koneksi, "UPDATE keranjang SET qty = qty + $qty WHERE id_user='$id_user' AND id_menu='$id_menu'");
    } else {
        // Jika belum ada, masukkan data baru
        mysqli_query($koneksi, "INSERT INTO keranjang (id_user, id_menu, qty) VALUES ('$id_user', '$id_menu', '$qty')");
    }

    // Alihkan ke halaman keranjang
    header("Location: keranjang.php");
}
?>