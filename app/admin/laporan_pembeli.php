<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Pembeli</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex">
    <?php include '../../includes/sidebar_admin.php'; ?>
    
    <main class="flex-1 p-8">
        <h1 class="text-2xl font-bold">Laporan Pembeli</h1>
        <p class="mt-4 text-slate-600">Halaman laporan pembeli sedang dalam pengembangan.</p>
        
        <!-- Placeholder content -->
        <div class="mt-8 bg-slate-100 p-6 rounded-lg">
            <p class="text-slate-600">Fitur ini akan menampilkan laporan detail untuk setiap pembeli.</p>
        </div>
    </main>

// Ambil data pembeli dengan statistik
$query_pembeli = mysqli_query($koneksi, "
    SELECT u.id_user, u.username, u.email, u.kelas, u.created_at,
           COUNT(DISTINCT p.id_pesanan) as total_pesanan,
           COALESCE(SUM(p.total_harga), 0) as total_belanja,
           MAX(p.tanggal) as pesanan_terakhir
    FROM users u
    LEFT JOIN pesanan p ON u.id_user = p.id_user
    WHERE u.role = 'pembeli'
    GROUP BY u.id_user
    ORDER BY total_belanja DESC
    LIMIT 200
");

// Statistik pembeli
$res = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role='pembeli'");
$total_pembeli = ($res && $row = mysqli_fetch_assoc($res)) ? $row['total'] : 0;

$res = mysqli_query($koneksi, "SELECT COALESCE(SUM(total_harga),0) as total FROM pesanan WHERE id_user IN (SELECT id_user FROM users WHERE role='pembeli')");
$total_belanja_keseluruhan = ($res && $row = mysqli_fetch_assoc($res)) ? $row['total'] : 0;

$res = mysqli_query($koneksi, "SELECT COUNT(DISTINCT id_user) as total FROM pesanan WHERE id_user IN (SELECT id_user FROM users WHERE role='pembeli')");
$pembeli_aktif = ($res && $row = mysqli_fetch_assoc($res)) ? $row['total'] : 0;

$rata_belanja = $pembeli_aktif > 0 ? $total_belanja_keseluruhan / $pembeli_aktif : 0;

// Pembeli baru bulan ini
$res = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role='pembeli' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
$pembeli_baru_bulan = ($res && $row = mysqli_fetch_assoc($res)) ? $row['total'] : 0;

// Statistik kelas
$kelas_stats = [];
$kelas_result = mysqli_query($koneksi, "
    SELECT u.kelas, COUNT(*) as jumlah, COALESCE(SUM(p.total_harga), 0) as total_belanja
    FROM users u
    LEFT JOIN pesanan p ON u.id_user = p.id_user
    WHERE u.role = 'pembeli'
    GROUP BY u.kelas
    ORDER BY u.kelas
");
if ($kelas_result) {
    while ($ks = mysqli_fetch_assoc($kelas_result)) {
        $kelas_stats[] = $ks;
    }
}

// Filter
$filterTanggal = $_GET['tanggal'] ?? '';
$filterKelas = $_GET['kelas'] ?? '';
$filterStatus = $_GET['status'] ?? ''; // aktif, tidak aktif

$where = ["u.role = 'pembeli'"];
if ($filterKelas) {
    $safe_kelas = mysqli_real_escape_string($koneksi, $filterKelas);
    $where[] = "u.kelas = '$safe_kelas'";
}

$where_sql = implode(' AND ', $where);

$query_filtered = mysqli_query($koneksi, "
    SELECT u.id_user, u.username, u.email, u.kelas, u.created_at,
           COUNT(DISTINCT p.id_pesanan) as total_pesanan,
           COALESCE(SUM(p.total_harga), 0) as total_belanja,
           MAX(p.tanggal) as pesanan_terakhir
    FROM users u
    LEFT JOIN pesanan p ON u.id_user = p.id_user
    WHERE $where_sql
    GROUP BY u.id_user
    ORDER BY total_belanja DESC
    LIMIT 200
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Laporan Pembeli - Kantin Kita</title>
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
            <h2 class="text-2xl md:text-3xl font-extrabold text-[#2a2a2a] tracking-tight">Laporan Pembeli</h2>
            <p class="text-orange-700 font-semibold mt-1 text-sm md:text-base">Analisis data dan aktivitas pembeli</p>
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
                    <span class="material-symbols-outlined text-xl md:text-2xl">people</span>
                </div>
                <div>
                    <p class="text-[10px] font-black text-orange-500 uppercase tracking-widest">Total Pembeli</p>
                    <h3 class="text-lg md:text-xl font-extrabold text-[#2a2a2a]"><?= number_format($total_pembeli) ?></h3>
                </div>
            </div>
        </div>
        <div class="bg-white/90 backdrop-blur-xl p-4 md:p-6 rounded-2xl border border-orange-100 shadow-glow-card">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-2xl bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center text-white shrink-0">
                    <span class="material-symbols-outlined text-xl md:text-2xl">shopping_cart</span>
                </div>
                <div>
                    <p class="text-[10px] font-black text-green-500 uppercase tracking-widest">Pembeli Aktif</p>
                    <h3 class="text-lg md:text-xl font-extrabold text-[#2a2a2a]"><?= number_format($pembeli_aktif) ?></h3>
                </div>
            </div>
        </div>
        <div class="bg-white/90 backdrop-blur-xl p-4 md:p-6 rounded-2xl border border-orange-100 shadow-glow-card">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-2xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white shrink-0">
                    <span class="material-symbols-outlined text-xl md:text-2xl">attach_money</span>
                </div>
                <div>
                    <p class="text-[10px] font-black text-blue-500 uppercase tracking-widest">Total Belanja</p>
                    <h3 class="text-sm md:text-lg font-extrabold text-[#2a2a2a]">Rp <?= number_format($total_belanja_keseluruhan, 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>
        <div class="bg-white/90 backdrop-blur-xl p-4 md:p-6 rounded-2xl border border-orange-100 shadow-glow-card">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-2xl bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center text-white shrink-0">
                    <span class="material-symbols-outlined text-xl md:text-2xl">trending_up</span>
                </div>
                <div>
                    <p class="text-[10px] font-black text-purple-500 uppercase tracking-widest">Rata-Rata Belanja</p>
                    <h3 class="text-sm md:text-lg font-extrabold text-[#2a2a2a]">Rp <?= number_format($rata_belanja, 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Kelas -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-2 bg-white rounded-2xl p-4 md:p-6 shadow-sm border border-slate-50">
            <h3 class="font-extrabold text-[#003049] text-lg flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-primary-orange">school</span>
                Pembeli per Kelas
            </h3>
            <?php if (count($kelas_stats) > 0): ?>
            <div class="space-y-4">
                <?php
                $total_kelas = array_sum(array_column($kelas_stats, 'jumlah'));
                foreach ($kelas_stats as $ks):
                    $persen = $total_kelas > 0 ? ($ks['jumlah'] / $total_kelas) * 100 : 0;
                ?>
                <div class="space-y-2">
                    <div class="flex justify-between text-xs font-bold">
                        <span class="text-slate-600">Kelas <?= htmlspecialchars($ks['kelas'] ?? 'N/A') ?></span>
                        <span class="text-slate-800"><?= (int)$ks['jumlah'] ?> pembeli (<?= round($persen) ?>%)</span>
                    </div>
                    <div class="h-2.5 w-full bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-orange-400 to-orange-600 rounded-full transition-all" style="width: <?= $persen ?>%"></div>
                    </div>
                    <p class="text-[10px] text-slate-400">Total belanja: Rp <?= number_format($ks['total_belanja'], 0, ',', '.') ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-10 text-slate-300">
                <span class="material-symbols-outlined text-5xl">school</span>
                <p class="mt-3 font-bold text-slate-400">Belum ada data pembeli</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Statistik Aktivitas -->
        <div class="bg-white rounded-2xl p-4 md:p-6 shadow-sm border border-slate-50">
            <h3 class="font-extrabold text-[#003049] text-lg flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-primary-orange">timeline</span>
                Aktivitas
            </h3>
            <div class="space-y-4">
                <div class="bg-gradient-to-br from-blue-50 to-white rounded-2xl p-4 border border-blue-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-blue-500 uppercase tracking-wider">Pembeli Baru Bulan Ini</p>
                            <h4 class="text-2xl font-extrabold text-[#2a2a2a]"><?= number_format($pembeli_baru_bulan) ?></h4>
                        </div>
                        <span class="material-symbols-outlined text-5xl text-blue-200">person_add</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-2xl p-4 md:p-6 mb-6 shadow-sm border border-slate-50">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Kelas</label>
                <select name="kelas" class="w-full px-4 py-3 rounded-xl border border-slate-100 bg-slate-50 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary-orange/20">
                    <option value="">Semua Kelas</option>
                    <option value="10" <?= $filterKelas == '10' ? 'selected' : '' ?>>Kelas 10</option>
                    <option value="11" <?= $filterKelas == '11' ? 'selected' : '' ?>>Kelas 11</option>
                    <option value="12" <?= $filterKelas == '12' ? 'selected' : '' ?>>Kelas 12</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-primary-orange text-white px-6 py-3 rounded-xl font-bold shadow-lg hover:scale-105 transition-all text-sm flex-1">
                    <span class="material-symbols-outlined text-sm mr-1">search</span> Filter
                </button>
                <a href="laporan_pembeli.php" class="bg-slate-200 text-slate-600 px-4 py-3 rounded-xl font-bold hover:bg-slate-300 transition-all text-sm">
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
                Data Pembeli
                <span class="bg-orange-100 text-orange-600 px-3 py-1 rounded-xl text-xs font-bold ml-2"><?= $query_filtered ? mysqli_num_rows($query_filtered) : 0 ?></span>
            </h3>
        </div>

        <div class="table-container">
            <table class="w-full mobile-card">
                <thead class="bg-slate-50 text-xs font-black text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-4 text-left">Pembeli</th>
                        <th class="px-4 py-4 text-left hidden md:table-cell">Email</th>
                        <th class="px-4 py-4 text-left hidden sm:table-cell">Kelas</th>
                        <th class="px-4 py-4 text-left">Total Pesanan</th>
                        <th class="px-4 py-4 text-left">Total Belanja</th>
                        <th class="px-4 py-4 text-left hidden lg:table-cell">Pesanan Terakhir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if ($query_filtered && mysqli_num_rows($query_filtered) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($query_filtered)): ?>
                        <tr class="hover:bg-orange-50/50 transition-colors">
                            <td class="px-4 py-4" data-label="Pembeli">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white text-xs font-bold uppercase shrink-0">
                                        <?= substr($row['username'] ?? 'U', 0, 1) ?>
                                    </div>
                                    <span class="font-semibold text-sm"><?= htmlspecialchars($row['username'] ?? 'Unknown') ?></span>
                                </div>
                            </td>
                            <td class="px-4 py-4 hidden md:table-cell" data-label="Email">
                                <span class="text-sm text-slate-600"><?= htmlspecialchars($row['email'] ?? '-') ?></span>
                            </td>
                            <td class="px-4 py-4 hidden sm:table-cell" data-label="Kelas">
                                <span class="inline-block bg-blue-100 text-blue-700 px-3 py-1 rounded-lg text-xs font-bold">
                                    <?= $row['kelas'] ? 'Kelas ' . htmlspecialchars($row['kelas']) : 'N/A' ?>
                                </span>
                            </td>
                            <td class="px-4 py-4" data-label="Total Pesanan">
                                <span class="font-bold text-slate-800"><?= (int)$row['total_pesanan'] ?> pesanan</span>
                            </td>
                            <td class="px-4 py-4" data-label="Total Belanja">
                                <span class="font-bold text-primary-orange">Rp <?= number_format($row['total_belanja'], 0, ',', '.') ?></span>
                            </td>
                            <td class="px-4 py-4 hidden lg:table-cell" data-label="Pesanan Terakhir">
                                <div>
                                    <p class="text-sm font-semibold"><?= $row['pesanan_terakhir'] ? date('d M Y', strtotime($row['pesanan_terakhir'])) : 'Belum ada' ?></p>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center">
                                <div class="flex flex-col items-center text-slate-300">
                                    <span class="material-symbols-outlined text-5xl">people</span>
                                    <p class="mt-3 font-bold text-slate-400">Belum ada data pembeli</p>
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