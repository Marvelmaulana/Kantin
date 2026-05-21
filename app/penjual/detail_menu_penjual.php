<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');
kk_ensure_buyer_schema($koneksi);

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'penjual') {
    header("Location: ../auth/login.php");
    exit;
}

$id_kantin = (int)($_SESSION['id_kantin'] ?? 0);
$id_menu = (int)($_GET['id'] ?? 0);
if ($id_menu <= 0) {
    header("Location: kelola_menu_penjual.php");
    exit;
}

$query = mysqli_query($koneksi, "
    SELECT m.*, k.nama_kantin, k.jam_buka, k.jam_tutup, k.status_buka,
           COALESCE(AVG(u.rating), 0) AS avg_rating,
           COUNT(u.id_ulasan) AS jml_rating
    FROM menu m
    JOIN kantin k ON m.id_kantin = k.id_kantin
    LEFT JOIN ulasan u ON u.id_menu = m.id_menu
    WHERE m.id_menu = $id_menu AND m.id_kantin = $id_kantin
    GROUP BY m.id_menu
");
$data = $query ? mysqli_fetch_assoc($query) : null;
if (!$data) {
    header("Location: kelola_menu_penjual.php");
    exit;
}

$opsiRaw = trim($data['opsi_pilihan'] ?? '');
$opsiList = $opsiRaw !== '' ? array_values(array_filter(array_map('trim', preg_split('/[\r\n,;|]+/', $opsiRaw)))) : [];
$avg = round((float)$data['avg_rating'], 1);
$jml = (int)$data['jml_rating'];
$stok = (int)($data['stok'] ?? 0);
$statusMenu = $data['status'] ?? 'Tersedia';
$isHabis = ($statusMenu === 'Habis' || $stok <= 0);
$stokTerbatas = (!$isHabis && $stok <= 5);

$q_ulasan = mysqli_query($koneksi, "
    SELECT ul.*, u.username, u.foto_profil
    FROM ulasan ul
    LEFT JOIN users u ON ul.id_user = u.id_user
    WHERE ul.id_menu = $id_menu
    ORDER BY ul.created_at DESC
    LIMIT 20
");
$ulasan_list = [];
while ($q_ulasan && ($r = mysqli_fetch_assoc($q_ulasan))) {
    $ulasan_list[] = $r;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= htmlspecialchars($data['nama_menu']); ?> - Detail Menu Penjual</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@700;800;900&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,1,0" />
<style>
body{font-family:'Be Vietnam Pro',sans-serif;background:linear-gradient(135deg,#fff7ed 0%,#fff5eb 35%,#f8fafc 100%);min-height:100vh}
.headline{font-family:'Plus Jakarta Sans',sans-serif}.gradient-text{background:linear-gradient(135deg,#f97316,#b22204);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.material-symbols-outlined{font-variation-settings:'FILL' 1,'wght' 500,'GRAD' 0,'opsz' 24}
</style>
</head>
<body class="text-stone-900 pb-32">

<header class="bg-white/90 backdrop-blur-xl sticky top-0 z-40 px-4 py-3 border-b-2 border-orange-100 shadow-sm">
    <div class="max-w-[1200px] mx-auto flex items-center justify-between">
        <button onclick="goBack()" class="w-11 h-11 rounded-2xl bg-gradient-to-br from-orange-500 to-orange-600 text-white flex items-center justify-center shadow-lg shadow-orange-200 hover:scale-105 transition-all">
            <span class="material-symbols-outlined">arrow_back</span>
        </button>
        <a href="kelola_menu_penjual.php" class="text-sm font-black text-orange-600">Kelola Menu</a>
    </div>
</header>

<main class="max-w-[1200px] mx-auto px-4 py-6 space-y-6">
    <div class="lg:grid lg:grid-cols-2 gap-6 bg-white rounded-3xl p-5 shadow-xl">
        <div class="relative overflow-hidden rounded-3xl bg-orange-50 shadow-xl border border-orange-100">
            <img class="w-full h-[420px] object-cover" src="<?= kk_upload_url($data['foto'] ?? '', 'menu'); ?>" onerror="this.src='../../public/assets/img/default-food.svg'">
            <div class="absolute left-4 bottom-4 bg-white/95 backdrop-blur-lg px-4 py-2 rounded-full flex items-center gap-2 shadow-xl">
                <span class="material-symbols-outlined text-yellow-400 text-xl">star</span>
                <span class="headline font-black text-base"><?= $avg > 0 ? $avg : 'Baru' ?></span>
                <span class="text-xs text-gray-400 font-semibold"><?= $jml ?> ulasan</span>
            </div>
            <?php if ($isHabis): ?>
            <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                <div class="bg-white/95 px-8 py-4 rounded-2xl flex items-center gap-3 shadow-2xl">
                    <span class="material-symbols-outlined text-red-500 text-3xl">cancel</span>
                    <div><p class="font-black text-red-600 text-lg">Stok Habis</p><p class="text-xs text-gray-500">Menu tidak tampil sebagai tersedia.</p></div>
                </div>
            </div>
            <?php elseif ($stokTerbatas): ?>
            <div class="absolute top-4 right-4 bg-amber-500 text-white px-4 py-2 rounded-full flex items-center gap-2 shadow-xl">
                <span class="material-symbols-outlined text-sm">inventory_2</span>
                <span class="text-xs font-black">TERSISA <?= $stok ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="space-y-5 mt-6 lg:mt-0">
            <div class="flex items-center gap-2">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-500 to-orange-600 text-white flex items-center justify-center shadow-lg">
                    <span class="material-symbols-outlined text-sm">storefront</span>
                </div>
                <span class="text-sm font-black text-orange-600 uppercase tracking-wider"><?= htmlspecialchars($data['nama_kantin']); ?></span>
            </div>

            <div>
                <h1 class="headline text-3xl md:text-4xl font-black leading-tight text-gray-900"><?= htmlspecialchars($data['nama_menu']); ?></h1>
                <p class="headline text-3xl md:text-4xl font-black gradient-text mt-2"><?= kk_format_rupiah($data['harga']); ?></p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div class="px-4 py-3 bg-orange-50 border-2 border-orange-100 rounded-2xl">
                    <p class="text-[10px] font-black text-orange-400 uppercase tracking-widest">Status</p>
                    <p class="font-black <?= $isHabis ? 'text-red-600' : 'text-green-600' ?>"><?= htmlspecialchars($statusMenu) ?></p>
                </div>
                <div class="px-4 py-3 bg-blue-50 border-2 border-blue-100 rounded-2xl">
                    <p class="text-[10px] font-black text-blue-400 uppercase tracking-widest">Stok</p>
                    <p class="font-black text-blue-700"><?= number_format($stok) ?> unit</p>
                </div>
            </div>

            <div class="bg-white rounded-3xl border-2 border-orange-100 p-5 shadow-lg">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
                        <i class="fa-solid fa-info-circle text-purple-500 text-sm"></i>
                    </div>
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400">Deskripsi</p>
                </div>
                <p class="text-sm leading-relaxed text-gray-600"><?= nl2br(htmlspecialchars($data['deskripsi'] ?: 'Belum ada deskripsi.')); ?></p>
            </div>

            <?php if (!empty($opsiList)): ?>
            <div class="bg-white rounded-3xl border-2 border-orange-100 p-5 shadow-lg">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                        <i class="fa-solid fa-list-check text-blue-500 text-sm"></i>
                    </div>
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400">Level / Pilihan</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <?php foreach ($opsiList as $opsi): ?>
                    <span class="inline-flex border-2 border-orange-100 bg-orange-50/60 text-gray-700 rounded-2xl px-5 py-2.5 text-sm font-black"><?= htmlspecialchars($opsi) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<section class="max-w-[1200px] mx-auto px-4 pb-6">
    <div class="bg-white rounded-3xl border-2 border-orange-100 p-5 shadow-lg">
        <div class="flex items-center gap-2 mb-4">
            <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center">
                <i class="fa-solid fa-comments text-orange-500 text-sm"></i>
            </div>
            <p class="font-black text-gray-800">Ulasan Pembeli <span class="text-orange-500">(<?= count($ulasan_list) ?>)</span></p>
        </div>

        <?php if (empty($ulasan_list)): ?>
        <div class="text-center py-8 text-gray-400">
            <i class="fa-solid fa-comment-slash text-3xl mb-2 opacity-40"></i>
            <p class="text-sm font-semibold">Belum ada ulasan</p>
        </div>
        <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($ulasan_list as $u): ?>
            <div class="flex gap-3 pb-4 border-b border-gray-100 last:border-0">
                <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center flex-shrink-0 overflow-hidden">
                    <?php if (!empty($u['foto_profil'])): ?>
                    <img src="<?= kk_upload_url($u['foto_profil'], 'profile') ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                    <i class="fa-solid fa-user text-orange-400"></i>
                    <?php endif; ?>
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between gap-3">
                        <p class="font-black text-sm text-gray-900"><?= htmlspecialchars($u['username'] ?? 'Anonim') ?></p>
                        <p class="text-[10px] text-gray-400"><?= !empty($u['created_at']) ? date('d M Y', strtotime($u['created_at'])) : '' ?></p>
                    </div>
                    <div class="flex gap-0.5 my-1">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="material-symbols-outlined text-sm <?= $i <= (int)$u['rating'] ? 'text-yellow-400' : 'text-gray-200' ?>">star</span>
                        <?php endfor; ?>
                    </div>
                    <?php if (!empty($u['komentar'])): ?>
                    <p class="text-sm text-gray-600"><?= htmlspecialchars($u['komentar']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<div class="fixed bottom-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-xl border-t-2 border-orange-100 shadow-[0_-8px_30px_rgba(249,115,22,0.15)]">
    <div class="max-w-[1200px] mx-auto px-4 py-4 grid grid-cols-2 gap-3">
        <a href="edit_menu.php?id=<?= $id_menu ?>" class="h-14 rounded-2xl border-2 border-blue-400 text-blue-600 font-black flex items-center justify-center gap-3 transition-all hover:bg-blue-50">
            <span class="material-symbols-outlined">edit_note</span>
            <span>Edit Menu</span>
        </a>
        <a href="proses_menu.php?aksi=hapus&id=<?= $id_menu ?>" onclick="return confirm('Hapus menu ini?')" class="h-14 rounded-2xl bg-gradient-to-r from-red-500 to-red-600 text-white font-black shadow-xl shadow-red-100 flex items-center justify-center gap-3 transition-all hover:from-red-600 hover:to-red-700">
            <span class="material-symbols-outlined">delete_forever</span>
            <span>Hapus</span>
        </a>
    </div>
</div>

<script>
function goBack() {
    if (history.length > 1) history.back();
    else location.href = 'kelola_menu_penjual.php';
}
</script>
</body>
</html>
