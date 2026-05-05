<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user'])) { 
    header("Location: ../auth/login.php"); 
    exit(); 
}

$id_user = mysqli_real_escape_string($koneksi, $_SESSION['id_user']);

// Logika Pengambilan Data (Pesan Langsung vs Keranjang)
// Menggunakan mysqli_real_escape_string untuk keamanan (Anti SQL Injection)
$id_menu_langsung = isset($_GET['id_menu']) ? mysqli_real_escape_string($koneksi, $_GET['id_menu']) : null;
$qty_langsung = isset($_GET['qty']) ? (int)$_GET['qty'] : 1;

if ($id_menu_langsung) {
    // Mode: Beli Langsung
    $query = mysqli_query($koneksi, "SELECT menu.*, '$qty_langsung' AS qty, '' AS catatan, kantin.nama_kantin 
                                     FROM menu 
                                     JOIN kantin ON menu.id_kantin = kantin.id_kantin 
                                     WHERE menu.id_menu = '$id_menu_langsung'");
} else {
    // Mode: Dari Keranjang
    $query = mysqli_query($koneksi, "SELECT keranjang.*, menu.nama_menu, menu.harga, menu.foto, kantin.nama_kantin 
                                     FROM keranjang 
                                     JOIN menu ON keranjang.id_menu = menu.id_menu 
                                     JOIN kantin ON menu.id_kantin = kantin.id_kantin 
                                     WHERE keranjang.id_user = '$id_user'");
}

$total_items = mysqli_num_rows($query);

// Jika keranjang kosong dan tidak ada menu langsung, kembalikan ke dashboard
if ($total_items == 0) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Pembayaran - Kantin Kita</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <style>
        body { font-family: 'Be Vietnam Pro', sans-serif; background-color: #fff8f6; }
        .headline-font { font-family: 'Plus Jakarta Sans', sans-serif; }
        .method-content { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-out; }
        .method-section.active .method-content { max-height: 500px; }
        .method-section.active .arrow-icon { transform: rotate(180deg); }
        .arrow-icon { transition: transform 0.3s; }
    </style>
</head>
<body class="pb-40">

<header class="bg-white/80 backdrop-blur-md flex items-center justify-between px-4 h-16 sticky top-0 z-50 border-b border-zinc-100">
    <div class="flex items-center gap-3">
        <button onclick="history.back()" class="w-10 h-10 flex items-center justify-center rounded-full hover:bg-zinc-100">
            <span class="material-symbols-outlined text-zinc-600">arrow_back</span>
        </button>
        <div>
            <h1 class="headline-font font-bold text-lg">Pembayaran</h1>
            <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest"><?= $total_items ?> Items</span>
        </div>
    </div>
</header>

<main class="max-w-md mx-auto px-4 py-6 space-y-6">
    <div class="p-4 bg-white rounded-2xl shadow-sm border border-zinc-100">
        <p class="text-[10px] font-bold text-[#b22204] uppercase">Order Reference</p>
        <h2 class="headline-font font-extrabold text-lg">#INV-<?= date('Ymd') ?>-<?= $_SESSION['id_user'] ?></h2>
    </div>

    <section class="space-y-3">
        <h3 class="headline-font font-bold text-sm px-1">Detail Pesanan</h3>
        <?php 
        $total_bayar = 0;
        while($row = mysqli_fetch_assoc($query)): 
            $sub = $row['harga'] * $row['qty'];
            $total_bayar += $sub;
        ?>
        <div class="flex gap-4 p-3 bg-white rounded-2xl border border-zinc-100">
            <img src="../../uploads/<?= $row['foto'] ?>" class="w-16 h-16 rounded-xl object-cover bg-zinc-100" onerror="this.src='../../assets/img/default-food.jpg'">
            <div class="flex-1">
                <div class="flex justify-between items-start">
                    <h4 class="font-bold text-sm"><?= $row['nama_menu'] ?></h4>
                    <span class="text-xs font-bold text-zinc-400">x<?= $row['qty'] ?></span>
                </div>
                <p class="text-[11px] text-[#b22204] font-bold mt-1">Rp <?= number_format($sub, 0, ',', '.') ?></p>
            </div>
        </div>
        <?php endwhile; ?>
    </section>

    <section class="space-y-4">
        <h3 class="headline-font font-bold text-sm px-1">Metode Pembayaran</h3>

        <div class="method-section bg-white rounded-2xl border border-zinc-100 overflow-hidden">
            <button onclick="toggleMethod(this)" class="w-full flex items-center justify-between p-4 focus:outline-none">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[#b22204]">account_balance_wallet</span>
                    <span class="font-bold text-sm">E-Wallet</span>
                </div>
                <span class="material-symbols-outlined arrow-icon text-zinc-400">expand_more</span>
            </button>
            <div class="method-content bg-zinc-50/50">
                <div class="p-4 grid grid-cols-3 gap-3">
                    <label class="cursor-pointer group">
                        <input type="radio" name="payment_method" value="GOPAY" class="hidden peer">
                        <div class="flex flex-col items-center p-3 rounded-xl border-2 border-transparent bg-white shadow-sm peer-checked:border-[#b22204] transition-all">
                            <span class="material-symbols-outlined text-blue-500 mb-1">payments</span>
                            <span class="text-[10px] font-bold">GOPAY</span>
                        </div>
                    </label>
                    <label class="cursor-pointer group">
                        <input type="radio" name="payment_method" value="OVO" class="hidden peer">
                        <div class="flex flex-col items-center p-3 rounded-xl border-2 border-transparent bg-white shadow-sm peer-checked:border-[#b22204] transition-all">
                            <span class="material-symbols-outlined text-purple-600 mb-1">wallet</span>
                            <span class="text-[10px] font-bold">OVO</span>
                        </div>
                    </label>
                    <label class="cursor-pointer group">
                        <input type="radio" name="payment_method" value="DANA" class="hidden peer">
                        <div class="flex flex-col items-center p-3 rounded-xl border-2 border-transparent bg-white shadow-sm peer-checked:border-[#b22204] transition-all">
                            <span class="material-symbols-outlined text-blue-400 mb-1">account_balance_wallet</span>
                            <span class="text-[10px] font-bold">DANA</span>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <label class="flex items-center justify-between p-4 bg-white rounded-2xl border border-zinc-100 cursor-pointer">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-[#b22204]">payments</span>
                <span class="font-bold text-sm">Tunai (Bayar di Kasir)</span>
            </div>
            <input type="radio" name="payment_method" value="CASH" class="w-4 h-4 text-[#b22204] focus:ring-[#b22204]">
        </label>
    </section>

    <section class="p-6 bg-zinc-100/50 rounded-[32px] space-y-3">
        <div class="flex justify-between items-center text-zinc-500 text-sm">
            <span>Subtotal</span>
            <span class="font-bold">Rp <?= number_format($total_bayar, 0, ',', '.') ?></span>
        </div>
        <div class="pt-3 border-t border-zinc-200 flex justify-between items-center">
            <span class="headline-font font-extrabold text-lg">Total Bayar</span>
            <span class="headline-font font-black text-xl text-[#b22204]">Rp <?= number_format($total_bayar, 0, ',', '.') ?></span>
        </div>
    </section>
</main>

<div class="fixed bottom-0 left-0 w-full p-6 bg-gradient-to-t from-[#fff8f6] via-[#fff8f6] to-transparent">
    <button onclick="prosesBayar()" class="w-full h-14 bg-gradient-to-r from-[#b22204] to-[#d63c1e] rounded-full flex items-center justify-center gap-3 text-white shadow-xl shadow-red-200 active:scale-95 transition-all">
        <span class="material-symbols-outlined">lock</span>
        <span class="headline-font font-extrabold text-lg tracking-tight">BAYAR SEKARANG</span>
    </button>
</div>

<script>
    function toggleMethod(element) {
        const section = element.parentElement;
        const isActive = section.classList.contains('active');
        document.querySelectorAll('.method-section').forEach(s => s.classList.remove('active'));
        if (!isActive) section.classList.add('active');
    }

    function prosesBayar() {
        const selected = document.querySelector('input[name="payment_method"]:checked');
        if (!selected) {
            alert('Silakan pilih metode pembayaran dulu ya!');
            return;
        }
        
        const method = selected.value;
        const urlParams = new URLSearchParams(window.location.search);
        const idMenu = urlParams.get('id_menu');
        const qty = urlParams.get('qty');

        // Membangun URL tujuan secara dinamis
        let targetUrl = `proses_checkout.php?method=${method}`;
        
        if (idMenu) {
            // Jika beli langsung
            targetUrl += `&id_menu=${idMenu}&qty=${qty}`;
        } else {
            // Jika dari keranjang
            targetUrl += `&source=cart`;
        }

        window.location.href = targetUrl;
    }
</script>

</body>
</html>