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
if (isset($_GET['error'])) {
    $message = urldecode($_GET['error']);
    $message_type = 'error';
} elseif (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    $message_type = 'success';
}

$filter = $_GET['filter'] ?? 'all';
$filter = in_array($filter, ['all', 'tersedia', 'blocked'], true) ? $filter : 'all';
$where = '';
if ($filter === 'tersedia') {
    $where = "WHERE menu.status = 'Tersedia'";
} elseif ($filter === 'blocked') {
    $where = "WHERE menu.status IN ('Dinonaktifkan','Diblokir')";
}

$sql = "SELECT menu.*, kantin.nama_kantin 
        FROM menu 
        JOIN kantin ON menu.id_kantin = kantin.id_kantin 
        $where
        ORDER BY menu.id_menu DESC";
$query = mysqli_query($koneksi, $sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= t('admin.manage_menus_title', 'Manajemen Menu - Kantin Kita') ?></title>
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
                <h2 class="text-3xl font-extrabold text-[#003049]"><?= t('admin.manage_menus_heading', 'Manajemen Menu') ?></h2>
                <p class="text-slate-400 font-medium text-sm"><?= t('admin.manage_menus_desc', 'Monitor semua produk kuliner.') ?></p>
            </div>
        </div>
        
        <div class="flex items-center gap-3">
            <div class="bg-white px-5 py-3 rounded-2xl border border-slate-100 flex items-center gap-3 shadow-sm text-sm font-bold text-slate-500">
                <span class="material-symbols-outlined text-primary-orange">restaurant</span>
                <span class="text-primary-orange"><?= mysqli_num_rows($query) ?></span>
                <?= $filter === 'blocked' ? 'Produk Terblokir' : ($filter === 'tersedia' ? 'Produk Tersedia' : 'Semua Produk') ?>
            </div>
        </div>
    </header>
    <div class="mb-8 flex flex-wrap gap-3 items-center">
        <a href="?filter=all" class="px-4 py-2 rounded-full text-sm font-semibold <?= $filter === 'all' ? 'bg-primary-orange text-white' : 'bg-white text-slate-600 border border-slate-200' ?>">Semua</a>
        <a href="?filter=tersedia" class="px-4 py-2 rounded-full text-sm font-semibold <?= $filter === 'tersedia' ? 'bg-primary-orange text-white' : 'bg-white text-slate-600 border border-slate-200' ?>">Tersedia</a>
        <a href="?filter=blocked" class="px-4 py-2 rounded-full text-sm font-semibold <?= $filter === 'blocked' ? 'bg-primary-orange text-white' : 'bg-white text-slate-600 border border-slate-200' ?>">Terblokir / Dinonaktifkan</a>
    </div>
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
            <div class="overflow-hidden rounded-3xl mb-5 h-44 bg-slate-100 relative">
                <img src="<?= htmlspecialchars($menu_image) ?>" alt="<?= htmlspecialchars($menu['nama_menu']) ?>" class="w-full h-full object-cover">
                <img src="../../uploads/logo/Cuplikan_layar_2026-06-08_104038-removebg-preview.png" alt="Halal" class="absolute bottom-2 right-2 w-8 h-8 md:w-10 md:h-10 object-contain drop-shadow-md bg-white/50 backdrop-blur-sm rounded-full p-0.5 z-10">
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
                <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Harga</p>
                    <p class="text-2xl font-black text-[#003049]">
                        <span class="text-primary-orange text-sm font-bold">Rp</span> <?= number_format($menu['harga'], 0, ',', '.') ?>
                    </p>
                </div>
                <?php if (!in_array($menu['status'], ['Diblokir','Dinonaktifkan'], true)): ?>
                <button type="button" class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-red-50 text-red-700 border border-red-100 hover:bg-red-100 transition text-sm font-semibold" data-menu-id="<?= $menu['id_menu'] ?>" data-menu-name="<?= htmlspecialchars($menu['nama_menu'], ENT_QUOTES) ?>" onclick="openBlockModal(this)">
                    <span class="material-symbols-outlined text-base">block</span>
                    Blokir
                </button>
                <?php endif; ?>
            </div>
            <div class="space-y-2">
                    <span class="inline-flex items-center justify-center w-max px-4 py-2 text-[10px] font-black uppercase tracking-widest rounded-full bg-orange-50 text-orange-700 border border-orange-100">
                        <?= htmlspecialchars($menu['status'] ?? 'Tersedia') ?>
                    </span>
                    <?php if (!empty($menu['catatan_blokir']) && in_array($menu['status'], ['Diblokir','Dinonaktifkan'], true)): ?>
                    <p class="text-[11px] text-slate-500 bg-slate-50 border border-slate-100 rounded-2xl px-3 py-2">
                        <strong class="text-slate-700">Catatan:</strong> <?= htmlspecialchars($menu['catatan_blokir']) ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</main>

<div id="blockModal" class="fixed inset-0 hidden items-center justify-center bg-slate-900/60 z-50 p-4">
    <div class="w-full max-w-lg bg-white rounded-3xl shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-200">
            <div>
                <h3 class="text-xl font-extrabold text-slate-900">Blokir Menu</h3>
                <p class="text-sm text-slate-500 mt-1" id="blockModalLabel">Masukkan alasan pemblokiran untuk menu ini.</p>
            </div>
            <button type="button" onclick="closeBlockModal()" class="text-slate-400 hover:text-slate-700 transition">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form action="proses_blokir_menu.php" method="post" class="px-6 py-6 space-y-5">
            <input type="hidden" name="id_menu" id="blockMenuId" value="">
            <div>
                <label for="catatanBlokir" class="block text-sm font-bold text-slate-700 mb-2">Catatan Pemblokiran</label>
                <textarea id="catatanBlokir" name="catatan_blokir" rows="5" required class="w-full px-4 py-3 border border-slate-200 rounded-3xl focus:border-primary-orange outline-none resize-none" placeholder="Tuliskan alasan kenapa menu ini diblokir"></textarea>
            </div>
            <div class="flex flex-col sm:flex-row sm:justify-end gap-3">
                <button type="button" onclick="closeBlockModal()" class="px-5 py-3 rounded-3xl border border-slate-200 text-slate-700 hover:bg-slate-50 transition">Batal</button>
                <button type="submit" class="px-5 py-3 rounded-3xl bg-red-600 text-white font-bold hover:bg-red-700 transition">Blokir Sekarang</button>
            </div>
        </form>
    </div>
</div>

<script>
function openBlockModal(button) {
    const menuId = button.dataset.menuId;
    const menuName = button.dataset.menuName || 'menu ini';
    document.getElementById('blockMenuId').value = menuId;
    document.getElementById('blockModalLabel').textContent = 'Alasan pemblokiran untuk "' + menuName + '"';
    document.getElementById('catatanBlokir').value = '';
    document.getElementById('blockModal').classList.remove('hidden');
}

function closeBlockModal() {
    document.getElementById('blockModal').classList.add('hidden');
}

window.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && !document.getElementById('blockModal').classList.contains('hidden')) {
        closeBlockModal();
    }
});
</script>

</body>
</html>
