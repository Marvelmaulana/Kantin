<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = (int)($_GET['id'] ?? 0);
if ($id_user <= 0) {
    header("Location: manajemen_penjual.php?error=Data+tidak+valid");
    exit();
}

$penjual = kk_fetch_one($koneksi, "SELECT id_user, id_kantin FROM users WHERE id_user=$id_user AND role='penjual' LIMIT 1");
if (!$penjual) {
    header("Location: manajemen_penjual.php?error=Penjual+tidak+ditemukan");
    exit();
}

$id_kantin = (int)($penjual['id_kantin'] ?? 0);

mysqli_begin_transaction($koneksi);
try {
    if ($id_kantin > 0) {
        mysqli_query($koneksi, "DELETE FROM keranjang WHERE id_menu IN (SELECT id_menu FROM menu WHERE id_kantin=$id_kantin)");
        mysqli_query($koneksi, "DELETE FROM favorit WHERE id_menu IN (SELECT id_menu FROM menu WHERE id_kantin=$id_kantin)");
        mysqli_query($koneksi, "DELETE FROM menu WHERE id_kantin=$id_kantin");
        mysqli_query($koneksi, "DELETE FROM kantin WHERE id_kantin=$id_kantin");
    }

    mysqli_query($koneksi, "DELETE FROM users WHERE id_user=$id_user AND role='penjual'");
    mysqli_commit($koneksi);
    header("Location: manajemen_penjual.php?success=hapus");
    exit();
} catch (Throwable $e) {
    mysqli_rollback($koneksi);
    header("Location: manajemen_penjual.php?error=" . urlencode($e->getMessage()));
    exit();
}
?>
