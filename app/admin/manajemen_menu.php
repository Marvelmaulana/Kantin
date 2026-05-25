<?php
session_start();
include(__DIR__ . '/../../config/config.php');

// 1. PROTEKSI HALAMAN
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// 2. AMBIL DATA MENU
$message = '';
$message_type = 'success';
if (isset($_GET['success']) && $_GET['success'] === 'hapus') {
    $message = 'Menu berhasil dihapus.';
} elseif (isset($_GET['error'])) {
    $message = urldecode($_GET['error']);
    $message_type = 'error';
}

$sql = "SELECT menu.*, kantin.nama_kantin 
        FROM menu 
        JOIN kantin ON menu.id_kantin = kantin.id_kantin 
        ORDER BY menu.id_menu DESC";
$query = mysqli_query($koneksi, $sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Manajemen Menu - Kantin Kita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet"/>
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
        .material-symbols-outlined {
            font-size: 24px;
            display: inline-block;
            line-height: 1;
        }
    </style>
</head>
<body class="text-slate-800 flex overflow-x-hidden">

<?php include '../../includes/sidebar_admin.php'; ?>

<main class="flex-1 lg:ml-72 p-4 md:p-10 min-h-screen">
    <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-10 mt-14 lg:mt-0">
        <div class="flex items-center gap-4">
            <a href="dashboard_admin.php" class="hidden md:flex w-12 h-12 rounded-2xl bg-white border border-slate-100 items-center justify-center text-slate-400 hover:text-primary-orange transition-all shadow-sm">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <div>
                <h2 class="text-3xl font-extrabold text-[#003049]">Manajemen Menu</h2>
                <p class="text-slate-400 font-medium text-sm">Monitor semua produk kuliner.</p>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            <div class="bg-white px-5 py-3 rounded-2xl border border-slate-100 flex items-center gap-3 shadow-sm text-sm font-bold text-slate-500">
                <span class="material-symbols-outlined text-primary-orange">restaurant</span> 
                <span class="text-primary-orange"><?= mysqli_num_rows($query) ?></span> Produk Aktif
            </div>
            <a href="tambah_menu.php" class="bg-primary-orange text-white px-5 py-3 rounded-2xl font-bold shadow-lg flex items-center gap-2 hover:scale-105 transition-all text-sm whitespace-nowrap">Tambah Menu</a>
        </div>
    </header>
    <?php if ($message !== ''): ?>
    <div class="mb-6 px-5 py-4 rounded-2xl border <?= $message_type==='success' ? 'bg-green-50 border-green-100 text-green-700' : 'bg-red-50 border-red-100 text-red-700' ?> font-bold text-sm">
        <?= $message ?>
    </div>
    <?php endif; ?>

    <?php if (mysqli_num_rows($query) === 0): ?>
    <div class="bg-white rounded-4xl shadow-sm border border-slate-100 p-10 text-center">
        <span class="material-symbols-outlined text-5xl text-slate-300">restaurant_menu</span>
        <h3 class="mt-4 text-xl font-bold text-slate-800">Belum ada menu</h3>
        <p class="mt-2 text-sm text-slate-500">Tambahkan menu baru untuk mengisi katalog kantin.</p>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-8">
        <?php while($menu = mysqli_fetch_assoc($query)): 
            $badge_style = 'bg-slate-100 text-slate-600';
            $icon_name = 'restaurant'; // Default icon
            
            if($menu['kategori'] == 'Makanan') {
                $badge_style = 'bg-orange-50 text-primary-orange border-orange-100';
                $icon_name = 'lunch_dining';
            } elseif($menu['kategori'] == 'Minuman') {
                $badge_style = 'bg-blue-50 text-accent-blue border-blue-100';
                $icon_name = 'coffee'; // Ganti dari local_beverage ke coffee agar lebih aman
            } elseif($menu['kategori'] == 'Cemilan') {
                $badge_style = 'bg-green-50 text-accent-green border-green-100';
                $icon_name = 'cookie';
            }
        ?>
        <div class="bg-white p-6 rounded-4xl shadow-sm border border-slate-50 group transition-all duration-300">
            <?php $menu_image = !empty($menu['foto']) ? '../../uploads/' . $menu['foto'] : 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=900&q=80'; ?>
            <div class="overflow-hidden rounded-3xl mb-5 h-44 bg-slate-100">
                <img src="<?= htmlspecialchars($menu_image) ?>" alt="<?= htmlspecialchars($menu['nama_menu']) ?>" class="w-full h-full object-cover">
            </div>
            <div class="flex justify-between items-start mb-6">
                <div class="w-14 h-14 rounded-2xl bg-bg-soft flex items-center justify-center text-primary-orange border border-orange-50">
                    <span class="material-symbols-outlined text-3xl">
                        <?= $icon_name ?>
                    </span>
                </div>
                <span class="px-4 py-1.5 border <?= $badge_style ?> text-[10px] font-black uppercase tracking-widest rounded-xl">
                    <?= htmlspecialchars($menu['kategori']) ?>
                </span>
            </div>

            <h3 class="text-xl font-extrabold text-[#003049] mb-1"><?= htmlspecialchars($menu['nama_menu']) ?></h3>
            <p class="text-xs text-slate-400 font-bold flex items-center gap-1.5 mb-6">
                <span class="material-symbols-outlined !text-sm text-accent-blue">storefront</span> 
                Stand: <span class="text-slate-600 uppercase"><?= htmlspecialchars($menu['nama_kantin']) ?></span>
            </p>

            <div class="flex flex-col gap-4 pt-5 border-t border-slate-50">
                <div>
                    <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Harga</p>
                    <p class="text-2xl font-black text-[#003049]">
                        <span class="text-primary-orange text-sm font-bold">Rp</span> <?= number_format($menu['harga'], 0, ',', '.') ?>
                    </p>
                </div>
                <span class="inline-flex items-center justify-center w-max px-4 py-2 text-[10px] font-black uppercase tracking-widest rounded-full bg-orange-50 text-orange-700 border border-orange-100">
                    <?= htmlspecialchars($menu['status'] ?? 'Tersedia') ?>
                </span>
                <div class="flex flex-wrap gap-3 pt-4">
                    <a href="edit_menu.php?id=<?= $menu['id_menu'] ?>" class="flex-1 text-center px-4 py-3 rounded-2xl bg-slate-100 text-slate-700 text-sm font-semibold hover:bg-primary-orange hover:text-white transition">Edit</a>
                    <a href="proses_hapus_menu.php?id=<?= $menu['id_menu'] ?>" onclick="return confirm('Hapus menu ini?')" class="flex-1 text-center px-4 py-3 rounded-2xl bg-red-50 text-red-700 text-sm font-semibold hover:bg-red-600 hover:text-white transition">Hapus</a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</main>

</body>
</html>
