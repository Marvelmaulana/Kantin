<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../halaman_login.php"); exit();
}

// AMBIL DATA REAL
$total_user = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role='pembeli'"))['total'] ?? 0;
$total_penjual = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role='penjual'"))['total'] ?? 0;
$pesanan_hari_ini = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pesanan WHERE DATE(tanggal) = CURDATE()"))['total'] ?? 0;
$total_pendapatan = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(total_harga) as total FROM pesanan WHERE status='Selesai'"))['total'] ?? 0;

$target_bulanan = 10000000; 
$persen_target = ($total_pendapatan > 0) ? ($total_pendapatan / $target_bulanan) * 100 : 0;
if($persen_target > 100) $persen_target = 100;

// LOGIKA GRAFIK
$grafik_data = [];
$days_label = ['Mon' => 'SEN', 'Tue' => 'SEL', 'Wed' => 'RAB', 'Thu' => 'KAM', 'Fri' => 'JUM', 'Sat' => 'SAB', 'Sun' => 'MIN'];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $day_en = date('D', strtotime($date));
    $res_grafik = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(total_harga) as total FROM pesanan WHERE DATE(tanggal) = '$date' AND status='Selesai'"));
    $grafik_data[] = ['label' => $days_label[$day_en], 'nilai' => $res_grafik['total'] ?? 0, 'is_today' => ($i == 0)];
}
$max_val = max(array_column($grafik_data, 'nilai')) ?: 1;

