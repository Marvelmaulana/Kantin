<?php
session_start();
include(__DIR__ . '/../../config/config.php');

// 1. PROTEKSI HALAMAN
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'penjual') {
    header("Location: login.php");
    exit;
}

// 2. TANGKAP DATA DARI FORM (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari input hidden dan select
    $id_p   = mysqli_real_escape_string($koneksi, $_POST['id_pesanan']);
    $status = mysqli_real_escape_string($koneksi, $_POST['status_baru']);

    // 3. UPDATE KE TABEL PESANAN (Bukan transaksi)
    $sql = "UPDATE pesanan SET status = '$status' WHERE id_pesanan = '$id_p'";

    if (mysqli_query($koneksi, $sql)) {
        // Jika berhasil, balikkan ke halaman pesanan masuk
        echo "<script>
                alert('Status pesanan #$id_p berhasil diubah menjadi $status!'); 
                window.location='pesanan_masuk.php';
              </script>";
    } else {
        echo "Gagal update: " . mysqli_error($koneksi);
    }
} else {
    // Jika mencoba akses file ini langsung tanpa form, tendang balik
    header("Location: pesanan_masuk.php");
    exit;
}
?>