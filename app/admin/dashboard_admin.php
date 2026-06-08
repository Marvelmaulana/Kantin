<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/language_helper.php');
include(__DIR__ . '/../../includes/auth_helpers.php');

// Initialize message variables to avoid "Undefined variable" warnings
$message = '';
$message_type = '';

// SECURITY: Session & Role Validation
if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// SECURITY: Session Timeout Check (1 hour)
$session_timeout = 3600; // 1 hour in seconds
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $session_timeout) {
    session_destroy();
    header("Location: ../auth/login.php?reason=timeout");
    exit();
}

// Update last activity time
$_SESSION['login_time'] = time();


// Statistik
$result_pembeli   = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role='pembeli'");
$jumlah_pembeli   = $result_pembeli ? (mysqli_fetch_assoc($result_pembeli)['total'] ?? 0) : 0;

$result_penjual   = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role='penjual'");
$jumlah_penjual   = $result_penjual ? (mysqli_fetch_assoc($result_penjual)['total'] ?? 0) : 0;

$result_kantin    = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kantin");
$jumlah_kantin    = $result_kantin ? (mysqli_fetch_assoc($result_kantin)['total'] ?? 0) : 0;

$result_menu      = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM menu");
$jumlah_menu      = $result_menu ? (mysqli_fetch_assoc($result_menu)['total'] ?? 0) : 0;

$result_transaksi = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pesanan");
$jumlah_transaksi = $result_transaksi ? (mysqli_fetch_assoc($result_transaksi)['total'] ?? 0) : 0;

// Seluruh Pendapatan Semua Kantin
$result_pajak     = mysqli_query($koneksi, "SELECT COALESCE(SUM(total_harga), 0) as total FROM pesanan WHERE status='Selesai'");
$pendapatan_admin = $result_pajak ? (mysqli_fetch_assoc($result_pajak)['total'] ?? 0) : 0;

