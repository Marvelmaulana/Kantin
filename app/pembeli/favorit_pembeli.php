<?php
session_start();
include(__DIR__ . '/../../config/config.php');
$id_user = $_SESSION['id_user'];

$query = mysqli_query($koneksi, "SELECT menu.* FROM favorit 
    JOIN menu ON favorit.id_menu = menu.id_menu 
    WHERE favorit.id_user = '$id_user'");
?>