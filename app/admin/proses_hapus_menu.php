<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$id_menu = (int)($_GET['id'] ?? 0);
if ($id_menu <= 0) {
    header('Location: manajemen_menu.php?error=Data+tidak+valid');
    exit();
}

$menu = kk_fetch_one($koneksi, "SELECT id_menu FROM menu WHERE id_menu=$id_menu LIMIT 1");
if (!$menu) {
    header('Location: manajemen_menu.php?error=Menu+tidak+ditemukan');
    exit();
}

$deleted = mysqli_query($koneksi, "DELETE FROM menu WHERE id_menu=$id_menu");
if ($deleted) {
    header('Location: manajemen_menu.php?success=hapus');
    exit();
}

header('Location: manajemen_menu.php?error=Gagal+menghapus+menu');
exit();
