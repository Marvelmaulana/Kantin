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
$csrfToken = kk_csrf_token();


if (isset($_POST['ajax_favorit'])) {
    header('Content-Type: application/json');
    if (!kk_verify_csrf($_POST['csrf_token'] ?? '')) {
        echo json_encode(['status' => 'error', 'message' => 'csrf']);
        exit();
    }
    $id_menu = (int)$_POST['id_menu'];
    $cek = mysqli_query($koneksi, "SELECT id_favorit FROM favorit WHERE id_user=$id_user AND id_menu=$id_menu");
    if ($cek && mysqli_num_rows($cek) > 0) {
        mysqli_query($koneksi, "DELETE FROM favorit WHERE id_user=$id_user AND id_menu=$id_menu");
        echo json_encode(['status' => 'removed']);
    } else {
        mysqli_query($koneksi, "INSERT INTO favorit (id_user, id_menu) VALUES ($id_user, $id_menu)");
        echo json_encode(['status' => 'added']);
    }
    exit();
}

$favorit_ids = [];
$q_fav = mysqli_query($koneksi, "SELECT id_menu FROM favorit WHERE id_user=$id_user");
while ($f = mysqli_fetch_assoc($q_fav)) $favorit_ids[] = (int)$f['id_menu'];

$tab = $_GET['tab'] ?? 'semua';
$kategoriMenu = $_GET['category'] ?? '';
$kategoriKantin = $_GET['kantin_cat'] ?? 'Semua';
$allowedKategori = ['Semua', 'Makanan', 'Minuman', 'Camilan'];
if (!in_array($kategoriKantin, $allowedKategori, true)) $kategoriKantin = 'Semua';

$selectMenu = "
    SELECT m.*, k.nama_kantin,
           COALESCE(AVG(rm.nilai_rating),0) AS avg_rating,
           COUNT(rm.id_rating) AS jml_rating,
           COUNT(u.id_ulasan) AS jml_ulasan
    FROM menu m
    JOIN kantin k ON m.id_kantin = k.id_kantin
    LEFT JOIN rating_menu rm ON m.id_menu = rm.id_menu
    LEFT JOIN ulasan u ON m.id_menu = u.id_menu
";

$whereAvailable = " WHERE 1=1 ";
$katSql = mysqli_real_escape_string($koneksi, $kategoriMenu);

if ($tab === 'favorit' && !empty($favorit_ids)) {
    $favIn = implode(',', $favorit_ids);
    $query_menu = mysqli_query($koneksi, "$selectMenu $whereAvailable AND m.id_menu IN ($favIn) GROUP BY m.id_menu ORDER BY m.id_menu DESC");
} elseif ($tab === 'favorit') {
    $query_menu = null;
} elseif ($kategoriMenu !== '') {
    $query_menu = mysqli_query($koneksi, "$selectMenu $whereAvailable AND m.kategori='$katSql' GROUP BY m.id_menu ORDER BY m.id_menu DESC LIMIT 20");
} else {
    $query_menu = mysqli_query($koneksi, "$selectMenu $whereAvailable GROUP BY m.id_menu ORDER BY m.id_menu DESC LIMIT 24");
}

$query_terbaru = mysqli_query($koneksi, "$selectMenu $whereAvailable GROUP BY m.id_menu ORDER BY m.id_menu DESC LIMIT 10");
$query_terlaris = mysqli_query($koneksi, "$selectMenu $whereAvailable GROUP BY m.id_menu ORDER BY avg_rating DESC, jml_rating DESC, m.id_menu DESC LIMIT 10");

