<?php
session_start();
include('../../config/config.php');

// Proteksi: Pastikan login sebagai penjual
if (!isset($_SESSION['id_kantin'])) {
    header("Location: ../halaman_login.php");
    exit();
}

$id_k = $_SESSION['id_kantin'];
$username_kantin = $_SESSION['username'] ?? 'kantin_user';

// 1. Hitung Total Menu Aktif
$q_menu = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM menu WHERE id_kantin = '$id_k'");
$d_menu = mysqli_fetch_assoc($q_menu);

// 2. Hitung Pesanan Perlu Diproses
$q_proses = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pesanan WHERE id_kantin = '$id_k' AND status = 'Diproses'");
$d_proses = mysqli_fetch_assoc($q_proses);

// 3. Hitung Total Pendapatan
$q_income = mysqli_query($koneksi, "SELECT SUM(total_harga) as total FROM pesanan WHERE id_kantin = '$id_k' AND (status = 'Selesai' OR status = 'Lunas')");
$d_income = mysqli_fetch_assoc($q_income);
$pendapatan = $d_income['total'] ?? 0;
?>

<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Seller Dashboard - Kantin Kita</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Be+Vietnam+Pro:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#b22204",
                        "on-surface": "#271815",
                        "on-surface-variant": "#5b403b",
                        "surface": "#fff8f6",
                        "surface-container-low": "#fff0ee",
                        "surface-container-high": "#ffe2dc",
                        "surface-container-highest": "#f9dcd6",
                        "tertiary-fixed": "#c2e8ff",
                        "secondary-fixed": "#ffdad3"
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Be Vietnam Pro', sans-serif; overflow-x: hidden; }
        h1, h2, h3 { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-surface text-on-surface">

<div class="flex min-h-screen relative">
    
    <?php include '../../includes/sidebar_penjual.php'; ?>

    <main class="flex-1 w-full lg:ml-64 p-4 md:p-8 transition-all">
        <header class="mb-8 mt-12 lg:mt-0 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
            <div>
                <h2 class="text-2xl md:text-3xl font-extrabold text-on-surface tracking-tight">Dashboard Penjual</h2>
                <p class="text-sm text-on-surface-variant mt-1">Halo <span class="font-bold text-primary"><?= $username_kantin; ?></span>, berikut ringkasan hari ini.</p>
            </div>
            <div class="flex gap-2">
                <button class="p-2 rounded-full hover:bg-surface-container-high text-on-surface-variant"><span class="material-symbols-outlined">notifications</span></button>
                <button class="p-2 rounded-full hover:bg-surface-container-high text-on-surface-variant"><span class="material-symbols-outlined">settings</span></button>
            </div>
        </header>

        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 mb-10">
            <div class="p-6 rounded-2xl bg-tertiary-fixed shadow-sm border border-blue-100">
                <span class="material-symbols-outlined text-blue-600">menu_book</span>
                <div class="mt-4">
                    <p class="text-xs font-bold text-blue-800 uppercase">Total Menu Aktif</p>
                    <h3 class="text-3xl font-black text-blue-900 mt-1"><?= $d_menu['total']; ?></h3>
                </div>
            </div>

            <div class="p-6 rounded-2xl bg-secondary-fixed shadow-sm border border-orange-100">
                <span class="material-symbols-outlined text-orange-700">hourglass_empty</span>
                <div class="mt-4">
                    <p class="text-xs font-bold text-orange-800 uppercase">Pesanan Diproses</p>
                    <h3 class="text-3xl font-black text-orange-900 mt-1"><?= $d_proses['total']; ?></h3>
                </div>
            </div>

            <div class="p-6 rounded-2xl bg-surface-container-high shadow-sm border border-primary/5 sm:col-span-2 lg:col-span-1">
                <span class="material-symbols-outlined text-primary">payments</span>
                <div class="mt-4">
                    <p class="text-xs font-bold text-on-surface-variant uppercase">Total Pendapatan</p>
                    <h3 class="text-2xl font-black text-on-surface mt-1">Rp <?= number_format($pendapatan, 0, ',', '.'); ?></h3>
                </div>
            </div>
        </section>

        <section class="mb-10">
            <h2 class="text-lg font-bold mb-4">Akses Cepat</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <button onclick="window.location.href='kelola_menu_penjual.php'" class="flex items-center gap-3 p-4 bg-primary text-white rounded-2xl font-bold hover:shadow-lg transition-all active:scale-95 text-sm">
                    <span class="material-symbols-outlined bg-white/20 p-2 rounded-xl text-[20px]">add_circle</span> Tambah Menu
                </button>
                <button onclick="window.location.href='pesanan_masuk.php'" class="flex items-center gap-3 p-4 bg-blue-600 text-white rounded-2xl font-bold hover:shadow-lg transition-all active:scale-95 text-sm">
                    <span class="material-symbols-outlined bg-white/20 p-2 rounded-xl text-[20px]">shopping_basket</span> Cek Pesanan
                </button>
                <button onclick="window.location.href='riwayat_pesanan_penjual.php'" class="flex items-center gap-3 p-4 bg-surface-container-highest text-on-surface rounded-2xl font-bold hover:bg-surface-variant transition-all active:scale-95 text-sm">
                    <span class="material-symbols-outlined bg-black/5 p-2 rounded-xl text-[20px]">assignment</span> Riwayat
                </button>
            </div>
        </section>

        <section class="bg-surface-container-low rounded-3xl p-4 md:p-8">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-bold text-lg">Pesanan Terbaru</h3>
                <a class="text-xs font-bold text-primary" href="pesanan_masuk.php">Lihat Semua</a>
            </div>
            <div class="space-y-3">
                <?php
                $q_list = mysqli_query($koneksi, "SELECT pesanan.*, users.username FROM pesanan 
                                                 JOIN users ON pesanan.id_user = users.id_user 
                                                 WHERE pesanan.id_kantin = '$id_k' 
                                                 ORDER BY pesanan.tanggal DESC LIMIT 3");
                
                if(mysqli_num_rows($q_list) > 0):
                    while($row = mysqli_fetch_assoc($q_list)):
                ?>
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 p-4 rounded-2xl bg-white hover:bg-orange-50 transition-colors border border-transparent hover:border-orange-100">
                    <div class="flex items-center gap-4 flex-1">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary font-bold text-xs">#<?= $row['id_pesanan']; ?></div>
                        <div class="overflow-hidden">
                            <h4 class="font-bold text-on-surface truncate text-sm"><?= $row['username']; ?></h4>
                            <p class="text-[10px] text-on-surface-variant"><?= date('d M, H:i', strtotime($row['tanggal'])); ?> • <?= $row['metode_pembayaran']; ?></p>
                        </div>
                    </div>
                    <div class="flex justify-between sm:text-right items-center sm:items-end">
                        <p class="font-bold text-primary text-sm">Rp <?= number_format($row['total_harga']); ?></p>
                        <span class="text-[9px] px-2 py-0.5 rounded-full bg-secondary-fixed text-orange-900 font-bold uppercase ml-2"><?= $row['status']; ?></span>
                    </div>
                </div>
                <?php endwhile; else: ?>
                    <div class="text-center py-8">
                        <span class="material-symbols-outlined text-4xl text-stone-300">order_approve</span>
                        <p class="text-sm text-stone-400 mt-2">Belum ada pesanan terbaru.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>

</body>
</html>