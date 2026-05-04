<?php
include('../../config/config.php');
session_start();

// Proteksi akses jika tidak ada session kantin
if (!isset($_SESSION['id_kantin'])) {
    exit("Akses ditolak");
}

$aksi = isset($_GET['aksi']) ? $_GET['aksi'] : '';
$id_k = $_SESSION['id_kantin'];

if ($aksi == 'tambah') {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_menu']);
    $harga = $_POST['harga'];
    $kategori = $_POST['kategori'];
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);

    // Logika Gambar
    $filename = $_FILES['foto']['name'];
    $tmp_name = $_FILES['foto']['tmp_name'];
    $file_ext = pathinfo($filename, PATHINFO_EXTENSION);
    
    // Buat nama unik agar tidak bentrok
    $newName = time() . "_" . uniqid() . "." . $file_ext; 
    
    // PATH: Pastikan folder 'uploads' ada di folder KANTIN/uploads/
    $path = "../../uploads/" . $newName;

    // Cek apakah folder uploads benar-benar ada
    if (!is_dir("../../uploads/")) {
        mkdir("../../uploads/", 0777, true);
    }

    if (move_uploaded_file($tmp_name, $path)) {
        // Tambahkan id_kantin agar menu terikat ke penjual yang benar
        $sql = "INSERT INTO menu (id_kantin, nama_menu, harga, foto, kategori, deskripsi, status) 
                VALUES ('$id_k', '$nama', '$harga', '$newName', '$kategori', '$deskripsi', 'Tersedia')";
        
        if(mysqli_query($koneksi, $sql)) {
            header("Location: kelola_menu_penjual.php");
        } else {
            echo "Error Database: " . mysqli_error($koneksi);
        }
    } else {
        echo "Gagal upload gambar. Cek permission folder uploads.";
    }
}

if ($aksi == 'hapus') {
    $id = mysqli_real_escape_string($koneksi, $_GET['id']);
    
    // Ambil nama file sebelum dihapus datanya
    $q = mysqli_query($koneksi, "SELECT foto FROM menu WHERE id_menu = '$id' AND id_kantin = '$id_k'");
    $data = mysqli_fetch_assoc($q);
    
    if($data) {
        // Hapus file fisik jika ada
        $file_target = "../../uploads/" . $data['foto'];
        if(file_exists($file_target) && !empty($data['foto'])) {
            unlink($file_target);
        }

        // Hapus data dari database
        mysqli_query($koneksi, "DELETE FROM menu WHERE id_menu = '$id'");
    }
    
    header("Location: kelola_menu_penjual.php");
}
?>