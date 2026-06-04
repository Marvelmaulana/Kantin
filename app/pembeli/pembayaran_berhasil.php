<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');
kk_ensure_buyer_schema($koneksi);

if (!isset($_SESSION['id_user']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'pembeli') {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = (int)$_SESSION['id_user'];
$id_pesanan = (int)($_GET['id_pesanan'] ?? 0);

if ($id_pesanan <= 0) {
    header("Location: dashboard.php");
    exit();
}

$query = mysqli_query($koneksi, "
    SELECT p.*, k.nama_kantin, k.logo, k.jam_buka, k.jam_tutup,
           COALESCE((
               SELECT SUM(dp.qty)
               FROM detail_pesanan dp
               WHERE dp.id_pesanan = p.id_pesanan
           ), 0) AS total_item
    FROM pesanan p
    JOIN kantin k ON p.id_kantin = k.id_kantin
    WHERE p.id_pesanan = $id_pesanan AND p.id_user = $id_user
");
$pesanan = mysqli_fetch_assoc($query);

if (!$pesanan) {
    header("Location: dashboard.php");
    exit();
}

$q_detail = mysqli_query($koneksi, "
    SELECT dp.*, m.foto
    FROM detail_pesanan dp
    LEFT JOIN menu m ON dp.id_menu = m.id_menu
    WHERE dp.id_pesanan = $id_pesanan
");

$methodConfig = [
    'GOPAY' => ['color' => '#00AED6', 'bg' => '#e6f7fb', 'icon' => 'payments', 'label' => 'GoPay'],
    'OVO'   => ['color' => '#4C3494', 'bg' => '#ede8f5', 'icon' => 'wallet', 'label' => 'OVO'],
    'DANA'  => ['color' => '#108BE3', 'bg' => '#e3f1fc', 'icon' => 'account_balance_wallet', 'label' => 'DANA'],
];

$metode = strtoupper($pesanan['metode_pembayaran'] ?: 'DANA');
$cfg = $methodConfig[$metode] ?? $methodConfig['DANA'];
$pajak = (int)($pesanan['pajak'] ?? kk_checkout_tax());
$subtotalPesanan = max(0, (int)($pesanan['total_harga'] ?? 0) - $pajak);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= t('checkout.success_title', 'Pembayaran Berhasil - Kantin Kita') ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800;900&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1" rel="stylesheet"/>
<style>
* { scrollbar-width: thin; scrollbar-color: #b5f5d4 transparent; }
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-thumb { background: #b5f5d4; border-radius: 10px; }

body {
    font-family: 'Be Vietnam Pro', sans-serif;
    background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #d1fae5 100%);
    min-height: 100vh;
}

.headline { font-family: 'Plus Jakarta Sans', sans-serif; }
.material-symbols-outlined { font-variation-settings: 'FILL' 1,'wght' 500,'GRAD' 0,'opsz' 24; }

.gradient-text {
    background: linear-gradient(135deg, #10b981, #059669);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

@keyframes bounce-in {
    0% { transform: scale(0); opacity: 0; }
    50% { transform: scale(1.15); }
    100% { transform: scale(1); opacity: 1; }
}
.animate-bounce-in { animation: bounce-in 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards; }

@keyframes confetti {
    0% { transform: translateY(-10px) rotate(0deg); opacity: 1; }
    100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
}
.confetti {
    position: fixed;
    width: 12px;
    height: 12px;
    border-radius: 2px;
    top: -15px;
    animation: confetti 3.5s ease-out forwards;
    z-index: 100;
}
</style>
</head>
<body class="pb-24">

<div class="confetti" style="left:8%;background:#f97316;animation-delay:0.0s;"></div>
<div class="confetti" style="left:18%;background:#10b981;animation-delay:0.2s;"></div>
<div class="confetti" style="left:28%;background:#8b5cf6;animation-delay:0.4s;"></div>
<div class="confetti" style="left:38%;background:#f59e0b;animation-delay:0.1s;"></div>
<div class="confetti" style="left:48%;background:#ec4899;animation-delay:0.3s;"></div>
<div class="confetti" style="left:58%;background:#06b6d4;animation-delay:0.5s;"></div>
<div class="confetti" style="left:68%;background:#84cc16;animation-delay:0.15s;"></div>
<div class="confetti" style="left:78%;background:#f97316;animation-delay:0.35s;"></div>
<div class="confetti" style="left:88%;background:#10b981;animation-delay:0.25s;"></div>

<header class="bg-white/90 backdrop-blur-xl sticky top-0 z-40 border-b-2 border-green-100 shadow-sm">
    <div class="max-w-[600px] mx-auto px-4 py-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center shadow-lg shadow-green-200 animate-bounce-in">
                <span class="material-symbols-outlined text-white text-lg">check</span>
            </div>
            <div>
                <h1 class="headline font-black text-lg gradient-text"><?= t('checkout.success_heading', 'Berhasil!') ?></h1>
                <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-widest"><?= t('checkout.success_label', 'Pembayaran') ?></p>
            </div>
        </div>
    </div>
</header>

<main class="max-w-[600px] mx-auto px-4 py-6 space-y-5">

    <div class="text-center animate-bounce-in">
        <div class="w-28 h-28 mx-auto rounded-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center shadow-2xl shadow-green-200/50 mb-4">
            <span class="material-symbols-outlined text-white text-5xl">celebration</span>
        </div>
        <h2 class="headline text-3xl font-black text-gray-800 mb-2"><?= t('checkout.thanks', 'Terimakasih') ?></h2>
        <p class="text-gray-500 text-sm"><?= t('checkout.process_desc', 'Pesananmu sedang diproses oleh penjual') ?></p>
    </div>

    <div class="bg-white rounded-3xl border-2 border-green-100 shadow-lg overflow-hidden">
        <div class="p-5" style="background: linear-gradient(135deg, #ecfdf5, #d1fae5);">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined" style="color:#10b981;font-variation-settings:'FILL' 1;"><?= $cfg['icon'] ?></span>
                    <span class="font-bold text-sm" style="color:#10b981"><?= $cfg['label'] ?></span>
                </div>
                <div class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-[11px] font-black uppercase">
                    <?= htmlspecialchars($pesanan['status']) ?>
                </div>
            </div>
            <p class="text-[11px] text-gray-400 mt-2 font-mono"><?= htmlspecialchars($pesanan['kode_pesanan'] ?: '#'.$id_pesanan) ?></p>
        </div>

        <div class="p-5 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <img src="<?= kk_upload_url($pesanan['logo'] ?? '', 'logo') ?>"
                     class="w-12 h-12 rounded-2xl object-cover bg-green-50 border-2 border-white shadow-sm"
                     onerror="this.src='../../public/assets/img/default-logo.svg'">
                <div class="flex-1">
                    <h3 class="font-bold text-gray-800"><?= htmlspecialchars($pesanan['nama_kantin']) ?></h3>
                    <p class="text-xs text-gray-400"><?= (int)$pesanan['total_item'] ?> item</p>
                </div>
            </div>
        </div>

        <div class="p-5 space-y-3">
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-3"><?= t('checkout.order_detail_label', 'Detail Pesanan') ?></p>
            <?php while ($item = mysqli_fetch_assoc($q_detail)): ?>
            <div class="flex items-center gap-3">
                <img src="<?= kk_upload_url($item['foto'] ?? '', 'menu') ?>"
                     class="w-12 h-12 rounded-xl object-cover bg-gray-100 shrink-0"
                     onerror="this.src='../../public/assets/img/default-food.svg'">
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-sm text-gray-800 truncate"><?= htmlspecialchars($item['nama_menu'] ?? 'Menu') ?></p>
                    <p class="text-[11px] text-gray-400"><?= (int)$item['qty'] ?> x Rp <?= number_format($item['harga'] ?? 0, 0, ',', '.') ?></p>
                </div>
                <div class="font-bold text-sm text-gray-800">
                    Rp <?= number_format($item['subtotal'] ?? 0, 0, ',', '.') ?>
                </div>
            </div>
            <?php endwhile; ?>

            <div class="pt-3 mt-3 border-t border-dashed border-gray-200">
                <div class="flex justify-between items-center mb-2">
                    <span class="font-semibold text-gray-500"><?= t('cart.subtotal', 'Subtotal') ?></span>
                    <span class="font-bold text-gray-700">Rp <?= number_format($subtotalPesanan, 0, ',', '.') ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="font-semibold text-gray-500"><?= t('cart.service_fee', 'Biaya Layanan') ?></span>
                    <span class="font-bold text-gray-700">Rp <?= number_format($pajak, 0, ',', '.') ?></span>
                </div>
                <div class="flex justify-between items-center pt-2 border-t border-dashed border-gray-200">
                    <span class="font-semibold text-gray-500"><?= t('cart.total_payment', 'Total Bayar') ?></span>
                    <span class="headline font-black text-xl gradient-text">
                        Rp <?= number_format($pesanan['total_harga'] ?? 0, 0, ',', '.') ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="p-4 bg-amber-50 border-t border-amber-100">
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-amber-500 text-xl">info</span>
                <div>
                    <p class="font-bold text-amber-700 text-sm">Info Pengambilan</p>
                    <p class="text-xs text-amber-600 mt-1">
                        Pesanan akan diproses saat jam operasional
                        (<?= date('H:i', strtotime($pesanan['jam_buka'] ?? '07:00')) ?> -
                         <?= date('H:i', strtotime($pesanan['jam_tutup'] ?? '15:00')) ?>)
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-3xl border-2 border-green-100 p-5 shadow-lg">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-white text-lg">notifications_active</span>
            </div>
            <div>
                <p class="font-bold text-gray-800">Status Pesanan</p>
                <p class="text-xs text-gray-400">Pantau pesananmu di halaman Pesanan</p>
            </div>
        </div>
        <div class="space-y-2">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-clock text-yellow-500 text-xs"></i>
                </div>
                <div class="flex-1">
                    <p class="font-bold text-sm text-gray-700">Menunggu Konfirmasi</p>
                    <p class="text-[11px] text-gray-400">Penjual akan memproses pesananmu</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-fire-burner text-blue-500 text-xs"></i>
                </div>
                <div class="flex-1">
                    <p class="font-bold text-sm text-gray-700">Sedang Diproses</p>
                    <p class="text-[11px] text-gray-400">Pesananmu sedang dibuat</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-bowl-food text-green-500 text-xs"></i>
                </div>
                <div class="flex-1">
                    <p class="font-bold text-sm text-gray-700">Siap Diambil</p>
                    <p class="text-[11px] text-gray-400">Pesanan siap diambil</p>
                </div>
            </div>
        </div>
    </div>

</main>

<div class="fixed bottom-0 left-0 right-0 z-50 bg-white/98 backdrop-blur-xl border-t-2 border-green-100 shadow-[0_-8px_30px_rgba(16,185,129,0.15)]">
    <div class="max-w-[600px] mx-auto px-4 py-4 grid grid-cols-2 gap-3">
        <a href="pesanan.php"
           class="h-13 py-4 rounded-2xl border-2 border-green-400 text-green-600 font-black flex items-center justify-center gap-2 hover:bg-green-50 transition-all">
            <span class="material-symbols-outlined text-xl">receipt_long</span>
            <span>Lihat Pesanan</span>
        </a>
        <a href="dashboard.php"
           class="h-13 py-4 rounded-2xl bg-gradient-to-r from-green-500 to-green-600 text-white font-black flex items-center justify-center gap-2 shadow-lg shadow-green-200 hover:shadow-xl hover:scale-[1.02] transition-all">
            <span class="material-symbols-outlined text-xl">home</span>
            <span>Beranda</span>
        </a>
    </div>
</div>

<script>
setTimeout(() => {
    document.querySelectorAll('.confetti').forEach(el => el.remove());
}, 3800);
</script>

</body>
</html>
