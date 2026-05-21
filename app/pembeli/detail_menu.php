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
$id_menu = (int)($_GET['id'] ?? 0);
if ($id_menu <= 0) {
    header("Location: dashboard.php");
    exit();
}

$query = mysqli_query($koneksi, "
    SELECT m.*, k.nama_kantin, k.jam_buka, k.jam_tutup, k.status_buka,
           COALESCE(AVG(rm.rating),0) AS avg_rating,
COUNT(rm.id_ulasan) AS jml_rating

    FROM menu m
    JOIN kantin k ON m.id_kantin = k.id_kantin
    LEFT JOIN ulasan rm ON m.id_menu = rm.id_menu
    WHERE m.id_menu = $id_menu
    GROUP BY m.id_menu
");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    header("Location: dashboard.php");
    exit();
}

// Check kantin operational hours
$jamBuka = $data['jam_buka'] ?? '07:00';
$jamTutup = $data['jam_tutup'] ?? '15:00';
$statusBuka = $data['status_buka'] ?? 'Buka';
$isLuarJam = !kk_is_kantin_open($data);

$opsiRaw = trim($data['opsi_pilihan'] ?? '');
$opsiList = $opsiRaw !== '' ? array_values(array_filter(array_map('trim', preg_split('/[\r\n,;|]+/', $opsiRaw)))) : [];
$avg = round((float)$data['avg_rating'], 1);
$jml = (int)$data['jml_rating'];
$stok = (int)($data['stok'] ?? 0);
$statusMenu = $data['status'] ?? 'Tersedia';
$isHabis = ($statusMenu === 'Habis' || $stok <= 0);
$stokTerbatas = (!$isHabis && $stok <= 5);
$csrfToken = kk_csrf_token();

