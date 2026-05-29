<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Ambil data kantin dengan statistik
$query_kantin = mysqli_query($koneksi, "
    SELECT k.id_kantin, k.nama_kantin, k.status_buka, k.rating, k.total_ulasan, k.logo,
           (SELECT GROUP_CONCAT(u.username SEPARATOR ', ') FROM users u WHERE u.id_kantin = k.id_kantin AND u.role='penjual' LIMIT 5) as nama_penjual,
           COUNT(DISTINCT m.id_menu) as total_menu,
           COALESCE(SUM(dp.qty), 0) as total_terjual,
           COALESCE(SUM(CASE WHEN p.status IN ('Selesai', 'Siap Diambil') THEN p.total_harga ELSE 0 END), 0) as total_pendapatan
    FROM kantin k
    LEFT JOIN menu m ON k.id_kantin = m.id_kantin
    LEFT JOIN detail_pesanan dp ON m.id_menu = dp.id_menu
    LEFT JOIN pesanan p ON dp.id_pesanan = p.id_pesanan
    GROUP BY k.id_kantin
    ORDER BY k.rating DESC, total_pendapatan DESC
    LIMIT 200
");

// Statistik kantin
$res = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kantin");
$total_kantin = ($res && $row = mysqli_fetch_assoc($res)) ? $row['total'] : 0;

$res = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kantin WHERE status_buka='Buka'");
$kantin_buka = ($res && $row = mysqli_fetch_assoc($res)) ? $row['total'] : 0;

$res = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM kantin WHERE status_buka='Tutup'");
$kantin_tutup = ($res && $row = mysqli_fetch_assoc($res)) ? $row['total'] : 0;

$res = mysqli_query($koneksi, "SELECT COALESCE(AVG(rating), 0) as rata FROM kantin WHERE rating > 0");
$rata_rating = ($res && $row = mysqli_fetch_assoc($res)) ? $row['rata'] : 0;

// Total menu
$res = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM menu");
$total_menu_keseluruhan = ($res && $row = mysqli_fetch_assoc($res)) ? $row['total'] : 0;

// Kantin dengan rating terbaik
$res = mysqli_query($koneksi, "SELECT k.*, (SELECT GROUP_CONCAT(u.username SEPARATOR ', ') FROM users u WHERE u.id_kantin = k.id_kantin AND u.role='penjual' LIMIT 5) as nama_penjual FROM kantin k ORDER BY k.rating DESC LIMIT 1");
$best_kantin = ($res && $row = mysqli_fetch_assoc($res)) ? $row : [];

// Filter
$filterStatus = $_GET['status'] ?? '';
$filterPenjual = $_GET['penjual'] ?? '';

$where = ["1=1"];
if ($filterStatus) {
    $safe_status = mysqli_real_escape_string($koneksi, $filterStatus);
    $where[] = "k.status_buka = '$safe_status'";
}

$where_sql = implode(' AND ', $where);

$query_filtered = mysqli_query($koneksi, "
    SELECT k.id_kantin, k.nama_kantin, k.status_buka, k.rating, k.total_ulasan, k.logo,
           (SELECT GROUP_CONCAT(u.username SEPARATOR ', ') FROM users u WHERE u.id_kantin = k.id_kantin AND u.role='penjual' LIMIT 5) as nama_penjual,
           COUNT(DISTINCT m.id_menu) as total_menu,
           COALESCE(SUM(dp.qty), 0) as total_terjual,
           COALESCE(SUM(CASE WHEN p.status IN ('Selesai', 'Siap Diambil') THEN p.total_harga ELSE 0 END), 0) as total_pendapatan
    FROM kantin k
    LEFT JOIN menu m ON k.id_kantin = m.id_kantin
    LEFT JOIN detail_pesanan dp ON m.id_menu = dp.id_menu
    LEFT JOIN pesanan p ON dp.id_pesanan = p.id_pesanan
    WHERE $where_sql
    GROUP BY k.id_kantin, k.nama_kantin, k.status_buka, k.rating, k.total_ulasan, k.logo
    ORDER BY k.rating DESC, total_pendapatan DESC
    LIMIT 200
");

// Handle query error
if (!$query_filtered) {
    $query_filtered = mysqli_query($koneksi, "SELECT * FROM kantin WHERE 1=0");
}

// Daftar status untuk filter
$daftar_status = ['Buka', 'Tutup'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Laporan Kantin - Kantin Kita</title>
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
                    borderRadius: { '4xl': '2.5rem' }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: radial-gradient(circle at top left, rgba(251,146,60,.20), transparent 32%), radial-gradient(circle at 80% 20%, rgba(255,183,3,.12), transparent 25%), linear-gradient(180deg,#fff7f1 0%,#fff2e7 38%,#fff9f3 100%); }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #FF8C20; border-radius: 10px; }
        .glow-card { box-shadow: 0 25px 80px rgba(251,146,60,0.16); }
        .table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .table-container table { min-width: 700px; }
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
            <h2 class="text-2xl md:text-3xl font-extrabold text-[#2a2a2a] tracking-tight">Laporan Kantin</h2>
            <p class="text-orange-700 font-semibold mt-1 text-sm md:text-base">Analisis kinerja dan data kantin</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
            <button onclick="window.print()" class="bg-gradient-to-r from-orange-500 to-orange-600 text-white px-4 py-2.5 rounded-2xl font-bold shadow-lg flex items-center justify-center gap-2 hover:-translate-y-0.5 transition-all text-sm w-full sm:w-auto">
                <span class="material-symbols-outlined text-lg">print</span> Cetak
            </button>
        </div>
    </header>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white/90 backdrop-blur-xl p-4 md:p-6 rounded-2xl border border-orange-100 shadow-glow-card">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-2xl bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white shrink-0">
                    <span class="material-symbols-outlined text-xl md:text-2xl">store</span>
                </div>
                <div>
                    <p class="text-[10px] font-black text-orange-500 uppercase tracking-widest">Total Kantin</p>
                    <h3 class="text-lg md:text-xl font-extrabold text-[#2a2a2a]"><?= number_format($total_kantin) ?></h3>
                </div>
            </div>
        </div>
        <div class="bg-white/90 backdrop-blur-xl p-4 md:p-6 rounded-2xl border border-orange-100 shadow-glow-card">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-2xl bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center text-white shrink-0">
                    <span class="material-symbols-outlined text-xl md:text-2xl">done</span>
                </div>
                <div>
                    <p class="text-[10px] font-black text-green-500 uppercase tracking-widest">Kantin Buka</p>
                    <h3 class="text-lg md:text-xl font-extrabold text-[#2a2a2a]"><?= number_format($kantin_buka) ?></h3>
                </div>
            </div>
        </div>
        <div class="bg-white/90 backdrop-blur-xl p-4 md:p-6 rounded-2xl border border-orange-100 shadow-glow-card">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-2xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white shrink-0">
                    <span class="material-symbols-outlined text-xl md:text-2xl">restaurant_menu</span>
                </div>
                <div>
                    <p class="text-[10px] font-black text-blue-500 uppercase tracking-widest">Total Menu</p>
                    <h3 class="text-lg md:text-xl font-extrabold text-[#2a2a2a]"><?= number_format($total_menu_keseluruhan) ?></h3>
                </div>
            </div>
        </div>
        <div class="bg-white/90 backdrop-blur-xl p-4 md:p-6 rounded-2xl border border-orange-100 shadow-glow-card">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-2xl bg-gradient-to-br from-yellow-400 to-yellow-600 flex items-center justify-center text-white shrink-0">
                    <span class="material-symbols-outlined text-xl md:text-2xl">star</span>
                </div>
                <div>
                    <p class="text-[10px] font-black text-yellow-500 uppercase tracking-widest">Rata-Rata Rating</p>
                    <h3 class="text-lg md:text-xl font-extrabold text-[#2a2a2a]"><?= round($rata_rating, 1) ?>/5</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Kantin -->
    <?php if (!empty($best_kantin) && $best_kantin['id_kantin']): ?>
    <div class="bg-white rounded-2xl p-4 md:p-6 shadow-sm border border-slate-50 mb-8">
        <h3 class="font-extrabold text-[#003049] text-lg flex items-center gap-2 mb-4">
            <span class="material-symbols-outlined text-primary-orange">star</span>
            Kantin Terbaik
        </h3>
        <div class="bg-gradient-to-br from-yellow-50 to-orange-50 rounded-2xl p-6 border border-yellow-100 flex gap-6 flex-col md:flex-row">
            <?php 
                $logo = !empty($best_kantin['logo']) && file_exists("../../uploads/{$best_kantin['logo']}") ? "../../uploads/{$best_kantin['logo']}" : "../../public/assets/img/default-kantin.svg";
            ?>
            <img src="<?= $logo ?>" class="w-full md:w-48 h-48 rounded-2xl object-cover bg-slate-100" alt="<?= htmlspecialchars($best_kantin['nama_kantin']) ?>">
            <div class="flex-1">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <h4 class="text-2xl font-extrabold text-[#2a2a2a]"><?= htmlspecialchars($best_kantin['nama_kantin']) ?></h4>
                        <p class="text-slate-600 font-semibold">Penjual: <?= htmlspecialchars($best_kantin['nama_penjual'] ?? 'Unknown') ?></p>
                    </div>
                    <div class="text-right">
                        <div class="flex items-center gap-1 justify-end mb-2">
                            <span class="material-symbols-outlined text-yellow-500 text-xl">star</span>
                            <span class="text-2xl font-extrabold text-[#2a2a2a]"><?= round($best_kantin['rating'], 1) ?></span>
                        </div>
                        <p class="text-xs text-slate-500"><?= (int)$best_kantin['total_ulasan'] ?> ulasan</p>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-white/50 backdrop-blur-sm rounded-xl p-3">
                        <p class="text-xs font-bold text-slate-400">Status</p>
                        <p class="text-sm font-extrabold text-[#2a2a2a] mt-1">
                            <span class="inline-block px-2 py-1 rounded-lg <?= $best_kantin['status_buka'] == 'Buka' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                <?= htmlspecialchars($best_kantin['status_buka']) ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="bg-white rounded-2xl p-4 md:p-6 mb-6 shadow-sm border border-slate-50">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Status</label>
                <select name="status" class="w-full px-4 py-3 rounded-xl border border-slate-100 bg-slate-50 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary-orange/20">
                    <option value="">Semua Status</option>
                    <option value="Buka" <?= $filterStatus == 'Buka' ? 'selected' : '' ?>>Buka</option>
                    <option value="Tutup" <?= $filterStatus == 'Tutup' ? 'selected' : '' ?>>Tutup</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Penjual</label>
                <select name="penjual" class="w-full px-4 py-3 rounded-xl border border-slate-100 bg-slate-50 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary-orange/20">
                    <option value="">Semua Penjual</option>
                    <?php 
                        if ($daftar_penjual) {
                            mysqli_data_seek($daftar_penjual, 0);
                            while ($p = mysqli_fetch_assoc($daftar_penjual)): 
                    ?>
                    <option value="<?= $p['id_user'] ?>" <?= $filterPenjual == $p['id_user'] ? 'selected' : '' ?>><?= htmlspecialchars($p['username']) ?></option>
                    <?php 
                            endwhile;
                        }
                    ?>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-primary-orange text-white px-6 py-3 rounded-xl font-bold shadow-lg hover:scale-105 transition-all text-sm flex-1">
                    <span class="material-symbols-outlined text-sm mr-1">search</span> Filter
                </button>
                <a href="laporan_kantin.php" class="bg-slate-200 text-slate-600 px-4 py-3 rounded-xl font-bold hover:bg-slate-300 transition-all text-sm">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-50 overflow-hidden">
        <div class="p-4 md:p-6 border-b border-slate-50">
            <h3 class="font-extrabold text-[#003049] text-lg flex items-center gap-2">
                <span class="material-symbols-outlined text-primary-orange">list_alt</span>
                Data Kantin
                <span class="bg-orange-100 text-orange-600 px-3 py-1 rounded-xl text-xs font-bold ml-2"><?= $query_filtered ? mysqli_num_rows($query_filtered) : 0 ?></span>
            </h3>
        </div>

        <div class="table-container">
            <table class="w-full mobile-card">
                <thead class="bg-slate-50 text-xs font-black text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-4 text-left">Kantin</th>
                        <th class="px-4 py-4 text-left hidden md:table-cell">Penjual</th>
                        <th class="px-4 py-4 text-left hidden sm:table-cell">Status</th>
                        <th class="px-4 py-4 text-left">Rating</th>
                        <th class="px-4 py-4 text-left hidden lg:table-cell">Total Menu</th>
                        <th class="px-4 py-4 text-left hidden lg:table-cell">Pendapatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if ($query_filtered && mysqli_num_rows($query_filtered) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($query_filtered)): ?>
                        <tr class="hover:bg-orange-50/50 transition-colors">
                            <td class="px-4 py-4" data-label="Kantin">
                                <div class="flex items-center gap-3">
                                    <?php 
                                        $logo = !empty($row['logo']) && file_exists("../../uploads/{$row['logo']}") ? "../../uploads/{$row['logo']}" : "../../public/assets/img/default-kantin.svg";
                                    ?>
                                    <img src="<?= $logo ?>" class="w-8 h-8 rounded-lg object-cover bg-slate-100 shrink-0" alt="">
                                    <span class="font-semibold text-sm"><?= htmlspecialchars($row['nama_kantin'] ?? 'Unknown') ?></span>
                                </div>
                            </td>
                            <td class="px-4 py-4 hidden md:table-cell" data-label="Penjual">
                                <span class="text-sm text-slate-600"><?= htmlspecialchars($row['nama_penjual'] ?? 'Unknown') ?></span>
                            </td>
                            <td class="px-4 py-4 hidden sm:table-cell" data-label="Status">
                                <span class="inline-block px-3 py-1 rounded-lg text-xs font-bold <?= $row['status_buka'] == 'Buka' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                    <?= htmlspecialchars($row['status_buka']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-4" data-label="Rating">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-yellow-500">star</span>
                                    <span class="font-bold"><?= round((float)$row['rating'], 1) ?></span>
                                    <span class="text-xs text-slate-400">(<?= (int)$row['total_ulasan'] ?>)</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 hidden lg:table-cell" data-label="Total Menu">
                                <span class="font-bold text-slate-800"><?= (int)$row['total_menu'] ?> menu</span>
                            </td>
                            <td class="px-4 py-4 hidden lg:table-cell" data-label="Pendapatan">
                                <span class="font-bold text-primary-orange">Rp <?= number_format($row['total_pendapatan'], 0, ',', '.') ?></span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center">
                                <div class="flex flex-col items-center text-slate-300">
                                    <span class="material-symbols-outlined text-5xl">store</span>
                                    <p class="mt-3 font-bold text-slate-400">Belum ada data kantin</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

</body>
</html>