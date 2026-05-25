<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/language_helper.php');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Statistik Pendapatan Admin
$total_pajak = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COALESCE(SUM(jumlah_pajak), 0) as total FROM transaksi"))['total'] ?? 0;
$total_transaksi = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM transaksi"))['total'] ?? 0;
$pajak_bulan_ini = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COALESCE(SUM(jumlah_pajak), 0) as total FROM transaksi WHERE MONTH(tanggal) = MONTH(NOW()) AND YEAR(tanggal) = YEAR(NOW())"))['total'] ?? 0;
$transaksi_bulan_ini = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM transaksi WHERE MONTH(tanggal) = MONTH(NOW()) AND YEAR(tanggal) = YEAR(NOW())"))['total'] ?? 0;

// Grafik 7 hari
$grafik_data = [];
$days_label  = ['Mon'=>'SEN','Tue'=>'SEL','Wed'=>'RAB','Thu'=>'KAM','Fri'=>'JUM','Sat'=>'SAB','Sun'=>'MIN'];
for ($i = 6; $i >= 0; $i--) {
    $date   = date('Y-m-d', strtotime("-$i days"));
    $day_en = date('D', strtotime($date));
    $res_g  = mysqli_query($koneksi, "SELECT COALESCE(SUM(jumlah_pajak), 0) as total FROM transaksi WHERE DATE(tanggal)='$date'");
    $nilai_grafik = 0;
    if ($res_g) {
        $row = mysqli_fetch_assoc($res_g);
        $nilai_grafik = $row['total'] ?? 0;
    }
    $grafik_data[] = ['label'=>$days_label[$day_en], 'nilai'=>$nilai_grafik, 'is_today'=>($i==0)];
}
$max_val = max(array_column($grafik_data, 'nilai'));
if (!$max_val || $max_val <= 0) {
    $max_val = 1;
}

// Filter
$filterTanggal = $_GET['tanggal'] ?? '';
$filterKantin = $_GET['kantin'] ?? '';

$where = [];
if ($filterTanggal) {
    $safe_tanggal = mysqli_real_escape_string($koneksi, $filterTanggal);
    $where[] = "DATE(t.tanggal) = '$safe_tanggal'";
}
if ($filterKantin) {
    $safe_kantin = mysqli_real_escape_string($koneksi, $filterKantin);
    $where[] = "p.id_kantin = '$safe_kantin'";
}

$where_sql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

