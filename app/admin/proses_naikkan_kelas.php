<?php
/**
 * Proses Naikkan Kelas Satu Siswa
 * 
 * Parameters:
 * - id: ID siswa yang akan dinaikkan kelasnya
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

// Ambil ID siswa dari GET parameter
$id_siswa = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_siswa <= 0) {
    header("Location: manajemen_siswa.php?error=invalid_id");
    exit();
}

// Naikkan kelas siswa
$result = promote_student_kelas($koneksi, $id_siswa);

if ($result['success']) {
    // Redirect dengan pesan sukses
    header("Location: manajemen_siswa.php?success=promote");
    exit();
} else {
    // Redirect dengan pesan error
    header("Location: manajemen_siswa.php?error=" . urlencode($result['message']));
    exit();
}
?>