// Grafik 7 hari
$grafik_data = [];
$days_label  = ['Mon'=>'SEN','Tue'=>'SEL','Wed'=>'RAB','Thu'=>'KAM','Fri'=>'JUM','Sat'=>'SAB','Sun'=>'MIN'];
for ($i = 6; $i >= 0; $i--) {
    $date   = date('Y-m-d', strtotime("-$i days"));
    $day_en = date('D', strtotime($date));
    
    // SECURITY: Use parameterized query to prevent SQL injection
    $date_escaped = mysqli_real_escape_string($koneksi, $date);
    $res_g  = mysqli_query($koneksi, "SELECT SUM(total_harga) as total FROM pesanan 
                                      WHERE DATE(tanggal)='$date_escaped' AND status='Selesai'");
    
    $nilai_grafik = 0;
    if ($res_g && $res_g instanceof mysqli_result) {
        $row = mysqli_fetch_assoc($res_g);
        $nilai_grafik = isset($row['total']) && $row['total'] ? (float)$row['total'] : 0;
        mysqli_free_result($res_g);
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        /* Chart Bar Animation */
        .chart-bar {
            animation: barRise 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }
        .chart-bar:nth-child(1) { animation-delay: 0.1s; }
        .chart-bar:nth-child(2) { animation-delay: 0.15s; }
        .chart-bar:nth-child(3) { animation-delay: 0.2s; }
        .chart-bar:nth-child(4) { animation-delay: 0.25s; }
        .chart-bar:nth-child(5) { animation-delay: 0.3s; }
        .chart-bar:nth-child(6) { animation-delay: 0.35s; }
        .chart-bar:nth-child(7) { animation-delay: 0.4s; }
        
        @keyframes barRise {
            from {
                height: 0%;
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        .chart-container {
            transition: all 0.3s ease;
        }
        
        .chart-container.expanded {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .chart-expanded-content {
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            width: 100%;
            max-width: 90vw;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 80px rgba(0, 0, 0, 0.3);
        }
        
        @media (max-width: 768px) {
            .chart-expanded-content {
                padding: 1.5rem;
                max-height: 80vh;
            }
        }
        
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
        
        // Format Rupiah
        function formatRp(value) {
            return 'Rp ' + Math.round(value).toLocaleString('id-ID');
        }
        
        // Chart Data from PHP
        const chartLabels = <?= json_encode(array_column($grafik_data, 'label')) ?>;
        const chartData = <?= json_encode(array_column($grafik_data, 'nilai')) ?>;
        const totalRevenue = chartData.reduce((a, b) => a + b, 0);
        const avgRevenue = totalRevenue / 7;
        const maxRevenue = Math.max(...chartData);
        const minRevenue = Math.min(...chartData);
        
        let salesChartInstance = null;
        let salesChartExpandedInstance = null;
        
        function createChart(canvasId, isExpanded = false) {
            const ctx = document.getElementById(canvasId)?.getContext('2d');
            if (!ctx) return null;
            
            return new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Pendapatan Harian',
                        data: chartData,
                        borderColor: '#E25E3E',
                        backgroundColor: (context) => {
                            const g = context.chart.ctx.createLinearGradient(0, 0, 0, isExpanded ? 500 : 300);
                            g.addColorStop(0, 'rgba(226, 94, 62, 0.2)');
                            g.addColorStop(1, 'rgba(226, 94, 62, 0.01)');
                            return g;
                        },
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#E25E3E',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2.5,
                        pointRadius: isExpanded ? 7 : 5,
                        pointHoverRadius: isExpanded ? 10 : 8,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(51, 65, 85, 0.9)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            padding: 12,
                            titleFont: { weight: 'bold', size: 12 },
                            bodyFont: { size: 11 },
                            callbacks: {
                                label: function(context) {
                                    return formatRp(context.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0, 0, 0, 0.05)' },
                            ticks: {
                                callback: function(value) {
                                    if (value === 0) return 'Rp0';
                                    if (value >= 1000000) return 'Rp' + (value / 1000000).toFixed(1) + 'M';
                                    if (value >= 1000) return 'Rp' + (value / 1000).toFixed(0) + 'K';
                                    return 'Rp' + value;
                                },
                                font: { size: isExpanded ? 12 : 10 }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: isExpanded ? 12 : 10 } }
                        }
                    },
                    interaction: { intersect: false, mode: 'index' }
                }
            });
        }
        
        function expandChart() {
            const modal = document.getElementById('chartModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                
                // Update statistics
                document.getElementById('totalRevenue').textContent = formatRp(totalRevenue);
                document.getElementById('avgRevenue').textContent = formatRp(avgRevenue);
                document.getElementById('maxRevenue').textContent = formatRp(maxRevenue);
                document.getElementById('minRevenue').textContent = formatRp(minRevenue);
                
                // Initialize expanded chart
                setTimeout(() => {
                    if (salesChartExpandedInstance) salesChartExpandedInstance.destroy();
                    salesChartExpandedInstance = createChart('salesChartExpanded', true);
                }, 50);
            }
        }
        
        function closeExpandedChart() {
            const modal = document.getElementById('chartModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }
        
        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeExpandedChart();
            }
        });
        
        // Close sidebar on link click
        document.addEventListener('DOMContentLoaded', function() {
            initSidebar();
            
            // Initialize main chart
            if (salesChartInstance) salesChartInstance.destroy();
            salesChartInstance = createChart('salesChart', false);
            
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
    </section>

    <!-- Grafik Penjualan -->
    <div class="bg-white rounded-xl sm:rounded-2xl lg:rounded-3xl shadow-sm border border-slate-50 p-3 sm:p-4 md:p-6">
        <div class="mb-4 sm:mb-6 flex items-center justify-between">
            <div>
                <h4 class="text-sm sm:text-base md:text-lg font-extrabold text-[#003049]">Penjualan 7 Hari Terakhir</h4>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Ringkasan total penjualan per hari</p>
            </div>
            <button onclick="expandChart()" class="ml-auto px-3 py-2 sm:px-4 sm:py-2 rounded-lg bg-primary-orange hover:bg-orange-600 text-white text-xs sm:text-sm font-bold transition-all hover:scale-105 flex items-center gap-2">
                <span class="material-symbols-outlined text-sm sm:text-base">fullscreen</span>
                <span class="hidden sm:inline">Perbesar</span>
            </button>
        </div>
        <div class="relative h-48 sm:h-64 md:h-80">
            <canvas id="salesChart"></canvas>
        </div>
    </div>

    <!-- Chart Expanded Modal -->
    <div id="chartModal" class="hidden chart-container" onclick="closeExpandedChart()">
        <div class="chart-expanded-content" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl sm:text-2xl font-extrabold text-[#003049]">Grafik Penjualan - 7 Hari Terakhir</h3>
                    <p class="text-sm text-slate-500 mt-1">Visualisasi lengkap total penjualan per hari</p>
                </div>
                <button onclick="closeExpandedChart()" class="text-slate-400 hover:text-slate-600 transition">
                    <span class="material-symbols-outlined text-2xl">close</span>
                </button>
            </div>
            <div class="relative h-96 sm:h-[500px]">
                <canvas id="salesChartExpanded"></canvas>
            </div>
            <div class="mt-6 pt-6 border-t border-slate-200">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="bg-slate-50 p-4 rounded-lg">
                        <p class="text-xs text-slate-500 font-bold">Total 7 Hari</p>
                        <p class="text-lg sm:text-xl font-extrabold text-primary-orange" id="totalRevenue">Rp0</p>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-lg">
                        <p class="text-xs text-slate-500 font-bold">Rata-rata/Hari</p>
                        <p class="text-lg sm:text-xl font-extrabold text-blue-600" id="avgRevenue">Rp0</p>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-lg">
                        <p class="text-xs text-slate-500 font-bold">Tertinggi</p>
                        <p class="text-lg sm:text-xl font-extrabold text-green-600" id="maxRevenue">Rp0</p>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-lg">
                        <p class="text-xs text-slate-500 font-bold">Terendah</p>
                        <p class="text-lg sm:text-xl font-extrabold text-rose-600" id="minRevenue">Rp0</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="mt-6 sm:mt-8 text-center text-xs sm:text-sm text-slate-500 pb-4">
        <p>© 2025-<?= date('Y') ?> <span class="font-bold text-[#2a2a2a]">Kantin Kita</span>. Dashboard Admin System.</p>
    </div>
</main>

</body>
</html>
