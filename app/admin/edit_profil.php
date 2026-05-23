<?php
session_start();

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

header("Location: dashboard_admin.php");
exit();
?>
