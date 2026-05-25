<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/language_helper.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');
kk_ensure_buyer_schema($koneksi);

// Initialize message variables to avoid "Undefined variable" warnings
$message = '';
$message_type = '';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php"); exit();
}

// Statistik
$result_pembeli   = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role='pembeli'");
$jumlah_pembeli   = $result_pembeli ? (mysqli_fetch_assoc($result_pembeli)['total'] ?? 0) : 0;

$result_penjual   = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role='penjual'");
$jumlah_penjual   = $result_penjual ? (mysqli_fetch_assoc($result_penjual)['total'] ?? 0) : 0;

$result_kantin    = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kantin");
$jumlah_kantin    = $result_kantin ? (mysqli_fetch_assoc($result_kantin)['total'] ?? 0) : 0;

$result_menu      = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM menu");
$jumlah_menu      = $result_menu ? (mysqli_fetch_assoc($result_menu)['total'] ?? 0) : 0;

$result_transaksi = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM transaksi");
$jumlah_transaksi = $result_transaksi ? (mysqli_fetch_assoc($result_transaksi)['total'] ?? 0) : 0;

// Pendapatan Admin (dari pajak)
$result_pajak     = mysqli_query($koneksi, "SELECT COALESCE(SUM(jumlah_pajak), 0) as total FROM transaksi");
$pendapatan_admin = $result_pajak ? (mysqli_fetch_assoc($result_pajak)['total'] ?? 0) : 0;

