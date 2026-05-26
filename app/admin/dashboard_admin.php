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
        body { font-family:'Plus Jakarta Sans',sans-serif; background: radial-gradient(circle at top left, rgba(251,146,60,.20), transparent 32%), radial-gradient(circle at 80% 20%, rgba(255,183,3,.12), transparent 25%), linear-gradient(180deg,#fff7f1 0%,#fff2e7 38%,#fff9f3 100%); margin: 0; padding: 0; }
        ::-webkit-scrollbar{width:6px;height:6px} ::-webkit-scrollbar-thumb{background:#FF8C20;border-radius:10px}
        .glow-card{box-shadow:0 25px 80px rgba(251,146,60,0.16);}
        .box-fade{animation:fadein .25s ease-out forwards}
        @keyframes fadein{from{opacity:0;transform:scale(.97) translateY(10px)}to{opacity:1;transform:scale(1) translateY(0)}}
        .modal-anim{animation:fadein .2s ease-out forwards}
        
        /* Sidebar responsive */
        #sidebar { transform: translateX(-100%) !important; transition: transform 0.3s ease-in-out !important; width: 18rem !important; flex-shrink: 0; }
        #overlay { display: none !important; }
        @media (min-width: 1024px) {
            #sidebar { transform: translateX(0) !important; width: 18rem !important; }
            #overlay { display: none !important; }
            main { margin-left: 18rem !important; width: calc(100% - 18rem) !important; }
            body { display: flex; }
        }
        #sidebar.active { transform: translateX(0) !important; }
        #overlay.active { display: block !important; }
        
        /* Responsive Mobile */
        @media (max-width: 1023px) {
            main { margin-left: 0 !important; width: 100% !important; }
            body { overflow-x: hidden; }
            #sidebar { position: fixed; left: 0; top: 0; }
        }
        
        @media (max-width: 768px) {
            main { padding: 1rem !important; }
            .grid { gap: 1rem !important; }
            header { flex-direction: column !important; }
        }
        
        @media (max-width: 640px) {
            main { padding: 0.75rem !important; margin-top: 3.5rem !important; }
            header { margin-top: 4rem !important; }
            .text-3xl { font-size: 1.875rem; }
            .text-4xl { font-size: 2.25rem; }
            .p-8, .p-10 { padding: 1.5rem !important; }
            table { font-size: 0.75rem; }
            .grid-cols-1 { gap: 0.75rem !important; }
        }
        
        /* Table scrolling on mobile */
        .overflow-x-auto { -webkit-overflow-scrolling: touch; }
    </style>
    <script>
        // Initialize sidebar state based on viewport
        function initSidebar() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth >= 1024) {
                sidebar.classList.remove('active');
                sidebar.style.transform = 'translateX(0)';
            } else {
                sidebar.classList.remove('active');
                sidebar.style.transform = 'translateX(-100%)';
            }
        }
        
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('hidden');
            if (sidebar.classList.contains('active')) {
                sidebar.style.transform = 'translateX(0)';
            } else {
                sidebar.style.transform = 'translateX(-100%)';
            }
        }
        
        // Close sidebar on link click
        document.addEventListener('DOMContentLoaded', function() {
            initSidebar();
            const navLinks = document.querySelectorAll('#sidebar a');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth < 1024) {
                        toggleSidebar();
                    }
                });
            });
        });
        
        // Handle window resize
        window.addEventListener('resize', initSidebar);
    </script>
</head>
<body class="bg-bg-soft text-slate-800 flex overflow-x-hidden">

<?php include '../../includes/sidebar_admin.php'; ?>

