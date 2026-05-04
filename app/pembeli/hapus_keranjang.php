<?php
session_start();
include(__DIR__ . '/../../config/config.php');

// 1. Pastikan user sudah login
if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

// 2. Cek apakah ada ID keranjang yang dikirim melalui URL
if (isset($_GET['id'])) {
    $id_keranjang = mysqli_real_escape_string($koneksi, $_GET['id']);

    // 3. Hapus data (pastikan id_user cocok agar tidak bisa menghapus keranjang orang lain)
    $query = "DELETE FROM keranjang WHERE id_keranjang = '$id_keranjang' AND id_user = '$id_user'";

    if (mysqli_query($koneksi, $query)) {
        // Berhasil hapus, kembalikan ke halaman keranjang
        header("Location: keranjang.php");
        exit();
    } else {
        echo "Gagal menghapus item: " . mysqli_error($koneksi);
    }
} else {
    // Jika tidak ada ID, langsung balik ke keranjang
    header("Location: keranjang.php");
    exit();
}
?>