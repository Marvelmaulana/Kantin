<?php
session_start();
include __DIR__ . '/config/config.php';

// Auto-login as test seller for testing
$_SESSION['id_user'] = 90;
$_SESSION['role'] = 'penjual';
$_SESSION['username'] = 'test_penjual';
$_SESSION['id_kantin'] = 20;
$_SESSION['nama_kantin'] = 'Kantin Test Penjual';

// Redirect to edit profil
header("Location: app/penjual/edit_profil.php");
exit();
?>
