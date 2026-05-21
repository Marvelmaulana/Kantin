<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');
kk_ensure_buyer_schema($koneksi);

if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'pembeli') {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = (int)$_SESSION['id_user'];
$id_kantin = (int)($_GET['id'] ?? 0);
if ($id_kantin <= 0) {
    header("Location: dashboard.php");
    exit();
}

$q_kantin = mysqli_query($koneksi, "
    SELECT k.*,
           COALESCE(AVG(rm.nilai_rating),0) AS avg_rating,
           COUNT(rm.id_rating) AS total_rating,
           COUNT(DISTINCT m.id_menu) AS total_menu
    FROM kantin k
    LEFT JOIN menu m ON k.id_kantin=m.id_kantin AND COALESCE(m.status,'Tersedia') <> 'Habis'
    LEFT JOIN rating_menu rm ON m.id_menu=rm.id_menu
    WHERE k.id_kantin=$id_kantin
    GROUP BY k.id_kantin
");
$kantin = mysqli_fetch_assoc($q_kantin);
if (!$kantin) {
    header("Location: dashboard.php");
    exit();
}

$favorit_ids = [];
$q_fav = mysqli_query($koneksi, "SELECT id_menu FROM favorit WHERE id_user=$id_user");
while ($f = mysqli_fetch_assoc($q_fav)) $favorit_ids[] = (int)$f['id_menu'];

$query_menu = mysqli_query($koneksi, "
    SELECT m.*,
           COALESCE(AVG(rm.nilai_rating),0) AS avg_rating,
           COUNT(rm.id_rating) AS jml_rating,
           COUNT(u.id_ulasan) AS jml_ulasan
    FROM menu m
    LEFT JOIN rating_menu rm ON m.id_menu=rm.id_menu
    LEFT JOIN ulasan u ON m.id_menu=u.id_menu
    WHERE m.id_kantin=$id_kantin AND COALESCE(m.status,'Tersedia') <> 'Habis'
    GROUP BY m.id_menu
    ORDER BY m.id_menu DESC
");

// Statistik: menu terlaris
$query_terlaris = mysqli_query($koneksi, "
    SELECT m.*,
           COALESCE(SUM(dp.qty),0) AS total_terjual,
           COALESCE(AVG(rm.nilai_rating),0) AS avg_rating,
           COUNT(DISTINCT rm.id_rating) AS jml_rating
    FROM menu m
    LEFT JOIN detail_pesanan dp ON m.id_menu = dp.id_menu
    LEFT JOIN pesanan p ON dp.id_pesanan = p.id_pesanan AND p.status IN ('Selesai', 'Siap Diambil')
    LEFT JOIN rating_menu rm ON m.id_menu = rm.id_menu
    WHERE m.id_kantin = $id_kantin
    GROUP BY m.id_menu
    ORDER BY total_terjual DESC, avg_rating DESC
    LIMIT 5
");

$logo = kk_upload_url($kantin['logo'] ?? '', 'logo');
$banner = kk_upload_url($kantin['banner'] ?? '', 'banner');

function renderKantinMenuCard($m, $is_fav) {
    $id = (int)$m['id_menu'];
    $foto = kk_upload_url($m['foto'] ?? '', 'menu');
    $avg = round((float)$m['avg_rating'], 1);
    $jml = (int)max($m['jml_rating'] ?? 0, $m['jml_ulasan'] ?? 0);
    return "
    <a href='detail_menu.php?id=$id' class='bg-white rounded-3xl overflow-hidden border border-orange-100 shadow-sm hover:shadow-xl hover:shadow-red-900/5 transition block'>
        <div class='relative'>
            <img src='$foto' class='w-full aspect-square object-cover bg-orange-50' alt='".htmlspecialchars($m['nama_menu'] ?? '')."'>
            <div class='absolute bottom-2 left-2 bg-white/95 rounded-full px-2 py-1 flex items-center gap-1'>
                <span class='material-symbols-outlined text-yellow-400 text-[13px]'>star</span>
                <span class='text-[10px] font-black'>".($avg > 0 ? $avg : 'Baru')."</span>
            </div>
        </div>
        <div class='p-3'>
            <h3 class='headline font-black text-sm truncate'>".htmlspecialchars($m['nama_menu'] ?? 'Menu')."</h3>
            <p class='text-[10px] text-stone-400 mt-1'>".($jml > 0 ? $jml . ' ulasan' : 'Belum ada ulasan')."</p>
            <p class='text-[#b22204] font-black text-sm mt-2'>".kk_format_rupiah($m['harga'] ?? 0)."</p>
        </div>
    </a>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($kantin['nama_kantin']) ?> - Kantin Kita</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@700;800;900&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1" rel="stylesheet"/>
<style>
body{font-family:'Be Vietnam Pro',sans-serif;background:#fffdfc}.headline{font-family:'Plus Jakarta Sans',sans-serif}
.material-symbols-outlined{font-variation-settings:'FILL' 1,'wght' 500,'GRAD' 0,'opsz' 24}
</style>
</head>
<body class="pb-28 text-stone-800">
<header class="bg-white/90 backdrop-blur-xl sticky top-0 z-40 px-4 md:px-7 py-3 border-b border-orange-100 w-full">
    <div class="w-full flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="dashboard.php" class="w-11 h-11 rounded-2xl bg-gradient-to-br from-[#b22204] to-[#ff6b35] text-white flex items-center justify-center shadow-lg hover:scale-105 transition-transform">
                <span class="material-symbols-outlined">home</span>
            </a>
            <button onclick="history.back()" class="w-11 h-11 rounded-2xl bg-stone-100 text-stone-600 flex items-center justify-center">
                <span class="material-symbols-outlined">arrow_back</span>
            </button>
        </div>
        <div class="min-w-0 flex-1">
            <h1 class="headline font-black text-lg text-[#b22204] truncate"><?= htmlspecialchars($kantin['nama_kantin']) ?></h1>
            <p class="text-[10px] text-stone-400 font-bold uppercase tracking-wider">Menu kantin</p>
        </div>
        <a href="keranjang.php" class="w-11 h-11 rounded-2xl bg-orange-50 text-[#b22204] flex items-center justify-center">
            <span class="material-symbols-outlined">shopping_bag</span>
        </a>
    </div>
</header>

<main class="w-full px-4 md:px-7 py-5 space-y-6">
    <section class="relative rounded-[2rem] overflow-hidden min-h-[250px] md:min-h-[340px] bg-stone-200 shadow-xl shadow-red-900/10">
        <img src="<?= $banner ?>" class="absolute inset-0 w-full h-full object-cover" alt="Banner <?= htmlspecialchars($kantin['nama_kantin']) ?>">
        <div class="absolute inset-0 bg-gradient-to-t from-stone-950/80 via-stone-950/25 to-transparent"></div>
        <div class="absolute left-5 right-5 bottom-5 flex flex-col md:flex-row md:items-end gap-4">
            <img src="<?= $logo ?>" class="w-24 h-24 md:w-28 md:h-28 rounded-3xl object-cover border-4 border-white shadow-xl bg-white">
            <div class="text-white min-w-0">
                <p class="text-xs font-black uppercase tracking-[0.2em] text-white/70">Kantin</p>
                <h2 class="headline text-3xl md:text-5xl font-black leading-tight"><?= htmlspecialchars($kantin['nama_kantin']) ?></h2>
                <div class="flex flex-wrap gap-2 mt-3">
                    <span class="bg-white/18 backdrop-blur px-3 py-1.5 rounded-full text-xs font-black"><?= (int)$kantin['total_menu'] ?> menu</span>
                    <span class="bg-white/18 backdrop-blur px-3 py-1.5 rounded-full text-xs font-black">
                        <?= (float)$kantin['avg_rating'] > 0 ? round($kantin['avg_rating'],1).' ★' : 'Belum ada rating' ?> (<?= (int)$kantin['total_rating'] ?> ulasan)
                    </span>
                </div>
            </div>
        </div>
    </section>

    <section>
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-stone-400">Daftar menu</p>
                <h2 class="headline font-black text-xl">Menu <?= htmlspecialchars($kantin['nama_kantin']) ?></h2>
            </div>
        </div>
        <?php if ($query_menu && mysqli_num_rows($query_menu) > 0): ?>
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 2xl:grid-cols-6 gap-3 md:gap-4">
            <?php while ($m = mysqli_fetch_assoc($query_menu)) echo renderKantinMenuCard($m, in_array((int)$m['id_menu'], $favorit_ids, true)); ?>
        </div>
        <?php else: ?>
        <div class="text-center py-16 bg-white rounded-3xl border border-dashed border-orange-100 text-stone-400 font-bold">Kantin ini belum punya menu tersedia.</div>
        <?php endif; ?>
    </section>

    <!-- STATISTIK MENU TERLARIS -->
    <?php if ($query_terlaris && mysqli_num_rows($query_terlaris) > 0): ?>
    <section class="bg-gradient-to-br from-[#fff0ee] to-orange-50 rounded-[2rem] p-6 border border-orange-100">
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white shadow-lg shadow-orange-200">
                    <span class="material-symbols-outlined" style="font-size:22px">local_fire_department</span>
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-orange-400">Hot Items</p>
                    <h2 class="headline font-black text-xl text-stone-800">Menu Terlaris</h2>
                </div>
            </div>
            <span class="bg-orange-100 text-orange-600 px-3 py-1.5 rounded-full text-[10px] font-black">Top 5</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <?php
            $rank = 1;
            mysqli_data_seek($query_terlaris, 0);
            while ($terlaris = mysqli_fetch_assoc($query_terlaris)):
                $foto_terlaris = kk_upload_url($terlaris['foto'] ?? '', 'menu');
                $terjual = (int)($terlaris['total_terjual'] ?? 0);
                $rating = round((float)($terlaris['avg_rating'] ?? 0), 1);
            ?>
            <a href="detail_menu.php?id=<?= (int)$terlaris['id_menu'] ?>" class="bg-white rounded-2xl p-4 border border-orange-100 hover:shadow-lg hover:shadow-orange-200/30 transition-all group relative">
                <?php if ($rank <= 3): ?>
                <div class="absolute -top-2 -right-2 w-8 h-8 rounded-full bg-gradient-to-br <?= $rank == 1 ? 'from-yellow-400 to-yellow-600' : ($rank == 2 ? 'from-gray-300 to-gray-400' : 'from-amber-400 to-amber-600') ?> flex items-center justify-center text-white font-black text-xs shadow-lg">
                    <?= $rank ?>
                </div>
                <?php endif; ?>
                <img src="<?= $foto_terlaris ?>" class="w-full aspect-square rounded-xl object-cover bg-orange-50 mb-3 group-hover:scale-105 transition-transform" alt="<?= htmlspecialchars($terlaris['nama_menu']) ?>">
                <h4 class="font-black text-sm text-stone-800 truncate"><?= htmlspecialchars($terlaris['nama_menu']) ?></h4>
                <div class="flex items-center gap-2 mt-2">
                    <span class="text-[#b22204] font-black text-sm"><?= kk_format_rupiah($terlaris['harga']) ?></span>
                </div>
                <div class="flex items-center justify-between mt-2">
                    <span class="text-[10px] font-bold text-stone-400"><i class="fa-solid fa-fire mr-1"></i><?= $terjual ?> terjual</span>
                    <span class="text-[10px] font-bold text-yellow-500"><i class="fa-solid fa-star mr-1"></i><?= $rating > 0 ? $rating : '-' ?></span>
                </div>
            </a>
            <?php $rank++; endwhile; ?>
        </div>
    </section>
    <?php endif; ?>
</main>

<?php $current_page = 'menu'; include(__DIR__ . '/../../includes/navbar.php'); ?>
</body>
</html>
