<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'pembeli') {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

// Toggle favorit via AJAX
if (isset($_POST['ajax_favorit'])) {
    header('Content-Type: application/json');
    $id_menu = (int)$_POST['id_menu'];
    $cek = mysqli_query($koneksi, "SELECT id_favorit FROM favorit WHERE id_user='$id_user' AND id_menu='$id_menu'");
    if (mysqli_num_rows($cek) > 0) {
        mysqli_query($koneksi, "DELETE FROM favorit WHERE id_user='$id_user' AND id_menu='$id_menu'");
        echo json_encode(['status' => 'removed']);
    } else {
        mysqli_query($koneksi, "INSERT INTO favorit (id_user, id_menu) VALUES ('$id_user', '$id_menu')");
        echo json_encode(['status' => 'added']);
    }
    exit();
}

// Ambil id menu yang sudah difavoritkan user ini
$favorit_ids = [];
$q_fav = mysqli_query($koneksi, "SELECT id_menu FROM favorit WHERE id_user='$id_user'");
while ($f = mysqli_fetch_assoc($q_fav)) $favorit_ids[] = $f['id_menu'];

// Kategori & tab aktif
$tab      = isset($_GET['tab'])      ? $_GET['tab']      : 'home';
$kategori = isset($_GET['category']) ? $_GET['category'] : '';

// Query menu utama
if ($tab === 'favorit') {
    if (empty($favorit_ids)) {
        $query_menu = null;
        $total_fav  = 0;
    } else {
        $fav_in = implode(',', $favorit_ids);
        $query_menu = mysqli_query($koneksi, "
            SELECT menu.*, 
                   COALESCE(AVG(rating_menu.nilai_rating), 0) AS avg_rating,
                   COUNT(rating_menu.id_rating) AS jml_rating
            FROM menu
            LEFT JOIN rating_menu ON menu.id_menu = rating_menu.id_menu
            WHERE menu.id_menu IN ($fav_in)
            GROUP BY menu.id_menu
            ORDER BY menu.id_menu DESC
        ");
        $total_fav = mysqli_num_rows($query_menu);
    }
} elseif ($kategori != '') {
    $kat = mysqli_real_escape_string($koneksi, $kategori);
    $query_menu = mysqli_query($koneksi, "
        SELECT menu.*,
               COALESCE(AVG(rating_menu.nilai_rating), 0) AS avg_rating,
               COUNT(rating_menu.id_rating) AS jml_rating
        FROM menu
        LEFT JOIN rating_menu ON menu.id_menu = rating_menu.id_menu
        WHERE menu.kategori = '$kat'
        GROUP BY menu.id_menu
        ORDER BY menu.id_menu DESC
        LIMIT 12
    ");
} else {
    $query_menu = mysqli_query($koneksi, "
        SELECT menu.*,
               COALESCE(AVG(rating_menu.nilai_rating), 0) AS avg_rating,
               COUNT(rating_menu.id_rating) AS jml_rating
        FROM menu
        LEFT JOIN rating_menu ON menu.id_menu = rating_menu.id_menu
        GROUP BY menu.id_menu
        ORDER BY menu.id_menu DESC
        LIMIT 8
    ");
}

// Menu rekomendasi (rating tertinggi, min 1 rating)
$query_rekomendasi = mysqli_query($koneksi, "
    SELECT menu.*,
           AVG(rating_menu.nilai_rating) AS avg_rating,
           COUNT(rating_menu.id_rating)  AS jml_rating
    FROM menu
    JOIN rating_menu ON menu.id_menu = rating_menu.id_menu
    GROUP BY menu.id_menu
    HAVING jml_rating >= 1
    ORDER BY avg_rating DESC, jml_rating DESC
    LIMIT 6
");

// Kantin
$query_kantin = mysqli_query($koneksi, "SELECT * FROM kantin LIMIT 6");

// Keranjang badge
$q_cart = mysqli_query($koneksi, "SELECT SUM(qty) as total FROM keranjang WHERE id_user='$id_user'");
$d_cart = mysqli_fetch_assoc($q_cart);
$jml_keranjang = ($d_cart['total'] > 0) ? $d_cart['total'] : 0;

// Nama user
$q_user = mysqli_query($koneksi, "SELECT username FROM users WHERE id_user='$id_user'");
$d_user = mysqli_fetch_assoc($q_user);
$nama   = explode(' ', $d_user['username'])[0]; // Ambil nama depan
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Kantin Kita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800;900&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@400,1&display=swap" rel="stylesheet"/>
    <style>
        body { font-family: 'Be Vietnam Pro', sans-serif; background: #fffdfc; padding-bottom: 120px; }
        .headline { font-family: 'Plus Jakarta Sans', sans-serif; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }

        /* Floating Nav */
        .floating-nav {
            position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
            background: rgba(255,255,255,0.97); backdrop-filter: blur(12px);
            height: 68px; display: flex; justify-content: space-around; align-items: center;
            z-index: 50; box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            border-radius: 24px; width: 88%; max-width: 440px;
            border: 1px solid rgba(0,0,0,0.06);
        }
        .nav-item { display: flex; flex-direction: column; align-items: center; gap: 2px; padding: 8px 20px; border-radius: 16px; transition: all 0.2s; text-decoration: none; }
        .nav-item.active { background: #fff0ed; }
        .nav-item span.label { font-size: 10px; font-weight: 700; color: #a8a29e; }
        .nav-item.active span.label { color: #b22204; }
        .nav-item .material-symbols-outlined { color: #a8a29e; font-size: 22px; }
        .nav-item.active .material-symbols-outlined { color: #b22204; }

        /* Love button */
        .love-btn { transition: transform 0.2s, color 0.2s; }
        .love-btn:active { transform: scale(1.3); }
        .love-btn.loved { color: #ef4444 !important; }

        /* Banner gradient */
        .banner-grad { background: linear-gradient(135deg, #b22204 0%, #ff6b35 100%); }

        /* Card hover */
        .menu-card { transition: transform 0.2s, box-shadow 0.2s; }
        .menu-card:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(178,34,4,0.12); }

        /* Skeleton */
        @keyframes shimmer { 0%{background-position:-200% 0} 100%{background-position:200% 0} }
        .skeleton { background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%); background-size: 200%; animation: shimmer 1.5s infinite; border-radius: 8px; }

        /* Star rating */
        .stars-display { display: flex; gap: 1px; }
        .star-filled { color: #f59e0b; }
        .star-empty  { color: #d1d5db; }

        /* Chip kategori aktif */
        .chip { padding: 8px 18px; border-radius: 12px; font-size: 12px; font-weight: 700; white-space: nowrap; transition: all 0.2s; text-decoration: none; }
        .chip-active { background: #b22204; color: white; box-shadow: 0 4px 12px rgba(178,34,4,0.3); }
        .chip-inactive { background: #fff0ed; color: #78716c; }
        .chip-inactive:hover { background: #ffe4de; }
    </style>
</head>
<body class="text-stone-800">

<!-- HEADER -->
<header class="bg-white sticky top-0 z-40 px-5 py-3 flex items-center justify-between border-b border-stone-100">
    <div>
        <p class="text-[11px] text-stone-400 font-medium">Selamat datang 👋</p>
        <span class="text-base font-extrabold text-[#b22204] headline uppercase italic">Kantin Kita</span>
    </div>
    <div class="flex items-center gap-3">
        <a href="keranjang.php" class="relative text-stone-600 w-10 h-10 flex items-center justify-center rounded-full hover:bg-stone-100">
            <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 0">shopping_bag</span>
            <?php if ($jml_keranjang > 0): ?>
            <span class="absolute -top-0.5 -right-0.5 bg-[#b22204] text-white text-[9px] w-4 h-4 flex items-center justify-center rounded-full font-bold"><?= $jml_keranjang ?></span>
            <?php endif; ?>
        </a>
        <a href="profil.php" class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center text-[#b22204] font-bold text-sm headline">
            <?= strtoupper(substr($nama, 0, 1)) ?>
        </a>
    </div>
</header>

<main class="max-w-2xl mx-auto px-4 py-5 space-y-7">

    <!-- SEARCH BAR -->
    <div class="relative">
        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-stone-400 text-xl">search</span>
        <input class="w-full pl-12 pr-4 py-3 bg-stone-100 rounded-2xl text-sm border-none outline-none focus:ring-2 focus:ring-[#b22204]/30"
               placeholder="Cari makanan favoritmu..." type="text"
               oninput="filterSearch(this.value)"/>
    </div>

    <!-- BANNER PROMO -->
    <?php if ($tab !== 'favorit' && $kategori === ''): ?>
    <div class="banner-grad rounded-3xl p-5 flex items-center justify-between overflow-hidden relative">
        <div class="absolute -right-8 -top-8 w-36 h-36 bg-white/10 rounded-full"></div>
        <div class="absolute -right-2 bottom-0 w-24 h-24 bg-white/10 rounded-full"></div>
        <div class="relative z-10">
            <p class="text-white/80 text-xs font-semibold">Promo Hari Ini 🎉</p>
            <h2 class="headline font-black text-white text-xl leading-tight mt-0.5">Makan Enak,<br>Harga Hemat!</h2>
            <p class="text-white/70 text-[11px] mt-1">Pesan sekarang & nikmati menu favorit</p>
            <a href="?category=Makanan" class="inline-block mt-3 bg-white text-[#b22204] text-xs font-bold px-4 py-2 rounded-full">
                Lihat Menu →
            </a>
        </div>
        <div class="text-6xl relative z-10 select-none">🍱</div>
    </div>
    <?php endif; ?>

    <!-- CHIP KATEGORI -->
    <div class="flex gap-2 overflow-x-auto hide-scrollbar pb-1">
        <a href="dashboard.php" class="chip <?= ($tab === 'home' && $kategori === '') ? 'chip-active' : 'chip-inactive' ?>">🏠 Beranda</a>
        <a href="?tab=favorit" class="chip <?= $tab === 'favorit' ? 'chip-active' : 'chip-inactive' ?>">
            ❤️ Favorit <?= count($favorit_ids) > 0 ? '('.count($favorit_ids).')' : '' ?>
        </a>
        <a href="?category=Makanan" class="chip <?= $kategori === 'Makanan' ? 'chip-active' : 'chip-inactive' ?>">🍛 Makanan</a>
        <a href="?category=Minuman" class="chip <?= $kategori === 'Minuman' ? 'chip-active' : 'chip-inactive' ?>">🥤 Minuman</a>
        <a href="?category=Camilan" class="chip <?= $kategori === 'Camilan' ? 'chip-active' : 'chip-inactive' ?>">🍿 Cemilan</a>
    </div>

    <!-- ===================== TAB FAVORIT ===================== -->
    <?php if ($tab === 'favorit'): ?>
    <section>
        <div class="flex items-center gap-2 mb-4">
            <span class="material-symbols-outlined text-[#b22204]" style="font-variation-settings:'FILL' 1">favorite</span>
            <h3 class="headline font-bold text-lg">Menu Favoritmu</h3>
        </div>

        <?php if (empty($favorit_ids)): ?>
        <div class="text-center py-16 bg-white rounded-3xl border-2 border-dashed border-stone-200">
            <div class="text-5xl mb-3">💔</div>
            <p class="font-bold text-stone-500">Belum ada menu favorit</p>
            <p class="text-xs text-stone-400 mt-1">Tekan ❤️ di menu yang kamu suka</p>
            <a href="dashboard.php" class="inline-block mt-4 bg-[#b22204] text-white text-xs font-bold px-5 py-2.5 rounded-full">
                Jelajahi Menu
            </a>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-2 gap-3" id="menu-grid">
            <?php while ($m = mysqli_fetch_assoc($query_menu)): 
                $is_fav   = in_array($m['id_menu'], $favorit_ids);
                $avg      = round($m['avg_rating'], 1);
                $jml      = $m['jml_rating'];
            ?>
            <?= renderMenuCard($m, $is_fav, $avg, $jml) ?>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- ===================== TAB KATEGORI / HOME ===================== -->
    <?php else: ?>

    <!-- REKOMENDASI (hanya di home) -->
    <?php if ($kategori === '' && mysqli_num_rows($query_rekomendasi) > 0): ?>
    <section>
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <span class="text-xl">⭐</span>
                <h3 class="headline font-bold text-base">Direkomendasikan</h3>
            </div>
            <span class="text-[10px] text-stone-400 bg-yellow-50 px-2 py-1 rounded-full font-bold">Rating tertinggi</span>
        </div>
        <div class="flex gap-3 overflow-x-auto hide-scrollbar pb-1">
            <?php while ($m = mysqli_fetch_assoc($query_rekomendasi)):
                $is_fav = in_array($m['id_menu'], $favorit_ids);
                $avg    = round($m['avg_rating'], 1);
                $jml    = $m['jml_rating'];
            ?>
            <div class="menu-card bg-white rounded-[1.5rem] overflow-hidden shadow-sm border border-stone-50 flex-shrink-0 w-40 relative">
                <!-- Love -->
                <button onclick="toggleFavorit(this, <?= $m['id_menu'] ?>)"
                        class="love-btn absolute top-2 right-2 z-10 w-7 h-7 bg-white/90 rounded-full flex items-center justify-center shadow-sm <?= $is_fav ? 'loved' : 'text-stone-300' ?>">
                    <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' <?= $is_fav ? '1' : '0' ?>">favorite</span>
                </button>
                <!-- Badge rating -->
                <div class="absolute top-2 left-2 z-10 bg-white/90 backdrop-blur-sm px-1.5 py-0.5 rounded-full flex items-center gap-0.5">
                    <span class="material-symbols-outlined text-yellow-400 text-[11px]" style="font-variation-settings:'FILL' 1">star</span>
                    <span class="text-[10px] font-black text-stone-700"><?= $avg > 0 ? $avg : 'Baru' ?></span>
                </div>
                <a href="detail_menu.php?id=<?= $m['id_menu'] ?>">
                    <img class="w-full h-36 object-cover" src="../../uploads/<?= $m['foto'] ?>"
                         onerror="this.src='../../assets/img/default-food.jpg'"/>
                    <div class="p-3">
                        <h4 class="font-bold text-xs line-clamp-1"><?= htmlspecialchars($m['nama_menu']) ?></h4>
                        <p class="text-[10px] text-stone-400 mt-0.5"><?= $jml ?> ulasan</p>
                        <p class="text-[#b22204] font-black text-sm mt-2">Rp <?= number_format($m['harga'], 0, ',', '.') ?></p>
                    </div>
                </a>
            </div>
            <?php endwhile; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- MENU TERBARU / KATEGORI -->
    <section>
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <span class="text-xl"><?= $kategori === '' ? '🆕' : ($kategori === 'Makanan' ? '🍛' : ($kategori === 'Minuman' ? '🥤' : '🍿')) ?></span>
                <h3 class="headline font-bold text-base"><?= $kategori === '' ? 'Menu Terbaru' : $kategori ?></h3>
            </div>
            <?php if ($kategori === ''): ?>
            <a href="semua_menu.php" class="text-[11px] font-bold text-[#b22204]">Lihat Semua →</a>
            <?php endif; ?>
        </div>

        <?php if ($query_menu && mysqli_num_rows($query_menu) > 0): ?>
        <div class="grid grid-cols-2 gap-3" id="menu-grid">
            <?php while ($m = mysqli_fetch_assoc($query_menu)):
                $is_fav = in_array($m['id_menu'], $favorit_ids);
                $avg    = round($m['avg_rating'], 1);
                $jml    = $m['jml_rating'];
            ?>
            <?= renderMenuCard($m, $is_fav, $avg, $jml) ?>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-12 bg-white rounded-2xl border border-dashed border-stone-200">
            <p class="text-stone-400 text-sm">Belum ada menu di kategori ini</p>
        </div>
        <?php endif; ?>
    </section>

    <!-- KANTIN POPULER (hanya di home) -->
    <?php if ($kategori === ''): ?>
    <section>
        <div class="flex items-center gap-2 mb-4">
            <span class="text-xl">🏪</span>
            <h3 class="headline font-bold text-base">Daftar Kantin</h3>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <?php while ($k = mysqli_fetch_assoc($query_kantin)): ?>
            <a href="kantin_detail.php?id=<?= $k['id_kantin'] ?>"
               class="bg-white p-4 rounded-2xl flex items-center gap-3 border border-stone-100 shadow-sm hover:border-[#b22204]/30 hover:shadow-md transition-all group">
                <div class="w-11 h-11 bg-gradient-to-br from-orange-100 to-red-100 rounded-xl flex items-center justify-center text-[#b22204] flex-shrink-0 group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1">storefront</span>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-bold line-clamp-1"><?= htmlspecialchars($k['nama_kantin']) ?></p>
                    <p class="text-[10px] text-stone-400 mt-0.5">Buka sekarang</p>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php endif; // end tab home ?>

</main>

<!-- FLOATING NAV -->
<nav class="floating-nav">
    <a href="dashboard.php" class="nav-item <?= $tab !== 'favorit' ? 'active' : '' ?>">
        <span class="material-symbols-outlined" style="font-variation-settings:'FILL' <?= $tab !== 'favorit' ? '1' : '0' ?>">home</span>
        <span class="label">Home</span>
    </a>
    <a href="orders.php" class="nav-item">
        <span class="material-symbols-outlined">receipt_long</span>
        <span class="label">Orders</span>
    </a>
    <a href="riwayat.php" class="nav-item">
        <span class="material-symbols-outlined">history</span>
        <span class="label">History</span>
    </a>
    <a href="profil.php" class="nav-item">
        <span class="material-symbols-outlined">person</span>
        <span class="label">Profile</span>
    </a>
</nav>

<script>
// ===== TOGGLE FAVORIT via AJAX =====
async function toggleFavorit(btn, idMenu) {
    const icon = btn.querySelector('.material-symbols-outlined');
    const fd   = new FormData();
    fd.append('ajax_favorit', '1');
    fd.append('id_menu', idMenu);

    const res  = await fetch('dashboard.php', { method: 'POST', body: fd });
    const data = await res.json();

    if (data.status === 'added') {
        btn.classList.add('loved');
        icon.style.fontVariationSettings = "'FILL' 1";
        // animasi pop
        btn.style.transform = 'scale(1.4)';
        setTimeout(() => btn.style.transform = 'scale(1)', 200);
        showToast('❤️ Ditambahkan ke favorit!');
    } else {
        btn.classList.remove('loved');
        icon.style.fontVariationSettings = "'FILL' 0";
        icon.style.color = '';
        showToast('💔 Dihapus dari favorit');
        // Di tab favorit: hapus card dari DOM
        const card = btn.closest('.menu-card, [data-card]');
        if (window.location.search.includes('tab=favorit') && card) {
            card.style.transition = 'all 0.3s';
            card.style.opacity    = '0';
            card.style.transform  = 'scale(0.8)';
            setTimeout(() => card.remove(), 300);
        }
    }
}

// ===== TOAST NOTIFIKASI =====
function showToast(msg) {
    let t = document.getElementById('toast');
    if (!t) {
        t = document.createElement('div');
        t.id = 'toast';
        t.style.cssText = 'position:fixed;bottom:100px;left:50%;transform:translateX(-50%);background:#1c1917;color:white;padding:10px 20px;border-radius:99px;font-size:13px;font-weight:600;z-index:9999;transition:opacity 0.3s;white-space:nowrap;';
        document.body.appendChild(t);
    }
    t.textContent = msg;
    t.style.opacity = '1';
    clearTimeout(window._toastTimer);
    window._toastTimer = setTimeout(() => t.style.opacity = '0', 2000);
}

// ===== SEARCH FILTER (client-side sederhana) =====
function filterSearch(val) {
    const cards = document.querySelectorAll('#menu-grid > div');
    val = val.toLowerCase();
    cards.forEach(c => {
        const nama = c.querySelector('h4')?.textContent.toLowerCase() || '';
        c.style.display = nama.includes(val) ? '' : 'none';
    });
}
</script>

<?php
// Helper function render card menu
function renderMenuCard($m, $is_fav, $avg, $jml) {
    $harga   = number_format($m['harga'], 0, ',', '.');
    $nama    = htmlspecialchars($m['nama_menu']);
    $foto    = htmlspecialchars($m['foto']);
    $id      = $m['id_menu'];
    $filled  = $is_fav ? '1' : '0';
    $loved   = $is_fav ? 'loved' : 'text-stone-300';
    $ratingTxt = $avg > 0 ? $avg : 'Baru';
    $ulasanTxt = $jml > 0 ? $jml . ' ulasan' : 'Belum ada ulasan';

    return "
    <div class='menu-card bg-white rounded-[1.5rem] overflow-hidden shadow-sm border border-stone-50 relative' data-card>
        <button onclick='toggleFavorit(this, $id)'
                class='love-btn absolute top-2 right-2 z-10 w-8 h-8 bg-white/90 rounded-full flex items-center justify-center shadow-sm $loved'>
            <span class='material-symbols-outlined text-sm' style='font-variation-settings:\"FILL\" $filled'>favorite</span>
        </button>
        <a href='detail_menu.php?id=$id'>
            <div class='relative'>
                <img class='w-full aspect-square object-cover' src='../../uploads/$foto'
                     onerror='this.src=\"../../assets/img/default-food.jpg\"'/>
                " . ($avg > 0 ? "
                <div class='absolute bottom-2 left-2 bg-white/90 backdrop-blur-sm px-1.5 py-0.5 rounded-full flex items-center gap-0.5'>
                    <span class='material-symbols-outlined text-yellow-400 text-[11px]' style='font-variation-settings:\"FILL\" 1'>star</span>
                    <span class='text-[10px] font-black text-stone-700'>$ratingTxt</span>
                </div>" : "") . "
            </div>
            <div class='p-3'>
                <h4 class='font-bold text-xs line-clamp-1'>$nama</h4>
                <p class='text-[10px] text-stone-400 mt-0.5'>$ulasanTxt</p>
                <div class='flex items-center justify-between mt-2'>
                    <span class='text-[#b22204] font-black text-sm'>Rp $harga</span>
                    <div class='w-7 h-7 bg-[#b22204] rounded-full flex items-center justify-center text-white shadow-sm'>
                        <span class='material-symbols-outlined text-sm'>add</span>
                    </div>
                </div>
            </div>
        </a>
    </div>";
}
?>

</body>
</html>