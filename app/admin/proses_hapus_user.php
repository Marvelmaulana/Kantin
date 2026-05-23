<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$id_user = (int)($_GET['id'] ?? 0);
if ($id_user <= 0) {
    header('Location: manajemen_user.php?error=Data+tidak+valid');
    exit();
}

$user = kk_fetch_one($koneksi, "SELECT id_user FROM users WHERE id_user=$id_user AND role='pembeli' LIMIT 1");
if (!$user) {
    header('Location: manajemen_user.php?error=User+tidak+ditemukan');
    exit();
}

$deleted = mysqli_query($koneksi, "DELETE FROM users WHERE id_user=$id_user AND role='pembeli'");
if ($deleted) {
    header('Location: manajemen_user.php?success=hapus');
    exit();
}

header('Location: manajemen_user.php?error=Gagal+menghapus+user');
exit();
