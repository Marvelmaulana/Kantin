<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Kantin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex">
    <?php include '../../includes/sidebar_admin.php'; ?>
    
    <main class="flex-1 p-8">
        <h1 class="text-2xl font-bold">Laporan Kantin</h1>
        <p class="mt-4 text-slate-600">Halaman laporan kantin sedang dalam pengembangan.</p>
        
        <!-- Placeholder content -->
        <div class="mt-8 bg-slate-100 p-6 rounded-lg">
            <p class="text-slate-600">Fitur ini akan menampilkan laporan detail untuk setiap kantin.</p>
        </div>
    </main>
</body>
</html>