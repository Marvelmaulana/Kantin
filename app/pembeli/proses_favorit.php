<?php
session_start();
include(__DIR__ . '/../../config/config.php');

$id_user = $_SESSION['id_user'];
$id_menu = $_GET['id'];

// Cek apakah sudah difavoritkan?
$cek = mysqli_query($koneksi, "SELECT * FROM favorit WHERE id_user = '$id_user' AND id_menu = '$id_menu'");

if (mysqli_num_rows($cek) > 0) {
    // Jika sudah ada, maka HAPUS (Unfavorite)
    mysqli_query($koneksi, "DELETE FROM favorit WHERE id_user = '$id_user' AND id_menu = '$id_menu'");
} else {
    // Jika belum ada, maka TAMBAH
    mysqli_query($koneksi, "INSERT INTO favorit (id_user, id_menu) VALUES ('$id_user', '$id_menu')");
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit();