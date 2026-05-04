<?php
// Pastikan session dimulai paling atas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Gunakan path absolut yang lebih aman untuk config
include(__DIR__ . '/../../config/config.php');

// 1. CEK PROTEKSI (Ganti pengecekan agar lebih fleksibel saat debugging)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'penjual') {
    // Jika tidak login sebagai penjual, arahkan ke login
    header("Location: ../auth/login.php");
    exit;
}

// 2. PROSES UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_pesanan'])) {
    
    $id_pesanan = mysqli_real_escape_string($koneksi, $_POST['id_pesanan']);
    $status_baru = mysqli_real_escape_string($koneksi, $_POST['status_baru']);

    // Update status di database
    $query = "UPDATE pesanan SET status = '$status_baru' WHERE id_pesanan = '$id_pesanan'";
    
    if (mysqli_query($koneksi, $query)) {
        // Berhasil: Langsung balik ke halaman pesanan
        echo "<script>
                alert('Berhasil: Status diubah menjadi $status_baru');
                window.location = 'pesanan_masuk.php';
              </script>";
    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
} else {
    // Jika akses file ini tanpa POST data
    header("Location: pesanan_masuk.php");
    exit;
}