// LOGIKA KATEGORI
$res_kat = mysqli_query($koneksi, "SELECT m.kategori, COUNT(dp.id_menu) as jumlah FROM detail_pesanan dp JOIN menu m ON dp.id_menu = m.id_menu GROUP BY m.kategori");
$stats = ['Makanan' => 0, 'Minuman' => 0, 'Cemilan' => 0];
$total_stats = 0;
while ($row = mysqli_fetch_assoc($res_kat)) {
    $nama_kat = ucfirst(strtolower($row['kategori']));
    if (isset($stats[$nama_kat])) { $stats[$nama_kat] = $row['jumlah']; $total_stats += $row['jumlah']; }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/><meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Dashboard Admin - Kantin Kita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    <script>
        tailwind.config = { theme: { extend: { colors: { 'bg-soft': '#FFF9F8', 'primary-orange': '#E25E3E', 'accent-blue': '#2D9CDB', 'accent-green': '#27AE60' }, borderRadius: { '4xl': '2.5rem' } } } }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="bg-bg-soft text-slate-800 flex">

    <?php include '../../includes/sidebar_admin.php'; ?>

    <main class="flex-1 w-full lg:ml-72 p-6 md:p-10">
        <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-10 mt-14 lg:mt-0">
            <div>
                <h2 class="text-3xl font-extrabold text-[#003049]">Dashboard Overview</h2>
                <p class="text-slate-400 font-medium">Laporan statistik operasional Kantin Kita.</p>
            </div>
            <div class="flex flex-wrap items-center gap-4 w-full md:w-auto">
                <div class="bg-white px-5 py-3 rounded-2xl border border-slate-100 flex items-center gap-3 shadow-sm text-sm font-bold text-slate-600">
                    <span class="material-symbols-outlined text-slate-400 text-lg">calendar_today</span>
                    <?= date('M d, Y') ?>
                </div>
                <button onclick="window.print()" class="bg-primary-orange text-white px-6 py-3 rounded-2xl font-bold shadow-lg shadow-orange-200 flex items-center justify-center gap-2 hover:scale-105 transition-all w-full md:w-auto">
                    <span class="material-symbols-outlined text-xl">print</span> Print Laporan
                </button>
            </div>
        </header>

        <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-10">
            <div class="bg-white p-8 rounded-4xl border border-slate-50 flex items-center gap-5 shadow-sm">
                <div class="w-14 h-14 rounded-2xl bg-[#E8F5FD] flex items-center justify-center text-accent-blue shrink-0">
                    <span class="material-symbols-outlined text-3xl font-bold">group</span>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total User</p>
                    <h3 class="text-2xl font-black text-[#003049]"><?= number_format($total_user) ?></h3>
                </div>
            </div>
            
            <div class="bg-white p-8 rounded-4xl border border-slate-50 flex items-center gap-5 shadow-sm">
                <div class="w-14 h-14 rounded-2xl bg-[#FFF1EE] flex items-center justify-center text-primary-orange shrink-0">
                    <span class="material-symbols-outlined text-3xl font-bold">store</span>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Penjual</p>
                    <h3 class="text-2xl font-black text-[#003049]"><?= number_format($total_penjual) ?></h3>
                </div>
            </div>

            <div class="bg-white p-8 rounded-4xl border border-slate-50 flex items-center gap-5 shadow-sm">
                <div class="w-14 h-14 rounded-2xl bg-[#EAF7F0] flex items-center justify-center text-accent-green shrink-0">
                    <span class="material-symbols-outlined text-3xl font-bold">shopping_cart</span>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Pesanan Baru</p>
                    <h3 class="text-2xl font-black text-[#003049]"><?= number_format($pesanan_hari_ini) ?></h3>
                </div>
            </div>

            <div class="bg-white p-8 rounded-4xl border border-slate-50 shadow-sm relative overflow-hidden">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total Pendapatan</p>
                <h3 class="text-xl font-black text-[#003049]">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></h3>
                <div class="w-full h-1.5 bg-slate-100 rounded-full mt-4 overflow-hidden">
                    <div class="h-full bg-accent-blue" style="width: <?= $persen_target ?>%"></div>
                </div>
                <p class="text-[8px] font-bold text-accent-blue mt-2 tracking-tighter uppercase"><?= round($persen_target) ?>% CAPAIAN TARGET</p>
            </div>
        </section>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            <div class="xl:col-span-2 bg-white p-8 md:p-10 rounded-4xl shadow-sm border border-slate-50 overflow-hidden">
                <h4 class="text-xl font-extrabold text-[#003049] mb-8">Statistik Penjualan</h4>
                <div class="flex items-end justify-between h-64 gap-2">
                    <?php foreach($grafik_data as $g): $height = ($g['nilai'] / $max_val) * 100; ?>
                    <div class="flex-1 flex flex-col items-center gap-4 group relative">
                        <div class="absolute -top-10 bg-[#003049] text-white text-[9px] px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">Rp<?= number_format($g['nilai']) ?></div>
                        <div class="w-full max-w-[12px] bg-slate-50 rounded-full relative h-48 overflow-hidden">
                            <div class="absolute bottom-0 left-0 w-full bg-primary-orange rounded-full transition-all duration-1000" style="height: <?= $height ?>%;"></div>
                        </div>
                        <span class="text-[10px] font-black <?= $g['is_today'] ? 'text-primary-orange' : 'text-slate-300' ?>"><?= $g['label'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bg-white p-8 md:p-10 rounded-4xl shadow-sm border border-slate-50 flex flex-col relative">
                <h4 class="text-xl font-extrabold text-[#003049] mb-2">Kategori</h4>
                <p class="text-xs text-slate-400 font-medium mb-10">Popularitas produk terjual</p>
                <div class="space-y-8 flex-1">
                    <?php 
                    $colors = ['Makanan' => 'bg-primary-orange', 'Minuman' => 'bg-accent-blue', 'Cemilan' => 'bg-accent-green'];
                    foreach($stats as $kat => $jml): 
                        $persen = ($total_stats > 0) ? ($jml / $total_stats) * 100 : 0;
                    ?>
                    <div class="space-y-3">
                        <div class="flex justify-between text-[10px] font-black uppercase tracking-widest">
                            <span class="text-slate-400"><?= $kat ?></span>
                            <span class="text-[#003049]"><?= round($persen) ?>%</span>
                        </div>
                        <div class="h-2.5 w-full bg-slate-50 rounded-full overflow-hidden">
                            <div class="h-full <?= $colors[$kat] ?? 'bg-slate-300' ?> rounded-full" style="width: <?= $persen ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <a href="manajemen_menu.php" class="mt-10 bg-slate-900 text-white p-4 rounded-2xl flex items-center justify-center gap-3 font-bold text-sm hover:bg-black transition-all">
                    <span class="material-symbols-outlined text-[18px]">open_in_new</span> Detail Menu
                </a>
            </div>
        </div>
    </main>

</body>
</html>