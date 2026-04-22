<?php
session_start();
include 'config.php';

if (!isset($_SESSION['keranjang']) || empty($_SESSION['keranjang'])) {
    header("Location: dashboard.php");
    exit;
}

$id_user = (int)$_SESSION['id_user'];
$id_kantin = (int)$_SESSION
