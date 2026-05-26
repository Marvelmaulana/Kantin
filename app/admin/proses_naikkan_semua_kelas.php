<?php
/**
 * Proses Kenaikan Kelas Otomatis untuk Semua Siswa
 * 
 * Alur:
 * 1. Hapus semua siswa kelas 12
 * 2. Naikkan siswa kelas 11 → 12
 * 3. Naikkan siswa kelas 10 → 11
 */

session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/student_helpers.php');

kk_ensure_buyer_schema($koneksi);

// Proteksi: hanya admin yang bisa akses
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Jalankan kenaikan kelas otomatis
$result = promote_all_students($koneksi);

if ($result['success']) {
    // Redirect dengan pesan sukses
    header("Location: manajemen_siswa.php?success=promote_all&deleted=" . $result['detail']['deleted_kelas_12']);
    exit();
} else {
    // Redirect dengan pesan error
    header("Location: manajemen_siswa.php?error=" . urlencode($result['message']));
    exit();
}
?>
