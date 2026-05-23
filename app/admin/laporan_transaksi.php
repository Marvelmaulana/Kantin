<?php
session_start();
include(__DIR__ . '/../../config/config.php');
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
	header('Location: ../auth/login.php');
	exit();
}
?>
<!doctype html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Laporan Transaksi</title>
	<script src="https://cdn.tailwindcss.com"></script>
	<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
	<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
	<style>body{font-family:'Plus Jakarta Sans',sans-serif}</style>
</head>
<body class="flex">
	<?php include __DIR__ . '/../../includes/sidebar_admin.php'; ?>

	<main class="flex-1 p-8">
		<h1 class="text-2xl font-bold">Laporan Transaksi</h1>
		<p class="mt-4 text-slate-600">Halaman laporan transaksi (placeholder).</p>
	</main>
</body>
</html>