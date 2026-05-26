<?php
/**
 * Proses Penghapusan Siswa Kelas 12
 * 
 * Parameters:
 * - action: 'delete_single' atau 'delete_all'
 * - id: ID siswa (hanya untuk delete_single)
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

// Ambil action dari GET parameter
$action = $_GET['action'] ?? '';

if ($action === 'delete_single') {
    // Hapus siswa tertentu
    $id_siswa = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id_siswa <= 0) {
        header("Location: manajemen_siswa.php?error=invalid_id");
        exit();
    }
    
    $result = delete_student_by_id($koneksi, $id_siswa);
    
    if ($result['success']) {
        header("Location: manajemen_siswa.php?success=delete");
        exit();
    } else {
        header("Location: manajemen_siswa.php?error=" . urlencode($result['message']));
        exit();
    }
    
} elseif ($action === 'delete_all') {
    // Hapus semua siswa kelas 12
    $result = delete_siswa_kelas_12($koneksi);
    
    if ($result['success']) {
        header("Location: manajemen_siswa.php?success=delete_kelas_12&deleted=" . $result['deleted_count']);
        exit();
    } else {
        header("Location: manajemen_siswa.php?error=" . urlencode($result['message']));
        exit();
    }
    
} else {
    header("Location: manajemen_siswa.php?error=invalid_action");
    exit();
}
?>