// Grafik 7 hari
$grafik_data = [];
$days_label  = ['Mon'=>'SEN','Tue'=>'SEL','Wed'=>'RAB','Thu'=>'KAM','Fri'=>'JUM','Sat'=>'SAB','Sun'=>'MIN'];
for ($i = 6; $i >= 0; $i--) {
    $date   = date('Y-m-d', strtotime("-$i days"));
    $day_en = date('D', strtotime($date));
    $res_g  = mysqli_query($koneksi, "SELECT SUM(total_harga) as total FROM pesanan WHERE DATE(tanggal)='$date' AND status='Selesai'");
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/><meta content="width=device-width,initial-scale=1.0" name="viewport"/>
    <title><?= t('admin.dashboard_title') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    <script>
        tailwind.config = { theme: { extend: { colors: { 'bg-soft':'#FFF4EB','primary-orange':'#E25E3E','primary-orange-dark':'#C2410C','accent-orange':'#fb8500','neon-orange':'#ffb703' }, borderRadius:{'4xl':'2.5rem'} } } }
    </script>
    <style>
        body { font-family:'Plus Jakarta Sans',sans-serif; background: radial-gradient(circle at top left, rgba(251,146,60,.20), transparent 32%), radial-gradient(circle at 80% 20%, rgba(255,183,3,.12), transparent 25%), linear-gradient(180deg,#fff7f1 0%,#fff2e7 38%,#fff9f3 100%); }
        ::-webkit-scrollbar{width:6px;height:6px} ::-webkit-scrollbar-thumb{background:#FF8C20;border-radius:10px}
        .glow-card{box-shadow:0 25px 80px rgba(251,146,60,0.16);}
        .badge-genz{background:linear-gradient(135deg,#ffb703,#fb8500);color:#fff;}
        .box-fade{animation:fadein .25s ease-out forwards}
        @keyframes fadein{from{opacity:0;transform:scale(.97) translateY(10px)}to{opacity:1;transform:scale(1) translateY(0)}}
        .modal-anim{animation:fadein .2s ease-out forwards}
        
        /* Responsive Mobile */
        @media (max-width: 768px) {
            main { padding: 1rem !important; }
            .grid { gap: 1rem !important; }
        }
        
        @media (max-width: 640px) {
            header { margin-top: 3.5rem !important; }
            .text-3xl { font-size: 1.875rem; }
            .text-4xl { font-size: 2.25rem; }
            .p-8, .p-10 { padding: 1.5rem !important; }
            table { font-size: 0.75rem; }
        }
        
        /* Table scrolling on mobile */
        .overflow-x-auto { -webkit-overflow-scrolling: touch; }
    </style>
</head>
<body class="bg-bg-soft text-slate-800 flex">

<?php include '../../includes/sidebar_admin.php'; ?>

<main class="flex-1 w-full lg:ml-72 p-4 md:p-6 lg:p-10">
    <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 sm:gap-6 mb-8 sm:mb-10 mt-14 lg:mt-0">
        <div>
            <h2 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-[#2a2a2a] tracking-tight"><?= t('admin.dashboard_overview') ?></h2>
            <p class="text-orange-700 font-semibold text-sm sm:text-base mt-2"><?= t('admin.dashboard_subtitle') ?></p>
        </div>
        <div class="flex flex-wrap items-center gap-3 sm:gap-4 w-full sm:w-auto">
            <div class="bg-white px-4 sm:px-5 py-2 sm:py-3 rounded-2xl border border-orange-100 flex items-center gap-2 sm:gap-3 shadow-glow-card text-xs sm:text-sm font-bold text-orange-700">
                <span class="material-symbols-outlined text-orange-500 text-sm sm:text-lg">calendar_today</span>
                <span class="line-clamp-1"><?= date('M d, Y') ?></span>
            </div>
        </div>
    </header>

    <!-- Statistik Cards -->
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4 lg:gap-6 mb-8 sm:mb-10">
        <a href="manajemen_kantin.php" class="bg-white/90 backdrop-blur-xl p-4 sm:p-6 lg:p-8 rounded-2xl lg:rounded-[2rem] border border-orange-100 flex items-center gap-3 sm:gap-5 shadow-glow-card hover:shadow-xl hover:scale-105 transition-all cursor-pointer">
            <div class="w-12 sm:w-14 lg:w-16 h-12 sm:h-14 lg:h-16 rounded-lg lg:rounded-[24px] bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white shrink-0 shadow-xl shadow-orange-300/40"><span class="material-symbols-outlined text-2xl lg:text-3xl">storefront</span></div>
            <div>
                <p class="text-[9px] sm:text-[10px] font-black text-orange-500 uppercase tracking-widest">Jumlah Kantin</p>
                <h3 class="text-lg sm:text-xl lg:text-2xl font-extrabold text-[#2a2a2a] mt-1"><?= number_format($jumlah_kantin) ?></h3>
            </div>
        </a>
        <a href="manajemen_penjual.php" class="bg-white/90 backdrop-blur-xl p-4 sm:p-6 lg:p-8 rounded-2xl lg:rounded-[2rem] border border-orange-100 flex items-center gap-3 sm:gap-5 shadow-glow-card hover:shadow-xl hover:scale-105 transition-all cursor-pointer">
            <div class="w-12 sm:w-14 lg:w-16 h-12 sm:h-14 lg:h-16 rounded-lg lg:rounded-[24px] bg-gradient-to-br from-orange-300 to-orange-500 flex items-center justify-center text-white shrink-0 shadow-xl shadow-orange-300/40"><span class="material-symbols-outlined text-2xl lg:text-3xl">store</span></div>
            <div>
                <p class="text-[9px] sm:text-[10px] font-black text-orange-500 uppercase tracking-widest">Jumlah Penjual</p>
                <h3 class="text-lg sm:text-xl lg:text-2xl font-extrabold text-[#2a2a2a] mt-1"><?= number_format($jumlah_penjual) ?></h3>
            </div>
        </a>
        <a href="manajemen_user.php" class="bg-white/90 backdrop-blur-xl p-4 sm:p-6 lg:p-8 rounded-2xl lg:rounded-[2rem] border border-orange-100 flex items-center gap-3 sm:gap-5 shadow-glow-card hover:shadow-xl hover:scale-105 transition-all cursor-pointer">
            <div class="w-12 sm:w-14 lg:w-16 h-12 sm:h-14 lg:h-16 rounded-lg lg:rounded-[24px] bg-gradient-to-br from-orange-200 to-orange-500 flex items-center justify-center text-orange-900 shrink-0 shadow-xl shadow-orange-300/40"><span class="material-symbols-outlined text-2xl lg:text-3xl">group</span></div>
            <div>
                <p class="text-[9px] sm:text-[10px] font-black text-orange-500 uppercase tracking-widest">Jumlah Pembeli</p>
                <h3 class="text-lg sm:text-xl lg:text-2xl font-extrabold text-[#2a2a2a] mt-1"><?= number_format($jumlah_pembeli) ?></h3>
            </div>
        </a>
        <a href="manajemen_menu.php" class="bg-white/90 backdrop-blur-xl p-4 sm:p-6 lg:p-8 rounded-2xl lg:rounded-[2rem] border border-orange-100 shadow-glow-card relative overflow-hidden hover:shadow-xl hover:scale-105 transition-all cursor-pointer">
            <div class="absolute -top-10 -right-6 w-24 h-24 rounded-full bg-orange-100/70 blur-2xl"></div>
            <p class="text-[9px] sm:text-[10px] font-black text-orange-500 uppercase tracking-widest mb-1">Jumlah Menu</p>
            <h3 class="text-lg sm:text-xl lg:text-2xl font-extrabold text-[#2a2a2a]"><?= number_format($jumlah_menu) ?></h3>
        </a>
        <a href="laporan_transaksi.php" class="bg-white/90 backdrop-blur-xl p-4 sm:p-6 lg:p-8 rounded-2xl lg:rounded-[2rem] border border-orange-100 flex items-center gap-3 sm:gap-5 shadow-glow-card hover:shadow-xl hover:scale-105 transition-all cursor-pointer">
            <div class="w-12 sm:w-14 lg:w-16 h-12 sm:h-14 lg:h-16 rounded-lg lg:rounded-[24px] bg-gradient-to-br from-orange-200 to-orange-400 flex items-center justify-center text-orange-900 shrink-0 shadow-xl shadow-orange-300/40"><span class="material-symbols-outlined text-2xl lg:text-3xl">receipt_long</span></div>
            <div>
                <p class="text-[9px] sm:text-[10px] font-black text-orange-500 uppercase tracking-widest">Jumlah Transaksi</p>
                <h3 class="text-base sm:text-lg lg:text-xl font-extrabold text-[#2a2a2a] mt-1"><?= number_format($jumlah_transaksi) ?></h3>
            </div>
        </a>
        <a href="laporan_penjualan.php" class="bg-white/90 backdrop-blur-xl p-4 sm:p-6 lg:p-8 rounded-2xl lg:rounded-[2rem] border border-orange-100 flex items-center gap-3 sm:gap-5 shadow-glow-card hover:shadow-xl hover:scale-105 transition-all cursor-pointer">
            <div class="w-12 sm:w-14 lg:w-16 h-12 sm:h-14 lg:h-16 rounded-lg lg:rounded-[24px] bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center text-white shrink-0 shadow-xl shadow-orange-300/40"><span class="material-symbols-outlined text-2xl lg:text-3xl">attach_money</span></div>
            <div>
                <p class="text-[9px] sm:text-[10px] font-black text-orange-500 uppercase tracking-widest">Pendapatan Admin</p>
                <h3 class="text-base sm:text-lg lg:text-xl font-extrabold text-[#2a2a2a] mt-1">Rp <?= number_format($pendapatan_admin, 0, ',', '.') ?></h3>
            </div>
        </a>
    </section>

    <!-- Grafik Penjualan -->
    <div class="grid grid-cols-1 gap-6 sm:gap-8">
        <div class="bg-white p-4 sm:p-6 md:p-10 rounded-2xl lg:rounded-4xl shadow-sm border border-slate-50">
            <h4 class="text-base sm:text-lg md:text-xl font-extrabold text-[#003049] mb-6 sm:mb-8">Grafik Penjualan 7 Hari Terakhir</h4>
            <div class="w-full overflow-x-auto">
                <div class="flex items-end justify-between h-40 sm:h-48 md:h-64 gap-1.5 sm:gap-2 md:gap-3 min-w-max py-4">
                    <?php foreach($grafik_data as $g): $height = ($g['nilai'] / $max_val) * 100; ?>
                    <div class="flex-1 min-w-[30px] sm:min-w-[40px] md:min-w-[60px] flex flex-col items-center gap-2 sm:gap-4 group relative">
                        <div class="absolute -top-8 sm:-top-10 bg-[#003049] text-white text-[8px] sm:text-[9px] px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">Rp<?= number_format($g['nilai']) ?></div>
                        <div class="w-full max-w-[8px] sm:max-w-[10px] md:max-w-[12px] bg-slate-50 rounded-full relative h-32 sm:h-40 md:h-48 overflow-hidden">
                            <div class="absolute bottom-0 left-0 w-full bg-gradient-to-t from-primary-orange to-orange-400 rounded-full transition-all duration-1000" style="height:<?= $height ?>%"></div>
                        </div>
                        <span class="text-[8px] sm:text-[9px] md:text-[10px] font-black <?= $g['is_today'] ? 'text-primary-orange' : 'text-slate-300' ?>"><?= $g['label'] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Statistik Lengkap -->
    <div class="grid grid-cols-1 gap-6 sm:gap-8 mb-8 sm:mb-10">
        <div class="bg-white rounded-2xl lg:rounded-4xl shadow-sm border border-slate-50 overflow-hidden">
            <div class="p-4 sm:p-6 md:p-8 border-b border-slate-50 bg-gradient-to-r from-orange-50 to-orange-100">
                <h4 class="text-base sm:text-lg md:text-xl font-extrabold text-[#003049] flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg sm:text-2xl">table_chart</span>
                    <span class="line-clamp-1">Ringkasan Statistik Sistem</span>
                </h4>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Data komprehensif kesehatan platform</p>
            </div>
            <div class="overflow-x-auto -mx-4 sm:mx-0">
                <div class="min-w-full px-4 sm:px-0">
                    <table class="w-full text-xs sm:text-sm md:text-base">
                        <thead>
                            <tr class="bg-gradient-to-r from-slate-50 to-slate-100 border-b border-slate-200">
                                <th class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 text-left font-bold text-slate-700 whitespace-nowrap">📊 Metrik</th>
                                <th class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 text-center font-bold text-slate-700 whitespace-nowrap">Total</th>
                                <th class="hidden sm:table-cell px-3 sm:px-4 md:px-6 py-3 sm:py-4 text-center font-bold text-slate-700 whitespace-nowrap">Status</th>
                                <th class="hidden md:table-cell px-3 sm:px-4 md:px-6 py-3 sm:py-4 text-center font-bold text-slate-700 whitespace-nowrap">Trend</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr class="hover:bg-slate-50 transition-all">
                                <td class="px-3 sm:px-4 md:px-6 py-2 sm:py-4 font-semibold text-slate-800 whitespace-nowrap">🏪 Kantin Aktif</td>
                                <td class="px-3 sm:px-4 md:px-6 py-2 sm:py-4 text-center">
                                    <span class="text-lg sm:text-xl md:text-2xl font-bold text-orange-600"><?= number_format($jumlah_kantin) ?></span>
                                </td>
                                <td class="hidden sm:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-4 text-center">
                                    <span class="inline-block px-2 sm:px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold whitespace-nowrap">✓ Aktif</span>
                                </td>
                                <td class="hidden md:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-4 text-center">
                                    <span class="text-green-600 font-semibold">↑ Stabil</span>
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50 transition-all">
                                <td class="px-3 sm:px-4 md:px-6 py-2 sm:py-4 font-semibold text-slate-800 whitespace-nowrap">👨‍💼 Penjual</td>
                                <td class="px-3 sm:px-4 md:px-6 py-2 sm:py-4 text-center">
                                    <span class="text-lg sm:text-xl md:text-2xl font-bold text-blue-600"><?= number_format($jumlah_penjual) ?></span>
                                </td>
                                <td class="hidden sm:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-4 text-center">
                                    <span class="inline-block px-2 sm:px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold whitespace-nowrap">✓ Terdaftar</span>
                                </td>
                                <td class="hidden md:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-4 text-center">
                                    <span class="text-blue-600 font-semibold">↑ Meningkat</span>
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50 transition-all">
                                <td class="px-3 sm:px-4 md:px-6 py-2 sm:py-4 font-semibold text-slate-800 whitespace-nowrap">👥 Pembeli</td>
                                <td class="px-3 sm:px-4 md:px-6 py-2 sm:py-4 text-center">
                                    <span class="text-lg sm:text-xl md:text-2xl font-bold text-indigo-600"><?= number_format($jumlah_pembeli) ?></span>
                                </td>
                                <td class="hidden sm:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-4 text-center">
                                    <span class="inline-block px-2 sm:px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-xs font-semibold whitespace-nowrap">✓ Aktif</span>
                                </td>
                                <td class="hidden md:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-4 text-center">
                                    <span class="text-indigo-600 font-semibold">↑ Tinggi</span>
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50 transition-all">
                                <td class="px-3 sm:px-4 md:px-6 py-2 sm:py-4 font-semibold text-slate-800 whitespace-nowrap">🍽️ Menu</td>
                                <td class="px-3 sm:px-4 md:px-6 py-2 sm:py-4 text-center">
                                    <span class="text-lg sm:text-xl md:text-2xl font-bold text-emerald-600"><?= number_format($jumlah_menu) ?></span>
                                </td>
                                <td class="hidden sm:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-4 text-center">
                                    <span class="inline-block px-2 sm:px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-semibold whitespace-nowrap">✓ Update</span>
                                </td>
                                <td class="hidden md:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-4 text-center">
                                    <span class="text-emerald-600 font-semibold">↑ Beragam</span>
                                </td>
                            </tr>
                            <tr class="hover:bg-slate-50 transition-all">
                                <td class="px-3 sm:px-4 md:px-6 py-2 sm:py-4 font-semibold text-slate-800 whitespace-nowrap">💳 Transaksi</td>
                                <td class="px-3 sm:px-4 md:px-6 py-2 sm:py-4 text-center">
                                    <span class="text-lg sm:text-xl md:text-2xl font-bold text-purple-600"><?= number_format($jumlah_transaksi) ?></span>
                                </td>
                                <td class="hidden sm:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-4 text-center">
                                    <span class="inline-block px-2 sm:px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-semibold whitespace-nowrap">✓ Berjalan</span>
                                </td>
                                <td class="hidden md:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-4 text-center">
                                    <span class="text-purple-600 font-semibold">↑ Dinamis</span>
                                </td>
                            </tr>
                            <tr class="bg-gradient-to-r from-yellow-50 to-orange-50 hover:from-yellow-100 hover:to-orange-100 transition-all">
                                <td class="px-3 sm:px-4 md:px-6 py-2 sm:py-4 font-bold text-slate-800 flex items-center gap-2 whitespace-nowrap">
                                    💰 Pendapatan
                                </td>
                                <td class="px-3 sm:px-4 md:px-6 py-2 sm:py-4 text-center">
                                    <span class="text-base sm:text-lg md:text-2xl font-extrabold text-orange-600 block">Rp<?= number_format($pendapatan_admin, 0, ',', '.') ?></span>
                                </td>
                                <td class="hidden sm:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-4 text-center">
                                    <span class="inline-block px-2 sm:px-3 py-1 bg-orange-200 text-orange-800 rounded-full text-xs font-semibold whitespace-nowrap">💵 Masuk</span>
                                </td>
                                <td class="hidden md:table-cell px-3 sm:px-4 md:px-6 py-2 sm:py-4 text-center">
                                    <span class="text-orange-600 font-bold">📈 Positif</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</main>

</body>
</html>
