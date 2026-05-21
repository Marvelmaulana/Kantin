<?php
session_start(); // Mulai session agar bisa dihapus
session_unset(); // Kosongkan semua data session
session_destroy(); // Hancurkan session

// Arahkan kembali ke halaman login
header("Location: login.php");
exit;
?>