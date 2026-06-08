<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'pembeli') {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = (int)$_SESSION['id_user'];
$q = mysqli_query($koneksi, "
    SELECT m.*, k.nama_kantin, 
           COALESCE(AVG(rm.nilai_rating),0) AS avg_rating, 
           COUNT(rm.id_rating) AS jml_rating
    FROM menu m
    JOIN kantin k ON m.id_kantin = k.id_kantin
    LEFT JOIN rating_menu rm ON m.id_menu = rm.id_menu
    WHERE COALESCE(m.status,'Tersedia') NOT IN ('Diblokir','Dinonaktifkan')
    GROUP BY m.id_menu
    ORDER BY CASE WHEN COALESCE(m.status,'Tersedia')='Tersedia' AND COALESCE(m.stok,0) > 0 THEN 0 ELSE 1 END,
             k.nama_kantin ASC, m.id_menu DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= t('menu.all', 'Semua Menu') ?> - <?= t('buyer.title', 'Kantin Kita') ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@700;800;900&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1" rel="stylesheet"/>
<style>body{font-family:'Be Vietnam Pro',sans-serif;background:#fffdfc}.headline{font-family:'Plus Jakarta Sans',sans-serif}</style>
</head>
<body class="pb-28 text-stone-800">
<header class="bg-white/90 backdrop-blur-xl sticky top-0 z-40 px-5 py-4 border-b border-stone-100">
    <div class="max-w-[1400px] mx-auto flex items-center gap-3">
        <button onclick="history.back()" class="w-10 h-10 rounded-2xl bg-stone-100 flex items-center justify-center"><span class="material-symbols-outlined">arrow_back</span></button>
        <div>
            <h1 class="headline font-black text-lg text-[#b22204]"><?= t('menu.all', 'Semua Menu') ?></h1>
            <p class="text-[10px] uppercase tracking-widest text-stone-400 font-bold"><?= t('menu.all_sub', 'Tersedia di atas, habis di bawah') ?></p>
        </div>
    </div>
</header>
<main class="max-w-[1400px] mx-auto px-4 md:px-6 py-5">
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3 md:gap-4">
        <?php while ($m = mysqli_fetch_assoc($q)): ?>
        <?php $tersedia = kk_is_menu_available($m); ?>
        <a href="detail_menu.php?id=<?= (int)$m['id_menu'] ?>" class="bg-white rounded-3xl overflow-hidden border border-orange-100 shadow-sm hover:shadow-xl hover:shadow-red-900/5 transition relative <?= $tersedia ? '' : 'opacity-70 grayscale-[35%]' ?>">
            <div class="relative">
                <img src="<?= kk_upload_url($m['foto'] ?? '', 'menu') ?>" class="w-full aspect-square object-cover bg-stone-100" onerror="this.src='../../public/assets/img/default-food.svg'">
                <!-- Logo Halal -->
                <img src='/kantin/uploads/logo/Cuplikan_layar_2026-06-08_104038-removebg-preview.png' alt='Halal' class='absolute bottom-2 right-2 w-8 h-8 object-contain drop-shadow-md z-10'>
                <span class="absolute top-2 right-2 px-2.5 py-1 rounded-full text-[10px] font-black text-white <?= $tersedia ? 'bg-green-500' : 'bg-red-500' ?>">
                    <?= $tersedia ? 'Tersedia' : 'Habis' ?>
                </span>
            </div>
            <div class="p-3">
                <p class="text-[10px] text-stone-400 font-bold truncate"><?= htmlspecialchars($m['nama_kantin']) ?></p>
                <h2 class="headline font-black text-sm truncate mt-1"><?= htmlspecialchars($m['nama_menu']) ?></h2>
                <div class="mt-2 flex items-center justify-between gap-2">
                    <span class="text-[#b22204] font-black text-sm">Rp <?= number_format($m['harga'],0,',','.') ?></span>
                    <span class="text-[10px] text-yellow-600 font-black"><?= (float)$m['avg_rating'] > 0 ? round($m['avg_rating'],1).'★' : 'Baru' ?></span>
                </div>
            </div>
        </a>
        <?php endwhile; ?>
    </div>
</main>
<?php $current_page = 'menu'; include(__DIR__ . '/../../includes/navbar.php'); ?>
</body>
</html>
