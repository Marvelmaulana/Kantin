<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');

if (isset($_POST['tambah_keranjang'])) {
    if (!kk_verify_csrf($_POST['csrf_token'] ?? '')) {
        kk_abort_csrf();
    }
    $id_user = $_SESSION['id_user'];
    $id_menu = (int)$_POST['id_menu'];
    $qty     = max(1, (int)$_POST['qty']);

    $q_menu = mysqli_query($koneksi, "
        SELECT m.id_menu, m.stok, m.status, k.jam_buka, k.jam_tutup, k.status_buka
        FROM menu m
        JOIN kantin k ON m.id_kantin = k.id_kantin
        WHERE m.id_menu = $id_menu
    ");
    $menu = mysqli_fetch_assoc($q_menu);
    if (!$menu || !kk_is_menu_available($menu) || !kk_is_kantin_open($menu) || $qty > (int)$menu['stok']) {
        header("Location: detail_menu.php?id=$id_menu");
        exit();
    }

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
