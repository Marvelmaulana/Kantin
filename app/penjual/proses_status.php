<?php
session_start();
include(__DIR__ . '/../../config/config.php');

// 1. PROTEKSI HALAMAN
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'penjual') {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: pesanan_masuk.php");
    exit;
}

$id_p = (int)($_POST['id_pesanan'] ?? 0);
$status = trim($_POST['status_baru'] ?? '');
$id_kantin = (int)($_SESSION['id_kantin'] ?? 0);
$status_valid = ['Pending', 'Diproses', 'Siap Diambil', 'Selesai', 'Dibatalkan'];

if ($id_p <= 0 || $id_kantin <= 0 || !in_array($status, $status_valid, true)) {
    header("Location: pesanan_masuk.php?error=Data+tidak+valid");
    exit;
}

$stmt = mysqli_prepare($koneksi, "UPDATE pesanan SET status = ? WHERE id_pesanan = ? AND id_kantin = ?");
mysqli_stmt_bind_param($stmt, 'sii', $status, $id_p, $id_kantin);
$ok = mysqli_stmt_execute($stmt);
$affected = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

if ($ok && $affected >= 0) {
    header("Location: " . ($status === 'Selesai' ? 'riwayat_penjual.php?success=selesai' : 'pesanan_masuk.php?success=1'));
    exit;
}

$err = urlencode(mysqli_error($koneksi) ?: 'Pesanan tidak ditemukan');
header("Location: pesanan_masuk.php?error=$err");
exit;
?>
