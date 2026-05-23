<?php
session_start();
include(__DIR__ . '/../../config/config.php');
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') { header('Location: ../auth/login.php'); exit(); }
?>
<!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Laporan Pembeli</title><script src="https://cdn.tailwindcss.com"></script></head><body class="flex"><?php include '../../includes/sidebar_admin.php'; ?>
<main class="flex-1 p-8"> <h1 class="text-2xl font-bold">Laporan Pembeli</h1><p class="mt-4 text-slate-600">Halaman laporan pembeli (placeholder).</p></main></body></html>