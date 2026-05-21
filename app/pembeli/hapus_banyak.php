<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');

if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = (int)$_SESSION['id_user'];
if (!kk_verify_csrf($_GET['csrf_token'] ?? '')) {
    kk_abort_csrf();
}

$ids = array_filter(array_map('intval', explode(',', $_GET['ids'] ?? '')));

if (!empty($ids)) {
    $ids_str = implode(',', $ids);
    mysqli_query($koneksi, "DELETE FROM keranjang WHERE id_user = $id_user AND id_keranjang IN ($ids_str)");
}

header("Location: keranjang.php");
exit();
?>
