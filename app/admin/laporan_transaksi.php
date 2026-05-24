<?php
session_start();
include(__DIR__ . '/../../config/config.php');
<<<<<<< HEAD
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
	header('Location: ../auth/login.php');
	exit();
}
?>
<!doctype html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Laporan Transaksi</title>
	<script src="https://cdn.tailwindcss.com"></script>
	<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
	<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
	<style>body{font-family:'Plus Jakarta Sans',sans-serif}</style>
</head>
<body class="flex">
	<?php include __DIR__ . '/../../includes/sidebar_admin.php'; ?>

	<main class="flex-1 p-8">
		<h1 class="text-2xl font-bold">Laporan Transaksi</h1>
		<p class="mt-4 text-slate-600">Halaman laporan transaksi (placeholder).</p>
	</main>
=======

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Ambil data transaksi
$query_transaksi = mysqli_query($koneksi, "
    SELECT t.*, u.username, u.email,
           k.nama_kantin,
           COUNT(dp.id_detail) as total_item
    FROM transaksi t
    LEFT JOIN users u ON t.id_user = u.id_user
    LEFT JOIN kantin k ON t.id_kantin = k.id_kantin
    LEFT JOIN detail_transaksi dt ON t.id_transaksi = dt.id_transaksi
    LEFT JOIN detail_pesanan dp ON dp.id_pesanan IN (SELECT id_pesanan FROM pesanan WHERE id_user = t.id_user)
    GROUP BY t.id_transaksi
    ORDER BY t.tanggal DESC
    LIMIT 100
");

// Statistik
$total_transaksi = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM transaksi"))['total'] ?? 0;
$total_pendapatan = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COALESCE(SUM(total_harga),0) as total FROM transaksi WHERE status='Selesai'"))['total'] ?? 0;
$transaksi_pending = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM transaksi WHERE status='Pending'"))['total'] ?? 0;
$transaksi_selesai = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM transaksi WHERE status='Selesai'"))['total'] ?? 0;

// Filter
$filter_status = $_GET['status'] ?? '';
$filterTanggal = $_GET['tanggal'] ?? '';

$where = [];
if ($filter_status) {
    $safe_status = mysqli_real_escape_string($koneksi, $filter_status);
    $where[] = "t.status = '$safe_status'";
}
if ($filterTanggal) {
    $safe_tanggal = mysqli_real_escape_string($koneksi, $filterTanggal);
    $where[] = "DATE(t.tanggal) = '$safe_tanggal'";
}

$where_sql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

$query_filtered = mysqli_query($koneksi, "
    SELECT t.*, u.username, u.email, k.nama_kantin
    FROM transaksi t
    LEFT JOIN users u ON t.id_user = u.id_user
    LEFT JOIN kantin k ON t.id_kantin = k.id_kantin
    $where_sql
    ORDER BY t.tanggal DESC
    LIMIT 100
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Laporan Transaksi - Kantin Kita</title>
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
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: radial-gradient(circle at top left, rgba(251,146,60,.20), transparent 32%), radial-gradient(circle at 80% 20%, rgba(255,183,3,.12), transparent 25%), linear-gradient(180deg,#fff7f1 0%,#fff2e7 38%,#fff9f3 100%); }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #FF8C20; border-radius: 10px; }
        .glow-card { box-shadow: 0 25px 80px rgba(251,146,60,0.16); }
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
<body class="text-slate-800 flex min-h-screen">

<?php include '../../includes/sidebar_admin.php'; ?>

<main class="flex-1 w-full lg:ml-72 p-4 md:p-6 lg:p-8">
    <!-- Header -->
    <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-8 mt-14 lg:mt-0">
        <div>
            <h2 class="text-2xl md:text-3xl font-extrabold text-[#2a2a2a] tracking-tight">Laporan Transaksi</h2>
            <p class="text-orange-700 font-semibold mt-1 text-sm md:text-base">Kelola dan lihat semua transaksi</p>
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
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-2xl bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center text-white shrink-0">
                    <span class="material-symbols-outlined text-xl md:text-2xl">check_circle</span>
                </div>
                <div>
                    <p class="text-[10px] font-black text-green-500 uppercase tracking-widest">Selesai</p>
                    <h3 class="text-lg md:text-xl font-extrabold text-[#2a2a2a]"><?= number_format($transaksi_selesai) ?></h3>
                </div>
            </div>
        </div>
        <div class="bg-white/90 backdrop-blur-xl p-4 md:p-6 rounded-2xl border border-orange-100 shadow-glow-card">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-2xl bg-gradient-to-br from-yellow-400 to-yellow-600 flex items-center justify-center text-white shrink-0">
                    <span class="material-symbols-outlined text-xl md:text-2xl">pending</span>
                </div>
                <div>
                    <p class="text-[10px] font-black text-yellow-500 uppercase tracking-widest">Pending</p>
                    <h3 class="text-lg md:text-xl font-extrabold text-[#2a2a2a]"><?= number_format($transaksi_pending) ?></h3>
                </div>
            </div>
        </div>
        <div class="bg-white/90 backdrop-blur-xl p-4 md:p-6 rounded-2xl border border-orange-100 shadow-glow-card">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-2xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white shrink-0">
                    <span class="material-symbols-outlined text-xl md:text-2xl">payments</span>
                </div>
                <div>
                    <p class="text-[10px] font-black text-blue-500 uppercase tracking-widest">Total Pendapatan</p>
                    <h3 class="text-sm md:text-lg font-extrabold text-[#2a2a2a]">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-2xl p-4 md:p-6 mb-6 shadow-sm border border-slate-50">
        <form method="GET" class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Status</label>
                <select name="status" class="w-full px-4 py-3 rounded-xl border border-slate-100 bg-slate-50 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary-orange/20">
                    <option value="">Semua Status</option>
                    <option value="Pending" <?= $filter_status == 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Diproses" <?= $filter_status == 'Diproses' ? 'selected' : '' ?>>Diproses</option>
                    <option value="Selesai" <?= $filter_status == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                    <option value="Dibatalkan" <?= $filter_status == 'Dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Tanggal</label>
                <input type="date" name="tanggal" value="<?= htmlspecialchars($filterTanggal) ?>" class="w-full px-4 py-3 rounded-xl border border-slate-100 bg-slate-50 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary-orange/20">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-primary-orange text-white px-6 py-3 rounded-xl font-bold shadow-lg hover:scale-105 transition-all text-sm whitespace-nowrap">
                    <span class="material-symbols-outlined text-sm mr-1">search</span> Filter
                </button>
                <a href="laporan_transaksi.php" class="bg-slate-200 text-slate-600 px-4 py-3 rounded-xl font-bold hover:bg-slate-300 transition-all text-sm">
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
                Data Transaksi
                <span class="bg-orange-100 text-orange-600 px-3 py-1 rounded-xl text-xs font-bold ml-2"><?= mysqli_num_rows($query_filtered) ?></span>
            </h3>
        </div>

        <div class="table-container">
            <table class="w-full mobile-card">
                <thead class="bg-slate-50 text-xs font-black text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-4 text-left">ID</th>
                        <th class="px-4 py-4 text-left">Pengguna</th>
                        <th class="px-4 py-4 text-left hidden sm:table-cell">Kantin</th>
                        <th class="px-4 py-4 text-left">Tanggal</th>
                        <th class="px-4 py-4 text-left">Total</th>
                        <th class="px-4 py-4 text-left">Status</th>
                        <th class="px-4 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (mysqli_num_rows($query_filtered) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($query_filtered)):
                            $statusClass = match($row['status']) {
                                'Selesai' => 'bg-green-100 text-green-700',
                                'Diproses' => 'bg-blue-100 text-blue-700',
                                'Pending' => 'bg-yellow-100 text-yellow-700',
                                'Dibatalkan' => 'bg-red-100 text-red-700',
                                default => 'bg-slate-100 text-slate-700'
                            };
                        ?>
                        <tr class="hover:bg-orange-50/50 transition-colors">
                            <td class="px-4 py-4" data-label="ID">#<?= $row['id_transaksi'] ?></td>
                            <td class="px-4 py-4" data-label="Pengguna">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white text-xs font-bold uppercase shrink-0">
                                        <?= substr($row['username'] ?? 'U', 0, 1) ?>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-bold text-sm truncate"><?= htmlspecialchars($row['username'] ?? 'Unknown') ?></p>
                                        <p class="text-xs text-slate-400 truncate"><?= htmlspecialchars($row['email'] ?? '-') ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 hidden sm:table-cell" data-label="Kantin"><?= htmlspecialchars($row['nama_kantin'] ?? '-') ?></td>
                            <td class="px-4 py-4" data-label="Tanggal">
                                <div>
                                    <p class="text-sm font-semibold"><?= date('d M Y', strtotime($row['tanggal'])) ?></p>
                                    <p class="text-xs text-slate-400"><?= date('H:i', strtotime($row['tanggal'])) ?></p>
                                </div>
                            </td>
                            <td class="px-4 py-4" data-label="Total">
                                <span class="font-bold text-primary-orange">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></span>
                            </td>
                            <td class="px-4 py-4" data-label="Status">
                                <span class="px-3 py-1.5 rounded-lg text-xs font-bold <?= $statusClass ?>">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center" data-label="Aksi">
                                <a href="detail_transaksi.php?id=<?= $row['id_transaksi'] ?>" class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-orange-100 text-primary-orange hover:bg-primary-orange hover:text-white transition-all">
                                    <span class="material-symbols-outlined text-sm">visibility</span>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center">
                                <div class="flex flex-col items-center text-slate-300">
                                    <span class="material-symbols-outlined text-5xl">receipt_long</span>
                                    <p class="mt-3 font-bold text-slate-400">Belum ada transaksi</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

>>>>>>> b27740e (Refactor kantin owner relation and sync penjual/kantin fields)
</body>
</html>