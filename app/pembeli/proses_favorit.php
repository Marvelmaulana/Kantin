<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = $_SESSION['id_user'];
$id_menu = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_menu <= 0) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

$id_menu = mysqli_real_escape_string($koneksi, $id_menu);

// Cek apakah sudah difavoritkan?
$cek = mysqli_query($koneksi, "SELECT * FROM favorit WHERE id_user = '$id_user' AND id_menu = '$id_menu'");

if (mysqli_num_rows($cek) > 0) {
    mysqli_query($koneksi, "DELETE FROM favorit WHERE id_user = '$id_user' AND id_menu = '$id_menu'");
} else {
    mysqli_query($koneksi, "INSERT INTO favorit (id_user, id_menu) VALUES ('$id_user', '$id_menu')");
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php'));
exit();
