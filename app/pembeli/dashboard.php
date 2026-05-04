<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'pembeli') {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

// --- BAGIAN PENTING: DEFINISIKAN VARIABEL AGAR TIDAK EROR ---
$query_kantin = mysqli_query($koneksi, "SELECT * FROM kantin LIMIT 10");

// Query Menu dengan pencarian kategori
$kategori_filter = isset($_GET['category']) ? $_GET['category'] : '';
$sql_menu = "SELECT * FROM menu";
if($kategori_filter != '') {
    $sql_menu .= " WHERE kategori = '$kategori_filter'";
}
$sql_menu .= " ORDER BY id_menu DESC LIMIT 8";
$query_menu = mysqli_query($koneksi, $sql_menu);

// Hitung keranjang
$q_cart = mysqli_query($koneksi, "SELECT SUM(qty) as total FROM keranjang WHERE id_user='$id_user'");
$d_cart = mysqli_fetch_assoc($q_cart);
$jml_keranjang = ($d_cart['total'] > 0) ? $d_cart['total'] : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Kantin Kita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;800&family=Be+Vietnam+Pro:wght@400;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <style>
        body { font-family: 'Be Vietnam Pro', sans-serif; background-color: #fffdfc; padding-bottom: 120px; }
        .font-headline { font-family: 'Plus Jakarta Sans', sans-serif; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .floating-nav {
            position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%);
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);
            height: 75px; display: flex; justify-content: space-around; align-items: center;
            z-index: 50; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); border-radius: 25px; width: 90%; max-width: 450px;
        }
    </style>
</head>
<body class="text-stone-800">

<header class="bg-white sticky top-0 z-40 px-6 py-4 flex items-center justify-between shadow-sm">
    <span class="text-xl font-extrabold text-[#b22204] font-headline uppercase italic">Kantin Kita</span>
    <div class="flex gap-4">
        <a href="keranjang.php" class="relative text-[#b22204]">
            <span class="material-symbols-outlined">shopping_bag</span>
            <?php if($jml_keranjang > 0): ?>
                <span class="absolute -top-2 -right-2 bg-red-600 text-white text-[10px] w-4 h-4 flex items-center justify-center rounded-full"><?= $jml_keranjang ?></span>
            <?php endif; ?>
        </a>
    </div>
</header>

<main class="max-w-4xl mx-auto px-6 py-6">
    <div class="mb-8">
        <div class="relative">
            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-stone-400">search</span>
            <input class="w-full pl-12 pr-4 py-3 bg-stone-100 border-none rounded-full text-sm" placeholder="Cari makanan favoritmu..." type="text"/>
        </div>
    </div>

    <div class="flex gap-2 overflow-x-auto hide-scrollbar mb-8">
        <a href="dashboard.php" class="px-6 py-2 rounded-xl text-xs font-bold <?= $kategori_filter == '' ? 'bg-[#b22204] text-white' : 'bg-orange-50 text-stone-600' ?>">Favorit</a>
        <a href="?category=Makanan" class="px-6 py-2 rounded-xl text-xs font-bold <?= $kategori_filter == 'Makanan' ? 'bg-[#b22204] text-white' : 'bg-orange-50 text-stone-600' ?>">Makanan</a>
        <a href="?category=Minuman" class="px-6 py-2 rounded-xl text-xs font-bold <?= $kategori_filter == 'Minuman' ? 'bg-[#b22204] text-white' : 'bg-orange-50 text-stone-600' ?>">Minuman</a>
        <a href="?category=Camilan" class="px-6 py-2 rounded-xl text-xs font-bold <?= $kategori_filter == 'Camilan' ? 'bg-[#b22204] text-white' : 'bg-orange-50 text-stone-600' ?>">Cemilan</a>
    </div>

    <section class="mb-10">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold font-headline italic">Terbaru</h3>
            <a href="#" class="text-xs font-bold text-[#b22204]">Lihat Semua</a>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php while($m = mysqli_fetch_assoc($query_menu)): ?>
            <div class="bg-white rounded-[2rem] overflow-hidden shadow-sm border border-stone-50 relative group">
                <a href="proses_favorit.php?id=<?= $m['id_menu'] ?>" class="absolute top-3 right-3 z-10 w-8 h-8 bg-white/80 rounded-full flex items-center justify-center text-stone-300 hover:text-red-500 shadow-sm">
                    <span class="material-symbols-outlined text-sm">favorite</span>
                </a>
                
                <a href="detail_menu.php?id=<?= $m['id_menu'] ?>">
                    <img class="w-full aspect-square object-cover" src="../../uploads/<?= $m['foto'] ?>"/>
                    <div class="p-4">
                        <h4 class="font-bold text-xs line-clamp-1"><?= $m['nama_menu'] ?></h4>
                        <div class="flex items-center gap-1 mt-1">
                            <span class="material-symbols-outlined text-yellow-400 text-[12px]" style='font-variation-settings: "FILL" 1;'>star</span>
                            <span class="text-[10px] font-bold text-stone-400">4.8 <span class="font-normal">(120+)</span></span>
                        </div>
                        <div class="flex items-center justify-between mt-3">
                            <span class="text-[#b22204] font-black text-sm">Rp <?= number_format($m['harga']) ?></span>
                            <div class="w-6 h-6 bg-stone-100 rounded-full flex items-center justify-center text-[#b22204]">
                                <span class="material-symbols-outlined text-sm">add</span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endwhile; ?>
        </div>
    </section>

    <section>
        <h3 class="text-lg font-bold font-headline mb-4 italic">Daftar Kantin</h3>
        <div class="grid grid-cols-2 gap-3">
            <?php while($k = mysqli_fetch_assoc($query_kantin)): ?>
            <a href="kantin_detail.php?id=<?= $k['id_kantin'] ?>" class="bg-white p-4 rounded-2xl flex items-center gap-3 border border-stone-100 shadow-sm">
                <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center text-[#b22204]">
                    <span class="material-symbols-outlined">storefront</span>
                </div>
                <span class="text-xs font-bold"><?= $k['nama_kantin'] ?></span>
            </a>
            <?php endwhile; ?>
        </div>
    </section>
</main>

<?php 
  $current_page = 'home';
  include(__DIR__ . '/../../includes/navbar.php'); 
?>

</body>
</html>