// Detail transaksi
$query_detail = mysqli_query($koneksi, "
    SELECT t.id_transaksi, t.tanggal, t.jumlah_pajak, t.metode_pembayaran,
           p.id_pesanan, p.total_harga, u.username, k.nama_kantin
    FROM transaksi t
    LEFT JOIN pesanan p ON t.id_pesanan = p.id_pesanan
    LEFT JOIN users u ON p.id_user = u.id_user
    LEFT JOIN kantin k ON p.id_kantin = k.id_kantin
    $where_sql
    ORDER BY t.tanggal DESC
    LIMIT 500
");

// Daftar kantin untuk filter
$daftar_kantin = mysqli_query($koneksi, "SELECT id_kantin, nama_kantin FROM kantin ORDER BY nama_kantin");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/><meta content="width=device-width,initial-scale=1.0" name="viewport"/>
    <title>Laporan Pendapatan Admin - Kantin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    <script>
        tailwind.config = { theme: { extend: { colors: { 'primary-orange':'#E25E3E','accent-orange':'#fb8500','neon-orange':'#ffb703' } } } }
    </script>
    <style>
        body { font-family:'Plus Jakarta Sans',sans-serif; background: linear-gradient(135deg, #fff7f1 0%, #fff2e7 100%); }
        ::-webkit-scrollbar{width:6px;height:6px} ::-webkit-scrollbar-thumb{background:#FF8C20;border-radius:10px}
        .glow-card{box-shadow:0 25px 80px rgba(226,94,62,0.12);}
        table { width: 100%; }
        thead { background: #f3f4f6; }
        tbody tr { border-bottom: 1px solid #e5e7eb; }
        tbody tr:hover { background: #faf9f8; }
    </style>
</head>
<body class="text-slate-800">

<?php include '../../includes/sidebar_admin.php'; ?>

<main class="flex-1 w-full lg:ml-72 p-6 md:p-10">
    <header class="mb-10 mt-14 lg:mt-0">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center text-white shadow-lg">
                <span class="material-symbols-outlined text-2xl">attach_money</span>
            </div>
            <div>
                <h2 class="text-3xl md:text-4xl font-extrabold text-[#2a2a2a] tracking-tight">Pendapatan Admin</h2>
                <p class="text-orange-700 font-semibold mt-1">Laporan pendapatan dari pajak transaksi</p>
            </div>
        </div>
    </header>

    <!-- Statistik Cards -->
    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-5 rounded-xl border border-orange-100 shadow-glow-card">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[9px] font-black text-orange-500 uppercase tracking-widest">Total Pendapatan</span>
                <span class="material-symbols-outlined text-lg text-orange-500">trending_up</span>
            </div>
            <h3 class="text-2xl font-extrabold text-[#2a2a2a]">Rp <?= number_format($total_pajak, 0, ',', '.') ?></h3>
            <p class="text-[11px] text-slate-400 mt-1"><?= number_format($total_transaksi) ?> transaksi</p>
        </div>

        <div class="bg-white p-5 rounded-xl border border-orange-100 shadow-glow-card">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[9px] font-black text-orange-500 uppercase tracking-widest">Bulan Ini</span>
                <span class="material-symbols-outlined text-lg text-orange-500">calendar_month</span>
            </div>
            <h3 class="text-2xl font-extrabold text-[#2a2a2a]">Rp <?= number_format($pajak_bulan_ini, 0, ',', '.') ?></h3>
            <p class="text-[11px] text-slate-400 mt-1"><?= number_format($transaksi_bulan_ini) ?> transaksi</p>
        </div>

        <div class="bg-white p-5 rounded-xl border border-orange-100 shadow-glow-card">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[9px] font-black text-orange-500 uppercase tracking-widest">Rata-rata Pajak</span>
                <span class="material-symbols-outlined text-lg text-orange-500">average</span>
            </div>
            <h3 class="text-2xl font-extrabold text-[#2a2a2a]">Rp <?= number_format($total_transaksi > 0 ? $total_pajak / $total_transaksi : 0, 0, ',', '.') ?></h3>
            <p class="text-[11px] text-slate-400 mt-1">per transaksi</p>
        </div>

        <div class="bg-gradient-to-br from-yellow-50 to-orange-50 p-5 rounded-xl border border-orange-200 shadow-glow-card">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[9px] font-black text-orange-600 uppercase tracking-widest">Target Bulan</span>
                <span class="material-symbols-outlined text-lg text-orange-600">flag</span>
            </div>
            <h3 class="text-xl font-extrabold text-orange-700">Rp 50.000.000</h3>
            <div class="w-full bg-orange-100 rounded-full h-1.5 mt-1.5">
                <div class="bg-gradient-to-r from-yellow-400 to-orange-500 h-1.5 rounded-full" style="width: <?= min(($pajak_bulan_ini / 50000000) * 100, 100) ?>%"></div>
            </div>
            <p class="text-[10px] text-slate-500 mt-1"><?= number_format(min(($pajak_bulan_ini / 50000000) * 100, 100), 1) ?>% dari target</p>
        </div>
    </section>

    <!-- Grafik 7 Hari Terakhir -->
    <div class="bg-white p-6 rounded-xl border border-orange-100 shadow-glow-card mb-8">
        <h3 class="text-base font-extrabold text-[#2a2a2a] mb-4">Pendapatan 7 Hari Terakhir</h3>
        <div class="flex items-end justify-between h-40 gap-1.5">
            <?php foreach($grafik_data as $g): $height = ($g['nilai'] / $max_val) * 100; ?>
            <div class="flex-1 flex flex-col items-center gap-4 group relative">
                <div class="absolute -top-10 bg-[#2a2a2a] text-white text-[9px] px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">Rp<?= number_format($g['nilai']) ?></div>
                <div class="w-full max-w-[14px] bg-slate-100 rounded-full relative h-48 overflow-hidden">
                    <div class="absolute bottom-0 left-0 w-full bg-gradient-to-t from-orange-500 to-orange-400 rounded-full transition-all duration-1000" style="height:<?= $height ?>%"></div>
                </div>
                <span class="text-[10px] font-black <?= $g['is_today'] ? 'text-primary-orange font-bold' : 'text-slate-400' ?>"><?= $g['label'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Filter -->
    <div class="bg-white p-5 rounded-xl border border-orange-100 mb-6">
        <form method="GET" class="flex flex-wrap gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-2">Tanggal</label>
                <input type="date" name="tanggal" value="<?= htmlspecialchars($filterTanggal) ?>" class="px-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-400">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-2">Kantin</label>
                <select name="kantin" class="px-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-400">
                    <option value="">Semua Kantin</option>
                    <?php while ($kantin = mysqli_fetch_assoc($daftar_kantin)): ?>
                        <option value="<?= $kantin['id_kantin'] ?>" <?= $filterKantin == $kantin['id_kantin'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($kantin['nama_kantin']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="flex gap-2 items-end">
                <button type="submit" class="bg-primary-orange text-white px-5 py-2 rounded-lg font-bold text-sm hover:bg-orange-600 transition-all">
                    <span class="material-symbols-outlined inline mr-1 text-sm">search</span>Filter
                </button>
                <a href="laporan_pendapatan_admin.php" class="bg-slate-200 text-slate-700 px-5 py-2 rounded-lg font-bold text-sm hover:bg-slate-300 transition-all">
                    <span class="material-symbols-outlined inline mr-1 text-sm">refresh</span>Reset
                </a>
                <button type="button" onclick="window.print()" class="bg-slate-600 text-white px-5 py-2 rounded-lg font-bold text-sm hover:bg-slate-700 transition-all">
                    <span class="material-symbols-outlined inline mr-1 text-sm">print</span>Cetak
                </button>
            </div>
        </form>
    </div>

    <!-- Tabel Detail -->
    <div class="bg-white rounded-xl border border-orange-100 shadow-glow-card overflow-hidden">
        <div class="p-4 border-b border-orange-100 bg-gradient-to-r from-orange-50 to-yellow-50">
            <h3 class="text-base font-extrabold text-[#2a2a2a]">Detail Pajak Transaksi</h3>
            <p class="text-[10px] text-slate-500 mt-0.5">Rincian pajak dari setiap transaksi yang berhasil</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-4 py-2 text-left font-bold text-slate-700">ID Transaksi</th>
                        <th class="px-4 py-2 text-left font-bold text-slate-700">Tanggal</th>
                        <th class="px-4 py-2 text-left font-bold text-slate-700">Pembeli</th>
                        <th class="px-4 py-2 text-left font-bold text-slate-700">Kantin</th>
                        <th class="px-4 py-2 text-right font-bold text-slate-700">Total Pesanan</th>
                        <th class="px-4 py-2 text-right font-bold text-slate-700">Biaya Layanan (500)</th>
                        <th class="px-4 py-2 text-left font-bold text-slate-700">Metode</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$query_detail || mysqli_num_rows($query_detail) === 0): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-4 text-center text-slate-500">
                            <span class="material-symbols-outlined text-2xl inline mb-1">inbox</span>
                            <p class="font-bold">Tidak ada data transaksi</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php while ($detail = mysqli_fetch_assoc($query_detail)): ?>
                        <tr class="border-b border-slate-100 hover:bg-orange-50 transition-colors">
                            <td class="px-6 py-4 font-bold text-orange-600">#<?= htmlspecialchars($detail['id_transaksi']) ?></td>
                            <td class="px-6 py-4 text-slate-700"><?= date('d M Y H:i', strtotime($detail['tanggal'])) ?></td>
                            <td class="px-6 py-4 text-slate-700"><?= htmlspecialchars($detail['username'] ?? 'Guest') ?></td>
                            <td class="px-6 py-4 text-slate-700"><?= htmlspecialchars($detail['nama_kantin'] ?? '-') ?></td>
                            <td class="px-6 py-4 text-right font-bold text-slate-900">Rp <?= number_format($detail['total_harga'] ?? 0, 0, ',', '.') ?></td>
                            <td class="px-6 py-4 text-right">
                                <span class="px-3 py-1 rounded-full text-sm font-bold text-white bg-gradient-to-r from-yellow-400 to-orange-500">
                                    Rp <?= number_format($detail['jumlah_pajak'] ?? 500, 0, ',', '.') ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700">
                                    <?= htmlspecialchars($detail['metode_pembayaran'] ?? 'Cash') ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Format mata uang pada hover
        document.querySelectorAll('[data-currency]').forEach(el => {
            el.addEventListener('mouseover', function() {
                this.style.fontWeight = 'bold';
            });
        });
    });
</script>
</body>
</html>
