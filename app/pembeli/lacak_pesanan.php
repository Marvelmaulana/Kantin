<?php
session_start();
include(__DIR__ . '/../../config/config.php');

$id_pesanan = mysqli_real_escape_string($koneksi, $_GET['id']);

// Ambil status pesanan
$query = mysqli_query($koneksi, "SELECT * FROM pesanan WHERE id_pesanan = '$id_pesanan'");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    header("Location: pesanan.php");
    exit();
}

// Logika penentuan icon/warna berdasarkan status
$status = $data['status']; // Misal: Menunggu, Diproses, Siap Diambil, Selesai
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Lacak Pesanan - Kantin Kita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
</head>
<body class="bg-[#fff8f6] min-h-screen">

<header class="p-6 flex items-center gap-4">
    <button onclick="history.back()" class="material-symbols-outlined p-2 bg-white rounded-full shadow-sm">arrow_back</button>
    <h1 class="font-['Plus_Jakarta_Sans'] font-bold text-xl text-orange-800">Status Pesanan</h1>
</header>

<main class="max-w-md mx-auto px-6 pt-4">
    <div class="bg-white rounded-3xl p-8 shadow-xl shadow-orange-900/5 text-center border border-orange-50 mb-8">
        <div class="w-24 h-24 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <span class="material-symbols-outlined text-orange-600 text-5xl">
                <?= ($status == 'Selesai') ? 'check_circle' : 'restaurant'; ?>
            </span>
        </div>
        <h2 class="text-2xl font-extrabold text-stone-800 mb-2"><?= $status; ?></h2>
        <p class="text-stone-500 text-sm">
            <?= ($status == 'Diproses') ? 'Penjual sedang menyiapkan makananmu, mohon ditunggu ya!' : 'Silakan tunjukkan nomor pesanan ke penjual.'; ?>
        </p>
    </div>

    <div class="space-y-8 pl-4 border-l-2 border-dashed border-stone-200 ml-4">
        <div class="relative">
            <div class="absolute -left-[25px] w-4 h-4 rounded-full bg-green-500 ring-4 ring-green-100"></div>
            <p class="font-bold text-stone-800 leading-none">Pesanan Diterima</p>
            <p class="text-xs text-stone-400 mt-1">Pesanan masuk ke dapur</p>
        </div>

        <div class="relative">
            <div class="absolute -left-[25px] w-4 h-4 rounded-full <?= ($status == 'Diproses' || $status == 'Siap Diambil' || $status == 'Selesai') ? 'bg-green-500 ring-4 ring-green-100' : 'bg-stone-200'; ?>"></div>
            <p class="font-bold <?= ($status == 'Diproses') ? 'text-orange-600' : 'text-stone-400'; ?> leading-none">Sedang Disiapkan</p>
            <p class="text-xs text-stone-400 mt-1">Makanan sedang dimasak</p>
        </div>

        <div class="relative">
            <div class="absolute -left-[25px] w-4 h-4 rounded-full <?= ($status == 'Siap Diambil' || $status == 'Selesai') ? 'bg-green-500 ring-4 ring-green-100' : 'bg-stone-200'; ?>"></div>
            <p class="font-bold <?= ($status == 'Siap Diambil') ? 'text-orange-600' : 'text-stone-400'; ?> leading-none">Siap Diambil</p>
            <p class="text-xs text-stone-400 mt-1">Ambil di stan kantin</p>
        </div>
    </div>

    <div class="mt-12 bg-orange-700 rounded-2xl p-6 text-white flex justify-between items-center">
        <div>
            <p class="text-[10px] uppercase font-bold tracking-widest opacity-70">Order ID</p>
            <p class="text-xl font-black">#<?= str_pad($id_pesanan, 4, "0", STR_PAD_LEFT); ?></p>
        </div>
        <div class="text-right">
            <p class="text-[10px] uppercase font-bold tracking-widest opacity-70">Total Bayar</p>
            <p class="text-xl font-black">Rp <?= number_format($data['total_harga'], 0, ',', '.'); ?></p>
        </div>
    </div>
</main>

</body>
</html>