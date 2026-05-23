<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$id_kantin = (int)($_GET['id'] ?? 0);
if ($id_kantin <= 0) {
    header('Location: manajemen_kantin.php?error=Data+tidak+valid');
    exit();
}

$kantin = kk_fetch_one($koneksi, "SELECT id_kantin, id_user FROM kantin WHERE id_kantin=$id_kantin LIMIT 1");
if (!$kantin) {
    header('Location: manajemen_kantin.php?error=Kantin+tidak+ditemukan');
    exit();
}

$id_user = (int)($kantin['id_user'] ?? 0);

mysqli_begin_transaction($koneksi);
try {
    mysqli_query($koneksi, "DELETE FROM keranjang WHERE id_menu IN (SELECT id_menu FROM menu WHERE id_kantin=$id_kantin)");
    mysqli_query($koneksi, "DELETE FROM favorit WHERE id_menu IN (SELECT id_menu FROM menu WHERE id_kantin=$id_kantin)");
    mysqli_query($koneksi, "DELETE FROM menu WHERE id_kantin=$id_kantin");
    mysqli_query($koneksi, "DELETE FROM kantin WHERE id_kantin=$id_kantin");
    if ($id_user > 0) {
        mysqli_query($koneksi, "UPDATE users SET id_kantin=NULL WHERE id_user=$id_user AND role='penjual'");
    }
    mysqli_commit($koneksi);
    header('Location: manajemen_kantin.php?success=hapus');
    exit();
} catch (Throwable $e) {
    mysqli_rollback($koneksi);
    header('Location: manajemen_kantin.php?error=' . urlencode('Gagal menghapus kantin'));
    exit();
}