// Query ulasan
$q_ulasan = mysqli_query($koneksi, "
    SELECT ul.*, u.username, u.foto_profil
    FROM ulasan ul
    LEFT JOIN users u ON ul.id_user = u.id_user
    WHERE ul.id_menu = $id_menu
    ORDER BY ul.created_at DESC
    LIMIT 20
");

$ulasan_list = [];
while ($r = mysqli_fetch_assoc($q_ulasan)) $ulasan_list[] = $r;

// Cek apakah user sudah pernah ulas
$sudah_ulas = false;
$q_cek = mysqli_query($koneksi, "
    SELECT id_ulasan FROM ulasan 
    WHERE id_menu = $id_menu AND id_user = $id_user LIMIT 1
");
if (mysqli_num_rows($q_cek) > 0) $sudah_ulas = true;

// Cek apakah user pernah beli menu ini
$pernah_beli = false;
$q_beli = mysqli_query($koneksi, "
    SELECT dp.id_detail FROM detail_pesanan dp
    JOIN pesanan p ON dp.id_pesanan = p.id_pesanan
    WHERE p.id_user = $id_user AND dp.id_menu = $id_menu
    AND p.status = 'Selesai' LIMIT 1
");
if (mysqli_num_rows($q_beli) > 0) $pernah_beli = true;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= htmlspecialchars($data['nama_menu']); ?> - Kantin Kita</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@700;800;900&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,1,0" />
<style>
* { scrollbar-width: thin; scrollbar-color: #fed7aa transparent; }
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-thumb { background: #fed7aa; border-radius: 10px; }

body {
    font-family: 'Be Vietnam Pro', sans-serif;
    background: linear-gradient(135deg, #fff7ed 0%, #fff5eb 25%, #ffe4e1 50%, #f0f4ff 75%, #e8f5e9 100%);
    min-height: 100vh;
}

body::before {
    content: '';
    position: fixed;
    inset: 0;
    background:
        radial-gradient(circle at 20% 10%, rgba(249,115,22,0.07) 0%, transparent 40%),
        radial-gradient(circle at 80% 90%, rgba(139,92,246,0.05) 0%, transparent 40%);
    z-index: -1;
}

.headline { font-family: 'Plus Jakarta Sans', sans-serif; }
.material-symbols-outlined { font-variation-settings: 'FILL' 1,'wght' 500,'GRAD' 0,'opsz' 24; }

.gradient-text {
    background: linear-gradient(135deg, #f97316, #ea580c);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.option-chip input:checked + span {
    background: linear-gradient(135deg, #f97316, #ea580c);
    color: white;
    border-color: #f97316;
    box-shadow: 0 8px 20px rgba(249,115,22,0.25);
}

.floating { animation: floating 3s ease-in-out infinite; }
@keyframes floating {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-8px) rotate(1deg); }
}
</style>
</head>
<body class="text-stone-900 pb-36">

<!-- HEADER -->
<header class="bg-white/90 backdrop-blur-xl sticky top-0 z-40 px-4 py-3 border-b-2 border-orange-100 shadow-sm">
    <div class="max-w-[1200px] mx-auto flex items-center justify-between">
        <button onclick="goBack()" class="w-11 h-11 rounded-2xl bg-gradient-to-br from-orange-500 to-orange-600 text-white flex items-center justify-center shadow-lg shadow-orange-200 hover:scale-105 transition-all">
            <span class="material-symbols-outlined">arrow_back</span>
        </button>
        <div class="w-11 h-11"></div>
    </div>
</header>

<script>
function goBack() {
    if (history.length > 1) {
        history.back();
    } else {
        location.href = 'dashboard.php';
    }
}
</script>

<main class="max-w-[1200px] mx-auto px-4 py-6 space-y-6">
    <!-- FOTO + INFO UTAMA -->
    <div class="lg:grid lg:grid-cols-2 gap-6 bg-white rounded-3xl p-5 shadow-xl">
        <div class="relative">
                <div class="relative overflow-hidden rounded-3xl bg-orange-50 shadow-xl border border-orange-100">

                <img class="w-full h-[420px] object-cover" src="<?= kk_upload_url($data['foto'] ?? '', 'menu'); ?>"
        onerror="this.src='../../public/assets/img/default-food.svg'"
    >

                <!-- Rating Badge -->
                <div class="absolute left-4 bottom-4 bg-white/95 backdrop-blur-lg px-4 py-2 rounded-full flex items-center gap-2 shadow-xl">
                    <span class="material-symbols-outlined text-yellow-400 text-xl">star</span>
                    <span class="headline font-black text-base"><?= $avg > 0 ? $avg : 'Baru' ?></span>
                    <span class="text-xs text-gray-400 font-semibold"><?= $jml ?> ulasan</span>
                </div>
                <!-- Habis Overlay -->
                <?php if ($isHabis): ?>
                <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                    <div class="bg-white/95 px-8 py-4 rounded-2xl flex items-center gap-3 shadow-2xl">
                        <span class="material-symbols-outlined text-red-500 text-3xl">cancel</span>
                        <div>
                            <p class="font-black text-red-600 text-lg">Stok Habis</p>
                            <p class="text-xs text-gray-500">Segera kembali nanti ya!</p>
                        </div>
                    </div>
                </div>
                <?php elseif ($stokTerbatas): ?>
                <div class="absolute top-4 right-4 bg-amber-500 text-white px-4 py-2 rounded-full flex items-center gap-2 shadow-xl animate-pulse">
                    <span class="material-symbols-outlined text-sm">local_fire_department</span>
                    <span class="text-xs font-black">TERSISA <?= $stok ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="space-y-5">
            <!-- Nama Kantin -->
            <div class="flex items-center gap-2">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-500 to-orange-600 text-white flex items-center justify-center shadow-lg">
                    <span class="material-symbols-outlined text-sm">storefront</span>
                </div>
                <span class="text-sm font-black text-orange-600 uppercase tracking-wider"><?= htmlspecialchars($data['nama_kantin']); ?></span>
            </div>

            <!-- Nama Menu -->
            <div>
                <h1 class="headline text-3xl md:text-4xl font-black leading-tight text-gray-900"><?= htmlspecialchars($data['nama_menu']); ?></h1>
                <p class="headline text-3xl md:text-4xl font-black gradient-text mt-2"><?= kk_format_rupiah($data['harga']); ?></p>
            </div>

            <!-- Stok Warning -->
            <?php if ($isHabis): ?>
            <div class="px-4 py-3 bg-red-50 border-2 border-red-200 rounded-2xl flex items-center gap-3">
                <span class="material-symbols-outlined text-red-500 text-2xl">error</span>
                <div>
                    <p class="font-black text-red-600 text-sm">Maaf, menu ini sedang habis</p>
                    <p class="text-xs text-red-400">Coba menu lain ya!</p>
                </div>
            </div>
            <?php elseif ($stokTerbatas): ?>
            <div class="px-4 py-3 bg-amber-50 border-2 border-amber-200 rounded-2xl flex items-center gap-3">
                <span class="material-symbols-outlined text-amber-500 text-2xl">warning</span>
                <div>
                    <p class="font-black text-amber-700 text-sm">Stok Terbatas!</p>
                    <p class="text-xs text-amber-500">Tersisa <?= $stok ?> unit</p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Jam Operasional Warning -->
            <?php if ($isLuarJam): ?>
            <div class="px-4 py-3 bg-purple-50 border-2 border-purple-200 rounded-2xl flex items-center gap-3">
                <span class="material-symbols-outlined text-purple-500 text-2xl">schedule</span>
                <div>
                    <p class="font-black text-purple-700 text-sm">Luar Jam Operasional</p>
                    <p class="text-xs text-purple-500">Buka <?= date('H:i', strtotime($jamBuka)) ?> - <?= date('H:i', strtotime($jamTutup)) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Deskripsi -->
            <div class="bg-white rounded-3xl border-2 border-orange-100 p-5 shadow-lg">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
                        <i class="fa-solid fa-info-circle text-purple-500 text-sm"></i>
                    </div>
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400">Deskripsi</p>
                </div>
                <p class="text-sm leading-relaxed text-gray-600">
                    <?= nl2br(htmlspecialchars($data['deskripsi'] ?: "Sajian favorit dari {$data['nama_kantin']}, dibuat fresh setelah kamu pesan.")); ?>
                </p>
            </div>

            <!-- Level / Pilihan -->
            <?php if (!empty($opsiList)): ?>
            <div class="bg-white rounded-3xl border-2 border-orange-100 p-5 shadow-lg">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                        <i class="fa-solid fa-list-check text-blue-500 text-sm"></i>
                    </div>
                    <p class="text-xs font-black uppercase tracking-widest text-gray-400">Level / Pilihan</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <?php foreach ($opsiList as $i => $opsi): ?>
                    <label class="option-chip cursor-pointer">
                        <input type="radio" name="opsi_pilihan" value="<?= htmlspecialchars($opsi) ?>" class="hidden" <?= $i === 0 ? 'checked' : '' ?>>
                        <span class="inline-flex border-2 border-orange-100 bg-orange-50/60 text-gray-700 rounded-2xl px-5 py-2.5 text-sm font-black transition-all hover:border-orange-300"><?= htmlspecialchars($opsi) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Jumlah -->
            <div class="bg-white rounded-3xl border-2 border-orange-100 p-5 shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center">
                            <i class="fa-solid fa-hashtag text-green-500 text-lg"></i>
                        </div>
                        <span class="text-sm font-black text-gray-600">Jumlah</span>
                    </div>
                    <div class="flex items-center bg-gray-100 rounded-full p-1 <?= $isHabis ? 'opacity-50' : '' ?>">
                        <button onclick="changeQty(-1)" id="btnMinus" class="w-10 h-10 rounded-full bg-white shadow-sm font-black text-gray-600 hover:bg-gray-50 transition-all <?= $isHabis ? 'opacity-40 cursor-not-allowed' : '' ?>" <?= $isHabis ? 'disabled' : '' ?>>−</button>
                        <span id="display_qty" class="w-14 text-center headline font-black text-lg">1</span>
                        <button onclick="changeQty(1)" id="btnPlus" class="w-10 h-10 rounded-full bg-gradient-to-r from-orange-500 to-orange-600 text-white shadow-sm font-black hover:from-orange-600 hover:to-orange-700 transition-all <?= $isHabis ? 'opacity-40 cursor-not-allowed' : '' ?>" <?= $isHabis ? 'disabled' : '' ?>>+</button>
                    </div>
                </div>
                <div id="stok_warning" class="hidden text-center text-xs text-red-500 font-bold mt-3 px-4 py-2 bg-red-50 rounded-xl flex items-center justify-center gap-2">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span id="stok_warning_text">Stok tinggal</span>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- SECTION ULASAN -->
<div class="max-w-[1200px] mx-auto px-4 pb-6 space-y-4">

    <!-- Form Beri Ulasan -->
    <?php if ($pernah_beli && !$sudah_ulas): ?>
    <div class="bg-white rounded-3xl border-2 border-orange-100 p-5 shadow-lg">
        <div class="flex items-center gap-2 mb-4">
            <div class="w-8 h-8 rounded-lg bg-yellow-100 flex items-center justify-center">
                <i class="fa-solid fa-star text-yellow-500 text-sm"></i>
            </div>
            <p class="font-black text-gray-800">Beri Ulasan</p>
        </div>

        <form id="formUlasan" onsubmit="kirimUlasan(event)">
            <!-- Bintang -->
            <div class="flex gap-2 mb-4" id="starContainer">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <button type="button" onclick="setBintang(<?= $i ?>)"
                        class="star-btn text-3xl text-gray-300 hover:text-yellow-400 transition-all"
                        data-val="<?= $i ?>">★</button>
                <?php endfor; ?>
            </div>

            <textarea id="komentarInput" rows="3" placeholder="Tulis ulasanmu di sini..."
                      class="w-full rounded-2xl bg-gray-50 border-2 border-transparent 
                             focus:border-orange-300 focus:bg-white p-4 text-sm 
                             resize-none outline-none transition-all mb-3"></textarea>

            <button type="submit"
                    class="w-full h-12 rounded-2xl bg-gradient-to-r from-orange-500 to-orange-600 
                           text-white font-black shadow-lg shadow-orange-200 
                           hover:from-orange-600 hover:to-orange-700 transition-all">
                Kirim Ulasan
            </button>
        </form>
    </div>
    <?php elseif ($sudah_ulas): ?>
    <div class="bg-green-50 border-2 border-green-200 rounded-3xl p-4 flex items-center gap-3">
        <i class="fa-solid fa-circle-check text-green-500 text-xl"></i>
        <p class="font-bold text-green-700 text-sm">Kamu sudah memberi ulasan untuk menu ini.</p>
    </div>
    <?php elseif (!$pernah_beli): ?>
    <div class="bg-gray-50 border-2 border-gray-200 rounded-3xl p-4 flex items-center gap-3">
        <i class="fa-solid fa-lock text-gray-400 text-xl"></i>
        <p class="font-bold text-gray-500 text-sm">Beli menu ini dulu untuk bisa memberi ulasan.</p>
    </div>
    <?php endif; ?>

    <!-- Daftar Ulasan -->
    <div class="bg-white rounded-3xl border-2 border-orange-100 p-5 shadow-lg">
        <div class="flex items-center gap-2 mb-4">
            <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center">
                <i class="fa-solid fa-comments text-orange-500 text-sm"></i>
            </div>
            <p class="font-black text-gray-800">Ulasan Pembeli
                <span class="text-orange-500">(<?= count($ulasan_list) ?>)</span>
            </p>
        </div>

        <?php if (empty($ulasan_list)): ?>
        <div class="text-center py-8 text-gray-400">
            <i class="fa-solid fa-comment-slash text-3xl mb-2 opacity-40"></i>
            <p class="text-sm font-semibold">Belum ada ulasan</p>
        </div>
        <?php else: ?>
        <div class="space-y-4" id="listUlasan">
        <?php foreach ($ulasan_list as $u): ?>
        <div class="flex gap-3 pb-4 border-b border-gray-100 last:border-0">
            <!-- Avatar -->
            <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center 
                        flex-shrink-0 overflow-hidden">
                <?php if (!empty($u['foto_profil'])): ?>
                <img src="../../uploads/<?= htmlspecialchars($u['foto_profil']) ?>" 
                     class="w-full h-full object-cover">
                <?php else: ?>
                <i class="fa-solid fa-user text-orange-400"></i>
                <?php endif; ?>
            </div>
            <!-- Konten -->
            <div class="flex-1">
                <div class="flex items-center justify-between">
                    <p class="font-black text-sm text-gray-900">
                        <?= htmlspecialchars($u['username'] ?? 'Anonim') ?>
                    </p>
                    <p class="text-[10px] text-gray-400">
                        <?= date('d M Y', strtotime($u['created_at'])) ?>
                    </p>
                </div>
                <!-- Bintang -->
                <div class="flex gap-0.5 my-1">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                   <span class="text-sm <?= $i <= $u['rating'] ? 'text-yellow-400' : 'text-gray-200' ?>">★</span>
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
</div>

<!-- TOMBOL AKSI -->
<div class="fixed bottom-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-xl border-t-2 border-orange-100 shadow-[0_-8px_30px_rgba(249,115,22,0.15)]">
    <div class="max-w-[1200px] mx-auto px-4 py-4 grid grid-cols-2 gap-3">
        <button type="button" id="btnKeranjang" onclick="prosesKeKeranjang()"
                class="h-14 rounded-2xl border-2 border-orange-400 text-orange-600 font-black flex items-center justify-center gap-3 transition-all hover:bg-orange-50 hover:border-orange-500 <?= ($isHabis || $isLuarJam) ? 'opacity-40 cursor-not-allowed' : '' ?>"
                <?= ($isHabis || $isLuarJam) ? 'disabled' : '' ?>>
            <span class="material-symbols-outlined">shopping_bag</span>
            <span><?= $isLuarJam ? 'Tutup' : 'Tambah' ?></span>
        </button>
        <button type="button" id="btnPesan" onclick="pesanSekarang()"
                class="h-14 rounded-2xl font-black shadow-xl flex items-center justify-center gap-3 transition-all <?= ($isHabis || $isLuarJam) ? 'bg-gray-300 text-gray-500 cursor-not-allowed' : 'bg-gradient-to-r from-orange-500 to-orange-600 text-white shadow-orange-200 hover:from-orange-600 hover:to-orange-700 hover:shadow-xl' ?>"
                <?= ($isHabis || $isLuarJam) ? 'disabled' : '' ?>>
            <span class="material-symbols-outlined">bolt</span>
            <span><?= $isLuarJam ? 'Tutup' : 'Pesan Sekarang' ?></span>
        </button>
    </div>
</div>

<script>
let currentQty = 1;
const maxStok = <?= $stok ?>;
const isHabis = <?= $isHabis ? 'true' : 'false' ?>;
const isLuarJam = <?= $isLuarJam ? 'true' : 'false' ?>;
const jamBuka = '<?= date('H:i', strtotime($jamBuka)) ?>';
const jamTutup = '<?= date('H:i', strtotime($jamTutup)) ?>';
const stokWarning = document.getElementById('stok_warning');
const stokWarningText = document.getElementById('stok_warning_text');
const btnPlus = document.getElementById('btnPlus');
const btnMinus = document.getElementById('btnMinus');

function updateStokUI() {
    if (isHabis) {
        if (stokWarning) { stokWarningText.textContent = 'Stok habis'; stokWarning.classList.remove('hidden'); }
    } else if (currentQty >= maxStok) {
        if (stokWarning) { stokWarningText.textContent = 'Stok tinggal ' + maxStok; stokWarning.classList.remove('hidden'); }
        if (btnPlus) btnPlus.disabled = true;
    } else {
        if (stokWarning) stokWarning.classList.add('hidden');
        if (btnPlus) btnPlus.disabled = false;
    }
}

function changeQty(n) {
    if (isHabis) return;
    const newQty = currentQty + n;
    if (newQty < 1) return;
    if (newQty > maxStok) {
        alert('Stok tidak mencukupi. Maksimal: ' + maxStok);
        return;
    }
    currentQty = newQty;
    document.getElementById('display_qty').innerText = currentQty;
    updateStokUI();
}

function getOpsi(){
    const selected = document.querySelector('input[name="opsi_pilihan"]:checked');
    return selected ? selected.value : '';
}

function pesanSekarang(){
    if (isHabis) {
        alert('Maaf, menu ini sedang habis.');
        return;
    }
    if (isLuarJam) {
        alert('Maaf, kantin ini sedang tutup. Buka pukul ' + jamBuka + ' - ' + jamTutup);
        return;
    }
    const p = new URLSearchParams({id_menu:'<?= $id_menu ?>', qty:currentQty, opsi:getOpsi()});
    location.href = 'checkout.php?' + p.toString();
}

function prosesKeKeranjang(){
    if (isHabis) {
        alert('Maaf, menu ini sedang habis.');
        return;
    }
    if (isLuarJam) {
        alert('Maaf, kantin ini sedang tutup. Buka pukul ' + jamBuka + ' - ' + jamTutup);
        return;
    }
    const p = new URLSearchParams({id:'<?= $id_menu ?>', qty:currentQty, opsi:getOpsi(), csrf_token:'<?= $csrfToken ?>'});
    location.href = 'tambah_keranjang.php?' + p.toString();
}

document.addEventListener('DOMContentLoaded', updateStokUI);
</script>
<?php $current_page = 'menu'; include(__DIR__ . '/../../includes/navbar.php'); ?>

<script>
let selectedBintang = 0;

function setBintang(val) {
    selectedBintang = val;
    document.querySelectorAll('.star-btn').forEach(btn => {
        btn.style.color = parseInt(btn.dataset.val) <= val ? '#facc15' : '#d1d5db';
    });
}

async function kirimUlasan(e) {
    e.preventDefault();
    if (selectedBintang === 0) {
        alert('Pilih bintang dulu!'); return;
    }
    const komentar = document.getElementById('komentarInput').value.trim();
    const fd = new FormData();
    fd.append('id_menu', '<?= $id_menu ?>');
    fd.append('rating', selectedBintang);
    fd.append('komentar', komentar);
    fd.append('csrf_token', '<?= $csrfToken ?>');

    const btn = e.submitter || e.target.querySelector('button[type="submit"]');
    if (btn) { btn.disabled = true; btn.textContent = 'Mengirim...'; }

    try {
        const res = await fetch('proses_ulasan.php', { method: 'POST', body: fd });
        const json = await res.json();
        if (json.success) {
            location.reload();
        } else {
            alert(json.message || 'Gagal mengirim ulasan.');
            if (btn) { btn.disabled = false; btn.textContent = 'Kirim Ulasan'; }
        }
    } catch(err) {
        alert('Terjadi error. Coba lagi.');
        if (btn) { btn.disabled = false; btn.textContent = 'Kirim Ulasan'; }
    }
}
</script>
</body>
</html>