<?php
session_start();
include(__DIR__ . '/../../config/config.php');

// 1. Cek Login
if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_user   = $_SESSION['id_user'];
    $id_menu   = mysqli_real_escape_string($koneksi, $_POST['id_menu']);
    $id_detail = mysqli_real_escape_string($koneksi, $_POST['id_detail']); // ID dari detail_pesanan
    $rating    = mysqli_real_escape_string($koneksi, $_POST['rating']);
    $komentar  = mysqli_real_escape_string($koneksi, $_POST['komentar']);
    
    // 2. Logika Upload Foto (Opsional)
    $foto_ulasan = "";
    if (isset($_FILES['foto_ulasan']) && $_FILES['foto_ulasan']['error'] == 0) {
        $target_dir = "../../uploads/ulasan/";
        
        // Buat folder jika belum ada
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES["foto_ulasan"]["name"], PATHINFO_EXTENSION);
        $file_name = "REV_" . time() . "_" . $id_user . "." . $file_extension;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["foto_ulasan"]["tmp_name"], $target_file)) {
            $foto_ulasan = $file_name;
        }
    }

    // 3. Simpan ke Database
    // Asumsi tabel bernama 'ulasan'
    $query = "INSERT INTO ulasan (id_user, id_menu, id_detail, rating, komentar, foto_ulasan, tanggal_ulasan) 
              VALUES ('$id_user', '$id_menu', '$id_detail', '$rating', '$komentar', '$foto_ulasan', NOW())";

    if (mysqli_query($koneksi, $query)) {
        // 4. Update status di detail_pesanan agar tidak bisa ulasan dua kali (opsional)
        // mysqli_query($koneksi, "UPDATE detail_pesanan SET status_ulasan = 'sudah' WHERE id_detail = '$id_detail'");

        echo "<script>
                alert('Terima kasih! Ulasan Anda sangat berarti bagi kami.');
                window.location.href = 'riwayat_pesanan.php'; 
              </script>";
    } else {
        echo "Gagal mengirim ulasan: " . mysqli_error($koneksi);
    }
} else {
    header("Location: dashboard.php");
    exit();
}
?>