<main class="w-full lg:w-auto p-4 md:p-6 lg:p-8 overflow-x-hidden transition-all duration-300">
    <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-10 mt-14 lg:mt-0">
        <div>
            <h2 class="text-3xl md:text-4xl font-extrabold text-[#2a2a2a] tracking-tight"><?= t('admin.dashboard_overview') ?></h2>
            <p class="text-orange-700 font-semibold mt-2"><?= t('admin.dashboard_subtitle') ?></p>
        </div>
        <div class="flex flex-wrap items-center gap-3 sm:gap-4 w-full sm:w-auto">
            <div class="bg-white px-4 sm:px-5 py-2 sm:py-3 rounded-2xl border border-orange-100 flex items-center gap-2 sm:gap-3 shadow-glow-card text-xs sm:text-sm font-bold text-orange-700">
                <span class="material-symbols-outlined text-orange-500 text-sm sm:text-lg">calendar_today</span>
                <span class="line-clamp-1"><?= date('M d, Y') ?></span>
            </div>
        </div>
    </header>

    <!-- Statistik Cards -->
    <section class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-2 sm:gap-3 lg:gap-4 mb-6 sm:mb-8">
        <a href="manajemen_kantin.php" class="bg-white/90 backdrop-blur-xl p-3 sm:p-4 rounded-xl lg:rounded-2xl border border-orange-100 flex flex-col items-center gap-2 shadow-sm hover:shadow-md hover:scale-105 transition-all cursor-pointer group">
            <div class="w-10 sm:w-12 h-10 sm:h-12 rounded-lg bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white group-hover:shadow-lg transition-all">
                <span class="material-symbols-outlined text-lg sm:text-xl">storefront</span>
            </div>
            <div class="text-center">
                <p class="text-[8px] sm:text-[9px] font-black text-orange-500 uppercase tracking-wider leading-tight">Kantin</p>
                <h3 class="text-base sm:text-lg lg:text-xl font-extrabold text-[#2a2a2a] mt-0.5"><?= number_format($jumlah_kantin) ?></h3>
            </div>
        </a>
        <a href="manajemen_penjual.php" class="bg-white/90 backdrop-blur-xl p-3 sm:p-4 rounded-xl lg:rounded-2xl border border-orange-100 flex flex-col items-center gap-2 shadow-sm hover:shadow-md hover:scale-105 transition-all cursor-pointer group">
            <div class="w-10 sm:w-12 h-10 sm:h-12 rounded-lg bg-gradient-to-br from-orange-300 to-orange-500 flex items-center justify-center text-white group-hover:shadow-lg transition-all">
                <span class="material-symbols-outlined text-lg sm:text-xl">store</span>
            </div>
            <div class="text-center">
                <p class="text-[8px] sm:text-[9px] font-black text-orange-500 uppercase tracking-wider leading-tight">Penjual</p>
                <h3 class="text-base sm:text-lg lg:text-xl font-extrabold text-[#2a2a2a] mt-0.5"><?= number_format($jumlah_penjual) ?></h3>
            </div>
        </a>
        <a href="manajemen_user.php" class="bg-white/90 backdrop-blur-xl p-3 sm:p-4 rounded-xl lg:rounded-2xl border border-orange-100 flex flex-col items-center gap-2 shadow-sm hover:shadow-md hover:scale-105 transition-all cursor-pointer group">
            <div class="w-10 sm:w-12 h-10 sm:h-12 rounded-lg bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white group-hover:shadow-lg transition-all">
                <span class="material-symbols-outlined text-lg sm:text-xl">group</span>
            </div>
            <div class="text-center">
                <p class="text-[8px] sm:text-[9px] font-black text-blue-500 uppercase tracking-wider leading-tight">Pembeli</p>
                <h3 class="text-base sm:text-lg lg:text-xl font-extrabold text-[#2a2a2a] mt-0.5"><?= number_format($jumlah_pembeli) ?></h3>
            </div>
        </a>
        <a href="manajemen_menu.php" class="bg-white/90 backdrop-blur-xl p-3 sm:p-4 rounded-xl lg:rounded-2xl border border-orange-100 flex flex-col items-center gap-2 shadow-sm hover:shadow-md hover:scale-105 transition-all cursor-pointer group">
            <div class="w-10 sm:w-12 h-10 sm:h-12 rounded-lg bg-gradient-to-br from-yellow-400 to-amber-500 flex items-center justify-center text-white group-hover:shadow-lg transition-all">
                <span class="material-symbols-outlined text-lg sm:text-xl">restaurant_menu</span>
            </div>
            <div class="text-center">
                <p class="text-[8px] sm:text-[9px] font-black text-amber-500 uppercase tracking-wider leading-tight">Menu</p>
                <h3 class="text-base sm:text-lg lg:text-xl font-extrabold text-[#2a2a2a] mt-0.5"><?= number_format($jumlah_menu) ?></h3>
            </div>
        </a>
        <a href="laporan_transaksi.php" class="bg-white/90 backdrop-blur-xl p-3 sm:p-4 rounded-xl lg:rounded-2xl border border-orange-100 flex flex-col items-center gap-2 shadow-sm hover:shadow-md hover:scale-105 transition-all cursor-pointer group">
            <div class="w-10 sm:w-12 h-10 sm:h-12 rounded-lg bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center text-white group-hover:shadow-lg transition-all">
                <span class="material-symbols-outlined text-lg sm:text-xl">receipt_long</span>
            </div>
            <div class="text-center">
                <p class="text-[8px] sm:text-[9px] font-black text-emerald-500 uppercase tracking-wider leading-tight">Transaksi</p>
                <h3 class="text-base sm:text-lg lg:text-xl font-extrabold text-[#2a2a2a] mt-0.5"><?= number_format($jumlah_transaksi) ?></h3>
            </div>
        </a>
        <a href="laporan_pendapatan_admin.php" class="bg-white/90 backdrop-blur-xl p-3 sm:p-4 rounded-xl lg:rounded-2xl border border-orange-100 flex flex-col items-center gap-2 shadow-sm hover:shadow-md hover:scale-105 transition-all cursor-pointer group">
            <div class="w-10 sm:w-12 h-10 sm:h-12 rounded-lg bg-gradient-to-br from-rose-400 to-rose-600 flex items-center justify-center text-white group-hover:shadow-lg transition-all">
                <span class="material-symbols-outlined text-lg sm:text-xl">trending_up</span>
            </div>
            <div class="text-center">
                <p class="text-[8px] sm:text-[9px] font-black text-rose-500 uppercase tracking-wider leading-tight">Pajak</p>
                <h3 class="text-xs sm:text-sm lg:text-base font-extrabold text-[#2a2a2a] mt-0.5">Rp <?= number_format($pendapatan_admin, 0, ',', '.') ?></h3>
            </div>
        </a>
    </section>

    <!-- Grafik Penjualan -->
    <div class="bg-white rounded-xl sm:rounded-2xl lg:rounded-3xl shadow-sm border border-slate-50 p-3 sm:p-4 md:p-6">
        <div class="mb-4 sm:mb-6">
            <h4 class="text-sm sm:text-base md:text-lg font-extrabold text-[#003049]">Penjualan 7 Hari Terakhir</h4>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Ringkasan total penjualan per hari</p>
        </div>
        <div class="overflow-x-auto pb-2">
            <div class="flex items-end justify-between h-32 sm:h-40 md:h-56 gap-1 sm:gap-2 min-w-[300px] sm:min-w-full">
                <?php foreach($grafik_data as $g): $height = ($g['nilai'] / $max_val) * 100; ?>
                <div class="flex-1 flex flex-col items-center gap-1 sm:gap-2 group relative">
                    <div class="absolute -top-6 sm:-top-8 bg-[#003049] text-white text-[8px] px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10 pointer-events-none">
                        Rp<?= number_format($g['nilai'], 0, ',', '.') ?>
                    </div>
                    <div class="w-full bg-slate-100 rounded-t relative flex-1 overflow-hidden min-h-[20px]">
                        <div class="absolute bottom-0 left-0 w-full bg-gradient-to-t from-primary-orange to-orange-400 rounded-t transition-all duration-700 ease-out" style="height:<?= $height ?>%"></div>
                    </div>
                    <span class="text-[7px] sm:text-[9px] font-black <?= $g['is_today'] ? 'text-primary-orange font-bold' : 'text-slate-400' ?>"><?= $g['label'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="mt-6 sm:mt-8 text-center text-xs sm:text-sm text-slate-500 pb-4">
        <p>© 2024-<?= date('Y') ?> <span class="font-bold text-[#2a2a2a]">Kantin Kita</span>. Dashboard Admin System.</p>
    </div>
</main>

</body>
</html>