$kantinWhere = '';
if ($kategoriKantin !== 'Semua') {
    $katKantinSql = mysqli_real_escape_string($koneksi, $kategoriKantin);
    $kantinWhere = "WHERE EXISTS (SELECT 1 FROM menu m2 WHERE m2.id_kantin=k.id_kantin AND m2.kategori='$katKantinSql' AND COALESCE(m2.status,'Tersedia') <> 'Habis' AND COALESCE(m2.stok,0) > 0)";
}
$query_kantin = mysqli_query($koneksi, "
    SELECT k.*,
           COUNT(m.id_menu) AS total_menu,
           COALESCE(AVG(rm.nilai_rating),0) AS avg_rating,
           COUNT(rm.id_rating) AS total_rating
    FROM kantin k
    LEFT JOIN menu m ON k.id_kantin=m.id_kantin AND COALESCE(m.status,'Tersedia') <> 'Habis' AND COALESCE(m.stok,0) > 0
    LEFT JOIN rating_menu rm ON m.id_menu=rm.id_menu
    $kantinWhere
    GROUP BY k.id_kantin
    ORDER BY k.id_kantin DESC
");

$q_user = mysqli_query($koneksi, "SELECT username, foto_profil FROM users WHERE id_user=$id_user");
$d_user = mysqli_fetch_assoc($q_user);
$nama = explode(' ', $d_user['username'] ?? 'Pembeli')[0];

function renderMenuCard($m, $is_fav) {
    $id = (int)$m['id_menu'];
    $avg = round((float)$m['avg_rating'], 1);
    $jml = (int)max($m['jml_rating'] ?? 0, $m['jml_ulasan'] ?? 0);
    $ratingTxt = $avg > 0 ? $avg : 'Baru';
    $ulasanTxt = $jml > 0 ? $jml . ' ulasan' : 'Belum ada ulasan';
    $foto = kk_upload_url($m['foto'] ?? '', 'menu');
    $loved = $is_fav ? 'loved text-red-500' : 'text-gray-300';
    $fill = $is_fav ? '1' : '0';
    $available = kk_is_menu_available($m);
    $badgeText = $available ? 'Tersedia' : 'Habis';
    $badgeClass = $available ? 'bg-orange-500/95 text-white' : 'bg-red-500/95 text-white';
    $cardClass = $available ? '' : 'opacity-75 grayscale-[35%]';

    return "
    <article class='menu-card bg-white rounded-3xl overflow-hidden border-2 border-transparent shadow-lg relative group hover:shadow-2xl hover:shadow-orange-200/40 hover:border-orange-300 transition-all duration-300 $cardClass' data-card>
        <button onclick='toggleFavorit(this,$id)' class='love-btn absolute top-3 left-3 z-10 w-10 h-10 bg-white/95 backdrop-blur rounded-full flex items-center justify-center shadow-lg $loved hover:scale-110 transition-all'>
            <span class='material-symbols-outlined text-lg' style='font-variation-settings:\"FILL\" $fill'>favorite</span>
        </button>
        <a href='detail_menu.php?id=$id' class='block'>
            <div class='relative overflow-hidden'>
                <img class='w-full aspect-square object-cover bg-gradient-to-br from-orange-50 to-orange-100 group-hover:scale-110 transition-transform duration-500' src='$foto' alt='".htmlspecialchars($m['nama_menu'] ?? '')."'>
                <div class='absolute top-3 right-3 $badgeClass text-[10px] font-black px-3 py-1.5 rounded-full shadow-lg uppercase tracking-wide'>$badgeText</div>
                <div class='absolute left-3 bottom-3 bg-white/95 backdrop-blur-lg px-3 py-1.5 rounded-full flex items-center gap-1 shadow-lg'>
                    <span class='material-symbols-outlined text-yellow-400 text-base'>star</span>
                    <span class='text-xs font-black text-gray-800'>$ratingTxt</span>
                </div>
            </div>
            <div class='p-4'>
                <p class='text-[10px] text-gray-400 font-black uppercase tracking-wider truncate'>".htmlspecialchars($m['nama_kantin'] ?? 'Kantin')."</p>
                <h4 class='font-black text-sm truncate mt-1 bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent leading-tight'>".htmlspecialchars($m['nama_menu'] ?? 'Menu')."</h4>
                <div class='flex items-center gap-2 mt-2'>
                    <span class='text-orange-600 font-black text-sm'>Rp ".number_format($m['harga'] ?? 0, 0, ',', '.')."</span>
                </div>
                <div class='flex items-center justify-between mt-2'>
                    <span class='text-[10px] text-gray-400 font-semibold'>$ulasanTxt</span>
                    <span class='w-8 h-8 rounded-full bg-gradient-to-br from-orange-500 to-orange-600 text-white flex items-center justify-center shadow-lg shadow-orange-200 group-hover:scale-110 transition-all'>
                        <span class='material-symbols-outlined text-sm'>add</span>
                    </span>
                </div>
            </div>
        </a>
    </article>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= t('buyer.title', 'Kantin Kita') ?> - <?= t('buyer.subtitle', 'Pesan Makanan Favorit') ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800;900&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1" rel="stylesheet"/>
<style>
* { scrollbar-width: thin; scrollbar-color: #fbbf24 transparent; }
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-thumb { background: #fbbf24; border-radius: 10px; }

body {
    font-family: 'Be Vietnam Pro', sans-serif;
    background: linear-gradient(135deg, #fff7eb 0%, #fff0dd 32%, #fff7ef 66%, #fffbf5 100%);
    min-height: 100vh;
    padding-bottom: 120px;
    color: #1f2937;
}

body::before {
    content: '';
    position: fixed;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background:
        radial-gradient(circle at 20% 18%, rgba(249,115,22,0.14) 0%, transparent 42%),
        radial-gradient(circle at 80% 80%, rgba(251,146,60,0.10) 0%, transparent 42%),
        radial-gradient(circle at 50% 50%, rgba(249,115,22,0.06) 0%, transparent 50%);
    z-index: -1;
    animation: floatBg 25s ease-in-out infinite;
}
@keyframes floatBg {
    0%, 100% { transform: translate(0, 0); }
    33% { transform: translate(2%, 1%); }
    66% { transform: translate(-1%, 2%); }
}

.headline { font-family: 'Plus Jakarta Sans', sans-serif; }

.material-symbols-outlined { font-variation-settings: 'FILL' 1,'wght' 500,'GRAD' 0,'opsz' 24 }

.gradient-text {
    background: linear-gradient(135deg, #d97706, #ea580c, #b91c1c);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.love-btn { transition: all 0.2s; }
.love-btn:active { transform: scale(1.3); }
.love-btn.loved { color: #ef4444 !important; }

.chip {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .65rem 1.25rem;
    border-radius: 18px;
    font-size: .82rem;
    font-weight: 800;
    white-space: nowrap;
    border: 2px solid rgba(251,146,60,.3);
    transition: all 0.25s;
    background: rgba(255,255,255,.92);
    color: #7c2d12;
    text-decoration: none;
}
.chip:hover {
    background: linear-gradient(135deg, #fff1e0, #ffe3cc);
    color: #b54708;
    border-color: #fb923c;
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(249,115,22,0.18);
}
.chip-active {
    background: linear-gradient(135deg, #ea580c, #c2410c) !important;
    color: white !important;
    border-color: transparent !important;
    box-shadow: 0 10px 30px rgba(194,65,12,0.35);
    transform: translateY(-2px);
}

.floating { animation: floating 3s ease-in-out infinite; }
@keyframes floating {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-10px) rotate(2deg); }
}

@keyframes gradient {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}
@keyframes blob {
    0% { transform: translate(0px, 0px) scale(1); }
    33% { transform: translate(30px, -50px) scale(1.1); }
    66% { transform: translate(-20px, 20px) scale(0.9); }
    100% { transform: translate(0px, 0px) scale(1); }
}
.animate-blob { animation: blob 7s infinite; }
.animation-delay-2000 { animation-delay: 2s; }
.animation-delay-4000 { animation-delay: 4s; }

.menu-card { transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1); background: linear-gradient(135deg, #ffffff, #fff4eb); border: 1px solid rgba(251,146,60,.15); border-radius: 30px; }
.menu-card:hover { transform: translateY(-6px); box-shadow: 0 24px 40px rgba(251,146,60,.12); }

.section-title {
    background: linear-gradient(135deg, #ea580c, #c2410c);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
</style>
</head>
<body class="text-gray-800">

<!-- HEADER -->
<header class="bg-white/90 backdrop-blur-xl sticky top-0 z-40 border-b-2 border-orange-100 shadow-sm">
    <div class="max-w-[1400px] mx-auto px-4 md:px-6 py-3">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="dashboard.php" class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-lg hover:scale-105 transition-all">
                    <img src="/kantin/uploads/logo/logo_kantin_kita.png" alt="Kantin Kita Logo" class="w-full h-full object-contain rounded-2xl">
                </a>
                <div>
                    <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest"><?= t('buyer.welcome', ['name' => $nama]) ?></p>
                    <h1 class="headline text-xl md:text-2xl font-black gradient-text italic">Kantin Kita</h1>
                </div>
            </div>
            <p class="hidden sm:block text-xs font-bold text-gray-400">Halo, <?= htmlspecialchars($d_user['username'] ?? 'Pembeli') ?></p>
        </div>
    </div>
</header>

<main class="max-w-[1400px] mx-auto px-4 md:px-6 py-6 space-y-8">

    <!-- HERO BANNER (REDESIGN) -->
    <div class="relative w-full rounded-3xl overflow-hidden shadow-[0_20px_50px_rgba(249,115,22,0.3)] mb-8">
        <!-- Abstract Animated Background -->
        <div class="absolute inset-0 bg-gradient-to-r from-orange-500 via-red-500 to-orange-600 bg-[length:200%_200%] animate-[gradient_8s_ease_infinite]"></div>
        
        <!-- Decorative Shapes (Blob effects) -->
        <div class="absolute -top-24 -left-24 w-64 h-64 bg-yellow-400 rounded-full mix-blend-multiply filter blur-3xl opacity-40 animate-blob"></div>
        <div class="absolute -bottom-24 -right-24 w-64 h-64 bg-red-400 rounded-full mix-blend-multiply filter blur-3xl opacity-40 animate-blob animation-delay-2000"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-pink-400 rounded-full mix-blend-multiply filter blur-3xl opacity-40 animate-blob animation-delay-4000"></div>

        <!-- Pattern / Glass Overlay -->
        <div class="absolute inset-0 bg-white/10 backdrop-blur-[2px]"></div>

        <div class="relative z-10 flex flex-col md:flex-row items-center justify-between p-8 md:p-12 gap-8">
            <div class="flex-1 text-center md:text-left">
                <!-- Badge -->
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/20 backdrop-blur-md border border-white/30 text-white text-xs font-bold uppercase tracking-widest mb-4 shadow-lg transform hover:scale-105 transition-transform">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-yellow-500"></span>
                    </span>
                    Selamat Datang di
                </div>

                <!-- Title -->
                <h2 class="headline text-5xl md:text-6xl lg:text-7xl font-black text-white leading-tight drop-shadow-md mb-2">
                    Kantin Kita
                </h2>

                <!-- Subtitle -->
                <p class="text-lg md:text-xl text-white/90 font-medium max-w-xl mx-auto md:mx-0 drop-shadow-sm mb-8 leading-relaxed">
                    Kantin Digital <span class="font-black text-yellow-300">SMKN 1 Boyolangu</span>. Temukan dan pesan makanan favoritmu lebih cepat dan praktis!
                </p>

                <!-- Action Button -->
                <div class="flex justify-center md:justify-start">
                    <a href="semua_menu.php" class="group relative inline-flex items-center gap-3 px-8 py-4 bg-white text-orange-600 font-black rounded-full overflow-hidden shadow-[0_8px_30px_rgb(255,255,255,0.3)] transition-all hover:scale-105 hover:shadow-[0_8px_30px_rgb(255,255,255,0.5)]">
                        <span class="relative z-10 text-sm tracking-wide uppercase">Jelajahi Menu</span>
                        <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center relative z-10 group-hover:bg-orange-200 transition-colors">
                            <i class="fa-solid fa-arrow-right text-orange-600 group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Floating Illustration / Emojis -->
            <div class="hidden md:flex relative w-48 h-48 md:w-64 md:h-64 items-center justify-center">
                <!-- Center Main Emoji -->
                <div class="absolute text-8xl md:text-[8rem] drop-shadow-2xl floating z-20">🍱</div>
                <!-- Small Floating Elements -->
                <div class="absolute top-0 right-10 text-4xl drop-shadow-lg floating" style="animation-delay: -1s;">🍔</div>
                <div class="absolute bottom-10 left-0 text-5xl drop-shadow-lg floating" style="animation-delay: -2s;">🥤</div>
                <div class="absolute top-1/2 -right-8 text-3xl drop-shadow-lg floating" style="animation-delay: -1.5s;">🍟</div>
            </div>
        </div>
    </div>

    <!-- SEARCH + FILTER -->
    <div class="bg-white rounded-3xl border-2 border-orange-100 p-4 md:p-5 shadow-lg">
        <div class="relative mb-4">
            <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input class="w-full pl-12 pr-4 py-4 bg-gray-50 rounded-2xl text-sm border-2 border-transparent focus:border-orange-300 focus:bg-white outline-none transition-all"
                   placeholder="Cari makanan atau minuman favorit..." type="text" oninput="filterSearch(this.value)"/>
        </div>
        <div class="flex gap-2 overflow-x-auto hide-scrollbar pb-1">
            <a href="dashboard.php" class="chip <?= ($tab === 'semua' && $kategoriMenu === '') ? 'chip-active' : '' ?>">
                <i class="fa-solid fa-house text-sm"></i> Beranda
            </a>
            <a href="?tab=favorit" class="chip <?= $tab === 'favorit' ? 'chip-active' : '' ?>">
                <i class="fa-solid fa-heart text-sm"></i> Favorit <?= count($favorit_ids) ? '(' . count($favorit_ids) . ')' : '' ?>
            </a>
            <a href="?category=Makanan" class="chip <?= $kategoriMenu === 'Makanan' ? 'chip-active' : '' ?>">
                <i class="fa-solid fa-burger text-sm"></i> Makanan
            </a>
            <a href="?category=Minuman" class="chip <?= $kategoriMenu === 'Minuman' ? 'chip-active' : '' ?>">
                <i class="fa-solid fa-mug-hot text-sm"></i> Minuman
            </a>
            <a href="?category=Camilan" class="chip <?= $kategoriMenu === 'Camilan' ? 'chip-active' : '' ?>">
                <i class="fa-solid fa-cookie text-sm"></i> Camilan
            </a>
        </div>
    </div>

    <?php if ($tab === 'favorit'): ?>
    <!-- FAVORIT -->
    <section>
        <div class="flex items-center gap-2 mb-5">
            <div class="w-10 h-10 rounded-xl bg-pink-100 flex items-center justify-center">
                <i class="fa-solid fa-heart text-pink-500 text-lg"></i>
            </div>
            <h2 class="headline font-black text-xl">Menu Favoritmu</h2>
        </div>
        <?php if (!$query_menu || mysqli_num_rows($query_menu) === 0): ?>
        <div class="text-center py-16 bg-white rounded-3xl border-2 border-dashed border-orange-100">
            <div class="floating">
                <div class="w-24 h-24 mx-auto mb-4 rounded-full bg-orange-50 flex items-center justify-center">
                    <i class="fa-solid fa-fire text-4xl text-orange-400"></i>
                </div>
            </div>
            <h3 class="headline font-black text-lg text-gray-600">Belum ada favorit</h3>
            <p class="text-sm text-gray-400 mt-1">Klik hati di menu untuk menambah favorit</p>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4">
            <?php while ($m = mysqli_fetch_assoc($query_menu)) echo renderMenuCard($m, in_array((int)$m['id_menu'], $favorit_ids, true)); ?>
        </div>
        <?php endif; ?>
    </section>
    <?php else: ?>

    <?php if ($kategoriMenu === ''): ?>
    <!-- TERBARU -->
    <section>
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2">
                <div class="w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center">
                    <i class="fa-solid fa-sparkles text-orange-500 text-lg"></i>
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Baru nih!</p>
                    <h2 class="headline font-black text-xl section-title">Menu Terbaru</h2>
                </div>
            </div>
            <span class="bg-orange-50 text-orange-700 px-3 py-1 rounded-full text-xs font-bold">
                <i class="fa-solid fa-bolt mr-1"></i>Fresh
            </span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4">
            <?php while ($m = mysqli_fetch_assoc($query_terbaru)) echo renderMenuCard($m, in_array((int)$m['id_menu'], $favorit_ids, true)); ?>
        </div>
    </section>

    <!-- TERLARIS -->
    <section>
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2">
                <div class="w-10 h-10 rounded-xl bg-yellow-100 flex items-center justify-center">
                    <i class="fa-solid fa-fire text-yellow-500 text-lg"></i>
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Hot item!</p>
                    <h2 class="headline font-black text-xl section-title">Menu Terlaris</h2>
                </div>
            </div>
            <span class="bg-yellow-50 text-yellow-600 px-3 py-1 rounded-full text-xs font-bold">
                <i class="fa-solid fa-crown mr-1"></i>Top Rating
            </span>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4">
            <?php while ($m = mysqli_fetch_assoc($query_terlaris)) echo renderMenuCard($m, in_array((int)$m['id_menu'], $favorit_ids, true)); ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- SEMUA MENU -->
    <section>
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2">
                <div class="w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center">
                    <i class="fa-solid fa-utensils text-orange-500 text-lg"></i>
                </div>
                <h2 class="headline font-black text-xl section-title">Semua Menu</h2>
            </div>
            <a href="semua_menu.php" class="text-sm font-bold text-orange-600 hover:text-orange-700 transition-all flex items-center gap-1">
                Lihat Semua <i class="fa-solid fa-chevron-right text-xs"></i>
            </a>
        </div>
        <?php if ($query_menu && mysqli_num_rows($query_menu) > 0): ?>
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4">
            <?php while ($m = mysqli_fetch_assoc($query_menu)) echo renderMenuCard($m, in_array((int)$m['id_menu'], $favorit_ids, true)); ?>
        </div>
        <?php else: ?>
        <div class="text-center py-12 bg-white rounded-3xl border-2 border-dashed border-orange-100 text-gray-400 font-bold">
            <i class="fa-solid fa-bowl-food text-4xl mb-3"></i>
            <p>Belum ada menu</p>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>
</main>

<script>
async function toggleFavorit(btn, idMenu){
    const icon = btn.querySelector('.material-symbols-outlined');
    const fd = new FormData();
    fd.append('ajax_favorit','1');
    fd.append('id_menu',idMenu);
    fd.append('csrf_token', <?= json_encode($csrfToken) ?>);
    const res = await fetch('dashboard.php',{method:'POST',body:fd});
    const data = await res.json();
    if(data.status === 'added'){
        btn.classList.add('loved','text-red-500');
        icon.style.fontVariationSettings = "'FILL' 1";
        showToast('Ditambahkan ke favorit!');
    }else if(data.status === 'removed'){
        btn.classList.remove('loved','text-red-500');
        icon.style.fontVariationSettings = "'FILL' 0";
        showToast('Dihapus dari favorit');
        if(location.search.includes('tab=favorit')) btn.closest('[data-card]')?.remove();
    }else{
        showToast('Sesi berakhir, muat ulang halaman.');
    }
}

let toastTimer;
function showToast(msg){
    let t=document.getElementById('toast')||createToast();
    t.textContent=msg;
    t.style.display='flex';
    t.style.opacity='1';
    clearTimeout(toastTimer);
    toastTimer=setTimeout(()=>{ t.style.opacity='0'; setTimeout(()=>t.style.display='none',300); },2000);
}
function createToast(){
    const t=document.createElement('div');
    t.id='toast';
    t.className='fixed bottom-24 left-1/2 -translate-x-1/2 bg-gray-900 text-white px-6 py-3 rounded-full text-sm font-bold z-[9999] transition-opacity shadow-2xl flex items-center gap-2';
    document.body.appendChild(t);
    return t;
}

function filterSearch(val){
    val=(val||'').toLowerCase();
    document.querySelectorAll('.menu-card').forEach(card=>{
        card.style.display=card.textContent.toLowerCase().includes(val)?'':'none';
    });
}
</script>

<?php $current_page = 'home'; include(__DIR__ . '/../../includes/navbar.php'); ?>
</body>
</html>
