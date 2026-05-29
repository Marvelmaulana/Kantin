<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/language_helper.php');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// === FILTER INPUT ===
$filterTanggal = $_GET['tanggal'] ?? date('Y-m-d');
$filterKantin = $_GET['kantin'] ?? '';

$where = ["p.status = 'Selesai'"];
if ($filterTanggal) {
    $safe_tanggal = mysqli_real_escape_string($koneksi, $filterTanggal);
    $where[] = "DATE(p.tanggal) = '$safe_tanggal'";
}
if ($filterKantin) {
    $safe_kantin = mysqli_real_escape_string($koneksi, $filterKantin);
    $where[] = "p.id_kantin = '$safe_kantin'";
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

// === STATISTIK PENDAPATAN ADMIN (DENGAN FILTER) ===
$stat_pajak_q = mysqli_query($koneksi, "
    SELECT COALESCE(SUM(p.pajak), 0) as total 
    FROM pesanan p
    $where_sql
");
$total_pajak = $stat_pajak_q ? mysqli_fetch_assoc($stat_pajak_q)['total'] : 0;

$stat_transaksi_q = mysqli_query($koneksi, "
    SELECT COUNT(*) as total 
    FROM pesanan p
    $where_sql
");
$total_transaksi = $stat_transaksi_q ? mysqli_fetch_assoc($stat_transaksi_q)['total'] : 0;

// Filter khusus bulan ini dengan mempertahankan filter kantin jika dipilih
$where_bulan_ini = $where;
$where_bulan_ini[] = "MONTH(p.tanggal) = MONTH(NOW()) AND YEAR(p.tanggal) = YEAR(NOW())";
$where_bulan_ini_sql = 'WHERE ' . implode(' AND ', $where_bulan_ini);

$stat_transaksi_bulan_ini_q = mysqli_query($koneksi, "
    SELECT COUNT(*) as total 
    FROM pesanan p
    $where_bulan_ini_sql
");
$transaksi_bulan_ini = $stat_transaksi_bulan_ini_q ? mysqli_fetch_assoc($stat_transaksi_bulan_ini_q)['total'] : 0;

// === PAGINATION ===
$halaman = isset($_GET['halaman']) ? max(1, (int)$_GET['halaman']) : 1;
$per_halaman = 10;
$offset = ($halaman - 1) * $per_halaman;

// Hitung total data terfilter
$result_count = mysqli_query($koneksi, "
    SELECT COUNT(*) as total 
    FROM pesanan p
    $where_sql
");
$total_data = $result_count ? mysqli_fetch_assoc($result_count)['total'] : 0;
$total_halaman = ceil($total_data / $per_halaman);

if ($halaman > $total_halaman && $total_halaman > 0) {
    $halaman = $total_halaman;
    $offset = ($halaman - 1) * $per_halaman;
}

// === GRAFIK 7 HARI (DENGAN FILTER KANTIN) ===
$grafik_data = [];
$days_label  = ['Mon'=>'SEN','Tue'=>'SEL','Wed'=>'RAB','Thu'=>'KAM','Fri'=>'JUM','Sat'=>'SAB','Sun'=>'MIN'];
for ($i = 6; $i >= 0; $i--) {
    $date   = date('Y-m-d', strtotime("-$i days"));
    $day_en = date('D', strtotime($date));
    
    $query_g = "
        SELECT COALESCE(SUM(p.pajak), 0) as total 
        FROM pesanan p
        WHERE DATE(p.tanggal)='$date' AND p.status='Selesai'
    ";
    if ($filterKantin) {
        $safe_kantin = mysqli_real_escape_string($koneksi, $filterKantin);
        $query_g .= " AND p.id_kantin = '$safe_kantin'";
    }
    
    $res_g = mysqli_query($koneksi, $query_g);
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

// === DETAIL DATA (DENGAN PAGINATION) ===
$query_detail = mysqli_query($koneksi, "
    SELECT p.id_pesanan as id_transaksi, p.tanggal, p.pajak as jumlah_pajak, p.metode_pembayaran,
           p.total_harga, u.username, k.nama_kantin
    FROM pesanan p
    LEFT JOIN users u ON p.id_user = u.id_user
    LEFT JOIN kantin k ON p.id_kantin = k.id_kantin
    $where_sql
    ORDER BY p.tanggal DESC
    LIMIT $offset, $per_halaman
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
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'bg-soft': '#FFF4EB',
                        'primary-orange': '#E25E3E',
                        'primary-orange-dark': '#C2410C',
                        'accent-orange': '#fb8500',
                        'neon-orange': '#ffb703'
                    },
                    borderRadius: {
                        '4xl': '2.5rem'
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family:'Plus Jakarta Sans',sans-serif; background: radial-gradient(circle at top left, rgba(251,146,60,.20), transparent 32%), radial-gradient(circle at 80% 20%, rgba(255,183,3,.12), transparent 25%), linear-gradient(180deg,#fff7f1 0%,#fff2e7 38%,#fff9f3 100%); }
        ::-webkit-scrollbar{width:6px;height:6px} ::-webkit-scrollbar-thumb{background:#FF8C20;border-radius:10px}
        .glow-card{box-shadow:0 25px 80px rgba(251,146,60,0.16);}
        .table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .table-container table { min-width: 600px; }
        @media (max-width: 640px) {
            .mobile-card { display: block !important; }
            .mobile-card thead { display: none; }
            .mobile-card tbody tr { display: block; margin-bottom: 1rem; background: white; border-radius: 1rem; padding: 1rem; box-shadow: 0 4px 15px rgba(251,146,60,0.1); }
            .mobile-card tbody td { display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; border-bottom: 1px solid #f1f1f1; }
            .mobile-card tbody td:last-child { border-bottom: none; }
            .mobile-card tbody td::before { content: attr(data-label); font-weight: 700; color: #666; width: 40%; }
        }
    </style>
</head>
<body class="text-slate-800 flex min-h-screen overflow-x-hidden">

<?php include '../../includes/sidebar_admin.php'; ?>

<main class="flex-1 w-full lg:ml-72 p-4 md:p-6 lg:p-8 overflow-x-hidden max-w-full">
    <!-- Header -->
    <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-8 mt-14 lg:mt-0">
        <div>
            <h2 class="text-2xl md:text-3xl font-extrabold text-[#2a2a2a] tracking-tight">Laporan Pendapatan Admin</h2>
            <p class="text-orange-700 font-semibold mt-1 text-sm md:text-base">Kelola dan lihat pendapatan dari biaya layanan transaksi</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
            <button onclick="window.print()" class="bg-gradient-to-r from-orange-500 to-orange-600 text-white px-4 py-2.5 rounded-2xl font-bold shadow-lg flex items-center justify-center gap-2 hover:-translate-y-0.5 transition-all text-sm w-full sm:w-auto">
                <span class="material-symbols-outlined text-lg">print</span> Cetak
            </button>
        </div>
    </header>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white/90 backdrop-blur-xl p-4 md:p-6 rounded-2xl border border-orange-100 shadow-glow-card">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-2xl bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white shrink-0">
                    <span class="material-symbols-outlined text-xl md:text-2xl">receipt_long</span>
                </div>
                <div>
                    <p class="text-[10px] font-black text-orange-500 uppercase tracking-widest">Total Transaksi</p>
                    <h3 class="text-lg md:text-xl font-extrabold text-[#2a2a2a]"><?= number_format($total_transaksi) ?></h3>
                </div>
            </div>
        </div>
        <div class="bg-white/90 backdrop-blur-xl p-4 md:p-6 rounded-2xl border border-orange-100 shadow-glow-card">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-2xl bg-gradient-to-br from-yellow-400 to-yellow-600 flex items-center justify-center text-white shrink-0">
                    <span class="material-symbols-outlined text-xl md:text-2xl">calendar_month</span>
                </div>
                <div>
                    <p class="text-[10px] font-black text-yellow-500 uppercase tracking-widest">Bulan Ini</p>
                    <h3 class="text-base md:text-lg font-extrabold text-[#2a2a2a]"><?= number_format($transaksi_bulan_ini) ?> trx</h3>
                </div>
            </div>
        </div>

        <div class="bg-white/90 backdrop-blur-xl p-4 md:p-6 rounded-2xl border border-orange-100 shadow-glow-card">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-2xl bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center text-white shrink-0">
                    <span class="material-symbols-outlined text-xl md:text-2xl">trending_up</span>
                </div>
                <div>
                    <p class="text-[10px] font-black text-green-500 uppercase tracking-widest">Total Pendapatan</p>
                    <h3 class="text-sm md:text-lg font-extrabold text-[#2a2a2a]">Rp <?= number_format($total_pajak, 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-2xl p-4 md:p-6 mb-6 shadow-sm border border-slate-50">
        <form method="GET" class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Tanggal</label>
                <input type="date" name="tanggal" value="<?= htmlspecialchars($filterTanggal) ?>" class="w-full px-4 py-3 rounded-xl border border-slate-100 bg-slate-50 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary-orange/20">
            </div>
            <div class="flex-1">
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Kantin</label>
                <select name="kantin" class="w-full px-4 py-3 rounded-xl border border-slate-100 bg-slate-50 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary-orange/20">
                    <option value="">Semua Kantin</option>
                    <?php 
                    // Reset pointer if needed
                    mysqli_data_seek($daftar_kantin, 0);
                    while ($kantin = mysqli_fetch_assoc($daftar_kantin)): 
                    ?>
                        <option value="<?= $kantin['id_kantin'] ?>" <?= $filterKantin == $kantin['id_kantin'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($kantin['nama_kantin']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-primary-orange text-white px-6 py-3 rounded-xl font-bold shadow-lg hover:scale-105 transition-all text-sm whitespace-nowrap flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-sm">search</span> Filter
                </button>
                <a href="laporan_pendapatan_admin.php" class="bg-slate-200 text-slate-600 px-4 py-3 rounded-xl font-bold hover:bg-slate-300 transition-all text-sm">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Grafik Pendapatan 7 Hari -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-50 p-4 md:p-6 mb-6">
        <div class="mb-4">
            <h4 class="text-base font-extrabold text-[#003049] flex items-center gap-2">
                <span class="material-symbols-outlined text-primary-orange">bar_chart</span>
                Tren Pendapatan Biaya Layanan 7 Hari Terakhir
            </h4>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">Ringkasan total pendapatan biaya layanan per hari</p>
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

    <!-- Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-50 overflow-hidden">
        <div class="p-4 md:p-6 border-b border-slate-50">
            <h3 class="font-extrabold text-[#003049] text-lg flex items-center gap-2">
                <span class="material-symbols-outlined text-primary-orange">table_chart</span>
                Detail Biaya Layanan Transaksi
                <span class="bg-orange-100 text-orange-600 px-3 py-1 rounded-xl text-xs font-bold ml-2"><?= number_format($total_data) ?></span>
            </h3>
        </div>
        <div class="table-container">
            <table class="w-full text-sm mobile-card">
                <thead class="bg-slate-50 text-xs font-black text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-4 text-left">No</th>
                        <th class="px-4 py-4 text-left">ID</th>
                        <th class="px-4 py-4 text-left">Pembeli</th>
                        <th class="px-4 py-4 text-left hidden sm:table-cell">Kantin</th>
                        <th class="px-4 py-4 text-left">Tanggal</th>
                        <th class="px-4 py-4 text-right">Total</th>
                        <th class="px-4 py-4 text-right">Biaya Layanan</th>
                        <th class="px-4 py-4 text-left">Metode</th>
                        <th class="px-4 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!$query_detail || mysqli_num_rows($query_detail) === 0): ?>
                    <tr>
                        <td colspan="9" class="px-4 py-16 text-center">
                            <div class="flex flex-col items-center text-slate-300">
                                <span class="material-symbols-outlined text-5xl">inbox</span>
                                <p class="mt-3 font-bold text-slate-400">Belum ada data pajak transaksi</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php 
                        $no = $offset + 1;
                        while ($detail = mysqli_fetch_assoc($query_detail)): 
                        ?>
                        <tr class="hover:bg-orange-50/50 transition-colors">
                            <td class="px-4 py-4 font-bold text-slate-500" data-label="No"><?= $no++ ?></td>
                            <td class="px-4 py-4" data-label="ID">
                                <span class="font-bold text-primary-orange">#<?= htmlspecialchars($detail['id_transaksi']) ?></span>
                            </td>
                            <td class="px-4 py-4" data-label="Pembeli">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white text-xs font-bold uppercase shrink-0">
                                        <?= substr($detail['username'] ?? 'U', 0, 1) ?>
                                    </div>
                                    <p class="font-bold text-sm"><?= htmlspecialchars($detail['username'] ?? 'Guest') ?></p>
                                </div>
                            </td>
                            <td class="px-4 py-4 hidden sm:table-cell" data-label="Kantin">
                                <span class="text-sm font-semibold"><?= htmlspecialchars($detail['nama_kantin'] ?? '-') ?></span>
                            </td>
                            <td class="px-4 py-4" data-label="Tanggal">
                                <div>
                                    <p class="text-sm font-semibold"><?= date('d M Y', strtotime($detail['tanggal'])) ?></p>
                                    <p class="text-xs text-slate-400"><?= date('H:i', strtotime($detail['tanggal'])) ?></p>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-right" data-label="Total">
                                <span class="font-bold text-primary-orange text-sm">Rp <?= number_format($detail['total_harga'] ?? 0, 0, ',', '.') ?></span>
                            </td>
                            <td class="px-4 py-4 text-right" data-label="Biaya Layanan">
                                <span class="px-3 py-1.5 rounded-lg text-xs font-bold text-white bg-gradient-to-r from-yellow-400 to-orange-500 inline-block">
                                    Rp <?= number_format($detail['jumlah_pajak'] ?? 1000, 0, ',', '.') ?>
                                </span>
                            </td>
                            <td class="px-4 py-4" data-label="Metode">
                                <span class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 inline-block">
                                    <?= htmlspecialchars($detail['metode_pembayaran'] ?? 'Cash') ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center" data-label="Aksi">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="detail_transaksi.php?id=<?= $detail['id_transaksi'] ?>" class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-orange-100 text-primary-orange hover:bg-primary-orange hover:text-white transition-all" title="Lihat Detail">
                                        <span class="material-symbols-outlined text-sm">visibility</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_halaman > 1): ?>
        <div class="px-4 md:px-6 py-4 border-t border-slate-50 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-sm text-slate-500 font-semibold">
                Menampilkan <?= $offset + 1 ?> - <?= min($offset + $per_halaman, $total_data) ?> dari <?= number_format($total_data) ?> data
            </p>
            <div class="flex items-center gap-2">
                <?php if ($halaman > 1): ?>
                    <a href="?halaman=1<?= $filterTanggal ? '&tanggal=' . urlencode($filterTanggal) : '' ?><?= $filterKantin ? '&kantin=' . urlencode($filterKantin) : '' ?>" class="px-3 py-2 rounded-lg border border-slate-200 text-slate-600 font-bold hover:bg-slate-100 transition-all text-sm">
                        &laquo; Awal
                    </a>
                    <a href="?halaman=<?= $halaman - 1 ?><?= $filterTanggal ? '&tanggal=' . urlencode($filterTanggal) : '' ?><?= $filterKantin ? '&kantin=' . urlencode($filterKantin) : '' ?>" class="px-3 py-2 rounded-lg border border-slate-200 text-slate-600 font-bold hover:bg-slate-100 transition-all text-sm">
                        &lsaquo; Sebelumnya
                    </a>
                <?php endif; ?>
                
                <div class="flex items-center gap-1">
                    <?php
                    $start_page = max(1, $halaman - 2);
                    $end_page = min($total_halaman, $halaman + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                        <?php if ($i == $halaman): ?>
                            <span class="px-3 py-2 rounded-lg bg-primary-orange text-white font-bold text-sm">
                                <?= $i ?>
                            </span>
                        <?php else: ?>
                            <a href="?halaman=<?= $i ?><?= $filterTanggal ? '&tanggal=' . urlencode($filterTanggal) : '' ?><?= $filterKantin ? '&kantin=' . urlencode($filterKantin) : '' ?>" class="px-3 py-2 rounded-lg border border-slate-200 text-slate-600 font-bold hover:bg-slate-100 transition-all text-sm">
                                <?= $i ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
                
                <?php if ($halaman < $total_halaman): ?>
                    <a href="?halaman=<?= $halaman + 1 ?><?= $filterTanggal ? '&tanggal=' . urlencode($filterTanggal) : '' ?><?= $filterKantin ? '&kantin=' . urlencode($filterKantin) : '' ?>" class="px-3 py-2 rounded-lg border border-slate-200 text-slate-600 font-bold hover:bg-slate-100 transition-all text-sm">
                        Selanjutnya &rsaquo;
                    </a>
                    <a href="?halaman=<?= $total_halaman ?><?= $filterTanggal ? '&tanggal=' . urlencode($filterTanggal) : '' ?><?= $filterKantin ? '&kantin=' . urlencode($filterKantin) : '' ?>" class="px-3 py-2 rounded-lg border border-slate-200 text-slate-600 font-bold hover:bg-slate-100 transition-all text-sm">
                        Akhir &raquo;
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
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
