<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Ambil data penjualan dari pesanan
$query_pesanan = mysqli_query($koneksi, "
    SELECT p.*, u.username, u.email, k.nama_kantin,
           dp.nama_menu, dp.qty as jumlah_item, dp.harga, dp.subtotal,
           dp.opsi_pilihan, dp.catatan
    FROM pesanan p
    LEFT JOIN users u ON p.id_user = u.id_user
    LEFT JOIN kantin k ON p.id_kantin = k.id_kantin
    LEFT JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    WHERE p.status IN ('Selesai', 'Siap Diambil')
    ORDER BY p.tanggal DESC
    LIMIT 200
");

// Statistik penjualan
$total_pesanan = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pesanan WHERE status IN ('Selesai', 'Siap Diambil')"))['total'] ?? 0;
$total_pendapatan = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COALESCE(SUM(total_harga),0) as total FROM pesanan WHERE status IN ('Selesai', 'Siap Diambil')"))['total'] ?? 0;
$pesanan_bulan_ini = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pesanan WHERE MONTH(tanggal) = MONTH(NOW()) AND YEAR(tanggal) = YEAR(NOW()) AND status IN ('Selesai', 'Siap Diambil')"))['total'] ?? 0;
$pendapatan_bulan_ini = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COALESCE(SUM(total_harga),0) as total FROM pesanan WHERE MONTH(tanggal) = MONTH(NOW()) AND YEAR(tanggal) = YEAR(NOW()) AND status IN ('Selesai', 'Siap Diambil')"))['total'] ?? 0;

// Top selling items
$top_menu = mysqli_query($koneksi, "
    SELECT m.nama_menu, m.harga, m.foto, k.nama_kantin,
           COALESCE(SUM(dp.qty), 0) as total_terjual,
           COALESCE(AVG(rm.nilai_rating), 0) as avg_rating,
           COUNT(DISTINCT rm.id_rating) as jml_rating
    FROM menu m
    JOIN kantin k ON m.id_kantin = k.id_kantin
    LEFT JOIN detail_pesanan dp ON m.id_menu = dp.id_menu
    LEFT JOIN pesanan p ON dp.id_pesanan = p.id_pesanan AND p.status IN ('Selesai', 'Siap Diambil')
    LEFT JOIN rating_menu rm ON m.id_menu = rm.id_menu
    GROUP BY m.id_menu
    ORDER BY total_terjual DESC
    LIMIT 5
");

// Penjualan per kategori
$kategori_stats = [];
$kat_result = mysqli_query($koneksi, "
    SELECT m.kategori, COALESCE(SUM(dp.qty), 0) as jumlah, COALESCE(SUM(dp.subtotal), 0) as total
    FROM detail_pesanan dp
    JOIN menu m ON dp.id_menu = m.id_menu
    JOIN pesanan p ON dp.id_pesanan = p.id_pesanan
    WHERE p.status IN ('Selesai', 'Siap Diambil')
    GROUP BY m.kategori
");
while ($kat = mysqli_fetch_assoc($kat_result)) {
    $kategori_stats[] = $kat;
}

// Filter
$filterTanggal = $_GET['tanggal'] ?? '';
$filterKantin = $_GET['kantin'] ?? '';
$filterKategori = $_GET['kategori'] ?? '';

$where = ["p.status IN ('Selesai', 'Siap Diambil')"];
if ($filterTanggal) {
    $safe_tanggal = mysqli_real_escape_string($koneksi, $filterTanggal);
    $where[] = "DATE(p.tanggal) = '$safe_tanggal'";
}
if ($filterKantin) {
    $safe_kantin = mysqli_real_escape_string($koneksi, $filterKantin);
    $where[] = "p.id_kantin = '$safe_kantin'";
}
if ($filterKategori) {
    $safe_kat = mysqli_real_escape_string($koneksi, $filterKategori);
    $where[] = "m.kategori = '$safe_kat'";
}

$where_sql = implode(' AND ', $where);

$query_filtered = mysqli_query($koneksi, "
    SELECT p.*, u.username, u.email, k.nama_kantin,
           dp.nama_menu, dp.qty, dp.harga, dp.subtotal,
           dp.opsi_pilihan, dp.catatan, m.kategori
    FROM pesanan p
    LEFT JOIN users u ON p.id_user = u.id_user
    LEFT JOIN kantin k ON p.id_kantin = k.id_kantin
    LEFT JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
    LEFT JOIN menu m ON dp.id_menu = m.id_menu
    WHERE $where_sql
    ORDER BY p.tanggal DESC
    LIMIT 200
");

// Daftar kantin untuk filter
$daftar_kantin = mysqli_query($koneksi, "SELECT id_kantin, nama_kantin FROM kantin ORDER BY nama_kantin");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Laporan Penjualan - Kantin Kita</title>
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
<body class="text-slate-800 flex min-h-screen">

<?php include '../../includes/sidebar_admin.php'; ?>

<main class="flex-1 w-full lg:ml-72 p-4 md:p-6 lg:p-8">
    <!-- Header -->
    <header class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 mb-8 mt-14 lg:mt-0">
        <div>
            <h2 class="text-2xl md:text-3xl font-extrabold text-[#2a2a2a] tracking-tight">Laporan Penjualan</h2>
            <p class="text-orange-700 font-semibold mt-1 text-sm md:text-base">Analisis performa penjualan kantin</p>
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
                    <span class="material-symbols-outlined text-xl md:text-2xl">shopping_bag</span>
                </div>
                <div>
                    <p class="text-[10px] font-black text-orange-500 uppercase tracking-widest">Total Pesanan</p>
                    <h3 class="text-lg md:text-xl font-extrabold text-[#2a2a2a]"><?= number_format($total_pesanan) ?></h3>
                </div>
            </div>
        </div>
        <div class="bg-white/90 backdrop-blur-xl p-4 md:p-6 rounded-2xl border border-orange-100 shadow-glow-card">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-2xl bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center text-white shrink-0">
                    <span class="material-symbols-outlined text-xl md:text-2xl">payments</span>
                </div>
                <div>
                    <p class="text-[10px] font-black text-green-500 uppercase tracking-widest">Total Pendapatan</p>
                    <h3 class="text-sm md:text-lg font-extrabold text-[#2a2a2a]">Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>
        <div class="bg-white/90 backdrop-blur-xl p-4 md:p-6 rounded-2xl border border-orange-100 shadow-glow-card">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-2xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white shrink-0">
                    <span class="material-symbols-outlined text-xl md:text-2xl">calendar_month</span>
                </div>
                <div>
                    <p class="text-[10px] font-black text-blue-500 uppercase tracking-widest">Pesanan Bulan Ini</p>
                    <h3 class="text-lg md:text-xl font-extrabold text-[#2a2a2a]"><?= number_format($pesanan_bulan_ini) ?></h3>
                </div>
            </div>
        </div>
        <div class="bg-white/90 backdrop-blur-xl p-4 md:p-6 rounded-2xl border border-orange-100 shadow-glow-card">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 rounded-2xl bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center text-white shrink-0">
                    <span class="material-symbols-outlined text-xl md:text-2xl">savings</span>
                </div>
                <div>
                    <p class="text-[10px] font-black text-purple-500 uppercase tracking-widest">Pendapatan Bulan Ini</p>
                    <h3 class="text-sm md:text-lg font-extrabold text-[#2a2a2a]">Rp <?= number_format($pendapatan_bulan_ini, 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Menu & Kategori -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Top Selling -->
        <div class="lg:col-span-2 bg-white rounded-2xl p-4 md:p-6 shadow-sm border border-slate-50">
            <h3 class="font-extrabold text-[#003049] text-lg flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-primary-orange">local_fire_department</span>
                Menu Terlaris
            </h3>
            <?php if (mysqli_num_rows($top_menu) > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
                <?php $rank = 1; while ($menu = mysqli_fetch_assoc($top_menu)):
                    $foto = !empty($menu['foto']) && file_exists("../../uploads/{$menu['foto']}") ? "../../uploads/{$menu['foto']}" : "../../public/assets/img/default-food.svg";
                    $rating = round((float)$menu['avg_rating'], 1);
                ?>
                <div class="bg-gradient-to-br from-orange-50 to-white rounded-2xl p-4 border border-orange-100 relative hover:shadow-lg transition-all">
                    <?php if ($rank <= 3): ?>
                    <div class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-gradient-to-br <?= $rank == 1 ? 'from-yellow-400 to-orange-500' : ($rank == 2 ? 'from-slate-300 to-slate-400' : 'from-amber-600 to-amber-700') ?> flex items-center justify-center text-white font-black text-xs shadow-lg">
                        <?= $rank ?>
                    </div>
                    <?php endif; ?>
                    <img src="<?= $foto ?>" class="w-full aspect-square rounded-xl object-cover bg-orange-50 mb-3" alt="<?= htmlspecialchars($menu['nama_menu']) ?>">
                    <p class="text-[10px] font-bold text-slate-400 truncate"><?= htmlspecialchars($menu['nama_kantin']) ?></p>
                    <p class="font-bold text-sm text-slate-800 truncate"><?= htmlspecialchars($menu['nama_menu']) ?></p>
                    <p class="text-primary-orange font-black text-sm mt-1">Rp <?= number_format($menu['harga'], 0, ',', '.') ?></p>
                    <div class="flex items-center justify-between mt-2 text-[10px]">
                        <span class="text-orange-500 font-bold"><i class="fa-solid fa-fire mr-1"></i><?= (int)$menu['total_terjual'] ?></span>
                        <span class="text-yellow-500 font-bold"><i class="fa-solid fa-star mr-1"></i><?= $rating > 0 ? $rating : '-' ?></span>
                    </div>
                </div>
                <?php $rank++; endwhile; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-10 text-slate-300">
                <span class="material-symbols-outlined text-5xl">restaurant</span>
                <p class="mt-3 font-bold text-slate-400">Belum ada data penjualan</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Kategori -->
        <div class="bg-white rounded-2xl p-4 md:p-6 shadow-sm border border-slate-50">
            <h3 class="font-extrabold text-[#003049] text-lg flex items-center gap-2 mb-4">
                <span class="material-symbols-outlined text-primary-orange">category</span>
                Per Kategori
            </h3>
            <?php if (count($kategori_stats) > 0): ?>
            <div class="space-y-4">
                <?php
                $total_kategori = array_sum(array_column($kategori_stats, 'jumlah'));
                $colors = ['Makanan' => 'from-orange-400 to-orange-600', 'Minuman' => 'from-blue-400 to-blue-600', 'Camilan' => 'from-purple-400 to-purple-600'];
                foreach ($kategori_stats as $kat):
                    $persen = $total_kategori > 0 ? ($kat['jumlah'] / $total_kategori) * 100 : 0;
                ?>
                <div class="space-y-2">
                    <div class="flex justify-between text-xs font-bold">
                        <span class="text-slate-600"><?= htmlspecialchars($kat['kategori']) ?></span>
                        <span class="text-slate-800"><?= (int)$kat['jumlah'] ?> item (<?= round($persen) ?>%)</span>
                    </div>
                    <div class="h-2.5 w-full bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r <?= $colors[$kat['kategori']] ?? 'from-slate-400 to-slate-500' ?> rounded-full transition-all" style="width: <?= $persen ?>%"></div>
                    </div>
                    <p class="text-[10px] text-slate-400">Rp <?= number_format($kat['total'], 0, ',', '.') ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-10 text-slate-300">
                <span class="material-symbols-outlined text-5xl">category</span>
                <p class="mt-3 font-bold text-slate-400">Belum ada data</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-2xl p-4 md:p-6 mb-6 shadow-sm border border-slate-50">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Tanggal</label>
                <input type="date" name="tanggal" value="<?= htmlspecialchars($filterTanggal) ?>" class="w-full px-4 py-3 rounded-xl border border-slate-100 bg-slate-50 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary-orange/20">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Kantin</label>
                <select name="kantin" class="w-full px-4 py-3 rounded-xl border border-slate-100 bg-slate-50 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary-orange/20">
                    <option value="">Semua Kantin</option>
                    <?php while ($k = mysqli_fetch_assoc($daftar_kantin)): ?>
                    <option value="<?= $k['id_kantin'] ?>" <?= $filterKantin == $k['id_kantin'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kantin']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Kategori</label>
                <select name="kategori" class="w-full px-4 py-3 rounded-xl border border-slate-100 bg-slate-50 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary-orange/20">
                    <option value="">Semua</option>
                    <option value="Makanan" <?= $filterKategori == 'Makanan' ? 'selected' : '' ?>>Makanan</option>
                    <option value="Minuman" <?= $filterKategori == 'Minuman' ? 'selected' : '' ?>>Minuman</option>
                    <option value="Camilan" <?= $filterKategori == 'Camilan' ? 'selected' : '' ?>>Camilan</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-primary-orange text-white px-6 py-3 rounded-xl font-bold shadow-lg hover:scale-105 transition-all text-sm flex-1">
                    <span class="material-symbols-outlined text-sm mr-1">search</span> Filter
                </button>
                <a href="laporan_penjualan.php" class="bg-slate-200 text-slate-600 px-4 py-3 rounded-xl font-bold hover:bg-slate-300 transition-all text-sm">
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
                Detail Penjualan
                <span class="bg-orange-100 text-orange-600 px-3 py-1 rounded-xl text-xs font-bold ml-2"><?= mysqli_num_rows($query_filtered) ?></span>
            </h3>
        </div>

        <div class="table-container">
            <table class="w-full mobile-card">
                <thead class="bg-slate-50 text-xs font-black text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-4 text-left">ID Pesanan</th>
                        <th class="px-4 py-4 text-left">Pengguna</th>
                        <th class="px-4 py-4 text-left hidden md:table-cell">Menu</th>
                        <th class="px-4 py-4 text-left hidden sm:table-cell">Kantin</th>
                        <th class="px-4 py-4 text-left">Tanggal</th>
                        <th class="px-4 py-4 text-left">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (mysqli_num_rows($query_filtered) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($query_filtered)): ?>
                        <tr class="hover:bg-orange-50/50 transition-colors">
                            <td class="px-4 py-4" data-label="ID Pesanan">
                                <span class="font-mono font-bold text-sm">#<?= $row['id_pesanan'] ?></span>
                            </td>
                            <td class="px-4 py-4" data-label="Pengguna">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white text-xs font-bold uppercase shrink-0">
                                        <?= substr($row['username'] ?? 'U', 0, 1) ?>
                                    </div>
                                    <span class="font-semibold text-sm"><?= htmlspecialchars($row['username'] ?? 'Unknown') ?></span>
                                </div>
                            </td>
                            <td class="px-4 py-4 hidden md:table-cell" data-label="Menu">
                                <div>
                                    <p class="font-semibold text-sm"><?= htmlspecialchars($row['nama_menu'] ?? '-') ?></p>
                                    <p class="text-xs text-slate-400"><?= (int)($row['qty'] ?? 0) ?> x Rp <?= number_format($row['harga'] ?? 0, 0, ',', '.') ?></p>
                                </div>
                            </td>
                            <td class="px-4 py-4 hidden sm:table-cell" data-label="Kantin">
                                <span class="text-sm"><?= htmlspecialchars($row['nama_kantin'] ?? '-') ?></span>
                            </td>
                            <td class="px-4 py-4" data-label="Tanggal">
                                <div>
                                    <p class="text-sm font-semibold"><?= date('d M Y', strtotime($row['tanggal'])) ?></p>
                                    <p class="text-xs text-slate-400"><?= date('H:i', strtotime($row['tanggal'])) ?></p>
                                </div>
                            </td>
                            <td class="px-4 py-4" data-label="Total">
                                <span class="font-bold text-primary-orange">Rp <?= number_format($row['subtotal'] ?? $row['total_harga'], 0, ',', '.') ?></span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center">
                                <div class="flex flex-col items-center text-slate-300">
                                    <span class="material-symbols-outlined text-5xl">shopping_bag</span>
                                    <p class="mt-3 font-bold text-slate-400">Belum ada data penjualan</p>
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