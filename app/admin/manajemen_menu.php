<?php
session_start();
include(__DIR__ . '/../../config/config.php');

// 1. PROTEKSI HALAMAN
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../halaman_login.php");
    exit();
}

// 2. AMBIL DATA MENU
$sql = "SELECT menu.*, users.username as nama_kantin 
        FROM menu 
        JOIN users ON menu.id_kantin = users.id_kantin 
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
<body class="text-slate-800 flex">

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
        
        <div class="bg-white px-5 py-3 rounded-2xl border border-slate-100 flex items-center gap-3 shadow-sm text-sm font-bold text-slate-500">
            <span class="material-symbols-outlined text-primary-orange">restaurant</span> 
            <span class="text-primary-orange"><?= mysqli_num_rows($query) ?></span> Produk Aktif
        </div>
    </header>

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

            <div class="flex justify-between items-center pt-5 border-t border-slate-50">
                <div>
                    <p class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Harga</p>
                    <p class="text-2xl font-black text-[#003049]">
                        <span class="text-primary-orange text-sm font-bold">Rp</span> <?= number_format($menu['harga'], 0, ',', '.') ?>
                    </p>
                </div>
                <div class="flex gap-2">
                    <button class="w-10 h-10 rounded-xl bg-slate-50 text-slate-400 hover:bg-accent-blue hover:text-white flex items-center justify-center transition-all">
                        <span class="material-symbols-outlined text-lg">edit</span>
                    </button>
                    <button class="w-10 h-10 rounded-xl bg-slate-50 text-slate-400 hover:bg-red-500 hover:text-white flex items-center justify-center transition-all">
                        <span class="material-symbols-outlined text-lg">delete</span>
                    </button>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</main>

</body>
</html>