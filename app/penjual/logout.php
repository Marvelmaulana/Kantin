<?php
session_start();

// Menghapus semua data session
$_SESSION = [];
session_unset();
session_destroy();

// Mengarahkan kembali ke halaman login yang ada di folder auth
// ../../ berarti keluar dari folder 'penjual', lalu keluar dari 'app'
header("Location: ../auth/login.php");
exit();
?>