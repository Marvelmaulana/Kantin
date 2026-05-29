<?php
session_start();
include(__DIR__ . '/../../config/config.php');

// 1. PROTEKSI HALAMAN
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$message = '';
$message_type = 'success';
if (isset($_GET['success']) && $_GET['success'] === 'hapus') {
    $message = 'Penjual berhasil dihapus.';
} elseif (isset($_GET['error'])) {
    $message = urldecode($_GET['error']);
    $message_type = 'error';
}

$search = mysqli_real_escape_string($koneksi, trim($_GET['search'] ?? ''));
$sql = "SELECT u.*, k.id_kantin FROM users u 
        LEFT JOIN kantin k ON u.id_user = k.id_penjual
        WHERE u.role='penjual'";
if ($search !== '') {
    $sql .= " AND (u.username LIKE '%$search%' OR u.email LIKE '%$search%')";
}
$sql .= " ORDER BY k.id_kantin ASC";
$query = mysqli_query($koneksi, $sql);
if (!$query) {
    die("Query Error: " . mysqli_error($koneksi));
}
$total_penjual = mysqli_num_rows($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Manajemen Penjual - Kantin Kita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'bg-soft': '#FFF9F8',
                        'primary-orange': '#E25E3E',
                        'accent-blue': '#2D9CDB',
                        'accent-green': '#27AE60'
                    },
                    borderRadius: { '4xl': '2.5rem' }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #FFF9F8; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #E25E3E; border-radius: 10px; }
    </style>
</head>
<body class="text-slate-800 flex overflow-x-hidden">

<?php include '../../includes/sidebar_admin.php'; ?>

<main class="flex-1 w-full lg:ml-72 p-4 md:p-10 min-h-screen">
    <header class="flex flex-col gap-6 mb-10 mt-14 lg:mt-0">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center gap-4">
                <a href="dashboard_admin.php" class="hidden md:flex w-12 h-12 rounded-2xl bg-white border border-slate-100 items-center justify-center text-slate-400 hover:text-primary-orange transition-all shadow-sm">
                    <span class="material-symbols-outlined">arrow_back</span>
                </a>
                <div>
                    <h2 class="text-3xl font-extrabold text-[#003049]">Manajemen Penjual</h2>
                    <p class="text-slate-400 font-medium text-sm">Kelola mitra penjual dan stand di platform.</p>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full sm:w-auto">
                <form method="GET" class="relative w-full sm:w-[320px]">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xl">search</span>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari penjual atau email..." class="pl-12 pr-4 py-3 w-full rounded-2xl border border-slate-200 focus:border-primary-orange focus:ring-primary-orange/20" />
                </form>
                <a href="tambah_penjual.php" class="bg-primary-orange text-white px-5 py-3 rounded-2xl font-bold shadow-lg flex items-center justify-center gap-2 hover:bg-orange-600 transition-all w-full sm:w-auto">
                    <span class="material-symbols-outlined">person_add</span> Tambah Penjual
                </a>
            </div>
        </div>
    </header>

    <?php if ($message !== ''): ?>
    <div class="mb-6 px-5 py-4 rounded-2xl border <?= $message_type==='success' ? 'bg-green-50 border-green-100 text-green-700' : 'bg-red-50 border-red-100 text-red-700' ?> font-bold text-sm">
        <?= $message ?>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-4xl shadow-sm border border-slate-50 overflow-hidden">
        <div class="p-8 border-b border-slate-50 flex flex-col sm:flex-row justify-between items-center gap-4 bg-white/50 backdrop-blur-md sticky left-0">
            <h3 class="font-bold text-[#003049] flex items-center gap-2"><span class="material-symbols-outlined text-accent-blue">storefront</span> Daftar Penjual</h3>
            <div class="flex items-center gap-2 px-4 py-2 bg-green-50 rounded-xl">
                <span class="w-2 h-2 rounded-full bg-accent-green animate-pulse"></span>
                <span class="text-[10px] font-black text-accent-green uppercase tracking-widest"><?= $total_penjual ?> Penjual Aktif</span>
            </div>
        </div>

        <div class="overflow-x-auto overflow-y-hidden">
            <table class="w-full text-left border-collapse min-w-[800px]">
                <thead>
                    <tr class="text-[10px] uppercase tracking-[0.15em] text-slate-400 font-black bg-slate-50/50">
                        <th class="py-6 px-8 text-center">No. Stand</th>
                        <th class="py-6 px-8">Informasi Penjual</th>
                        <th class="py-6 px-8">Kontak Merchant</th>
                        <th class="py-6 px-8 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php while ($penjual = mysqli_fetch_assoc($query)): ?>
                    <tr class="group hover:bg-bg-soft transition-all">
                        <td class="py-6 px-8 text-center">
                            <span class="inline-block px-4 py-1.5 bg-slate-100 text-slate-600 text-[11px] font-black rounded-xl border border-slate-200">
                                #STN-0<?= $penjual['id_kantin'] ?: '00' ?>
                            </span>
                        </td>
                        <td class="py-6 px-8">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl bg-orange-50 flex items-center justify-center text-primary-orange shadow-inner border border-orange-100 group-hover:rotate-6 transition-transform">
                                    <span class="material-symbols-outlined text-2xl font-bold">store</span>
                                </div>
                                <div>
                                    <p class="font-bold text-[#003049] text-base group-hover:text-primary-orange transition-colors"><?= htmlspecialchars($penjual['username']) ?></p>
                                    <div class="flex items-center gap-1 mt-0.5">
                                        <span class="material-symbols-outlined text-[12px] text-accent-blue">verified</span>
                                        <span class="text-[9px] text-accent-blue font-black uppercase tracking-tighter">Official Merchant</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="py-6 px-8">
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[16px] text-slate-400">mail</span>
                                    <span class="text-sm font-semibold text-slate-600"><?= htmlspecialchars($penjual['email']) ?></span>
                                </div>
                                <span class="text-[10px] text-slate-400 font-medium ml-6">Aktif sejak: <?= date('d M Y') ?></span>
                            </div>
                        </td>
                        <td class="py-6 px-8">
                            <div class="flex flex-wrap justify-center gap-2">
                                <a href="edit_penjual.php?id=<?= $penjual['id_user'] ?>" class="px-3 py-2 rounded-2xl bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-primary-orange hover:text-white transition">Edit</a>
                                <a href="proses_hapus_penjual.php?id=<?= $penjual['id_user'] ?>" onclick="return confirm('Hapus mitra ini? Semua menu yang terdaftar di stand ini juga akan ikut terhapus!')" class="px-3 py-2 rounded-2xl bg-red-50 text-red-700 text-xs font-semibold hover:bg-red-600 hover:text-white transition">Hapus</a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>

                    <?php if ($total_penjual === 0): ?>
                    <tr>
                        <td colspan="4" class="py-32 text-center bg-slate-50/20">
                            <div class="flex flex-col items-center">
                                <div class="w-24 h-24 bg-white rounded-4xl flex items-center justify-center shadow-sm mb-6 border border-slate-100">
                                    <span class="material-symbols-outlined text-5xl text-slate-200">storefront_off</span>
                                </div>
                                <h4 class="text-slate-800 font-bold text-lg">Belum Ada Penjual</h4>
                                <p class="text-slate-400 text-sm mt-1">Tambahkan penjual baru untuk mengisi stand dan menu.</p>
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
