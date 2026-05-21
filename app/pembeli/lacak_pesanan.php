<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');
include(__DIR__ . '/../../includes/language_helper.php');
kk_ensure_buyer_schema($koneksi);

if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$id = intval($_GET['id'] ?? 0);

$query = mysqli_query($koneksi, "
SELECT 
    pesanan.*,
    kantin.nama_kantin,
    kantin.logo
FROM pesanan
JOIN kantin 
ON pesanan.id_kantin = kantin.id_kantin
WHERE pesanan.id_pesanan = '$id'
AND pesanan.id_user = '$id_user'
LIMIT 1
");

$data = mysqli_fetch_assoc($query);

if (!$data) {
    echo t('tracking.order_not_found');
    exit;
}

$detail = mysqli_query($koneksi, "
SELECT 
    detail_pesanan.*,
    menu.nama_menu,
    menu.foto,
    menu.harga
FROM detail_pesanan
JOIN menu 
ON detail_pesanan.id_menu = menu.id_menu
WHERE detail_pesanan.id_pesanan = '$id'
");

$status = $data['status'];

$step1 = 'bg-[#b22204] text-white';
$step2 = 'bg-stone-200 text-stone-500';
$step3 = 'bg-stone-200 text-stone-500';
$line1 = 'bg-stone-200';
$line2 = 'bg-stone-200';

if ($status == 'Diproses' || $status == 'Proses Masak') {
    $step2 = 'bg-[#b22204] text-white';
    $line1 = 'bg-[#b22204]';
}

if ($status == 'Siap Diambil') {
    $step2 = 'bg-[#b22204] text-white';
    $step3 = 'bg-[#b22204] text-white';
    $line1 = 'bg-[#b22204]';
    $line2 = 'bg-[#b22204]';
}

if ($status == 'Selesai') {
    $step1 = 'bg-green-600 text-white';
    $step2 = 'bg-green-600 text-white';
    $step3 = 'bg-green-600 text-white';
    $line1 = 'bg-green-600';
    $line2 = 'bg-green-600';
}

if ($status == 'Dibatalkan') {
    $step1 = 'bg-red-500 text-white';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= t('tracking.title') ?></title>

<meta http-equiv="refresh" content="15">

<script src="https://cdn.tailwindcss.com"></script>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@700;800&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet"/>

<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>

<style>
body{
    font-family:'Be Vietnam Pro',sans-serif;
    background:#fff8f6;
}

h1,h2,h3,h4{
    font-family:'Plus Jakarta Sans',sans-serif;
}
</style>

</head>

<body class="pb-32">

<!-- HEADER -->
<header class="bg-white sticky top-0 z-50 border-b border-stone-100">

<div class="max-w-md mx-auto px-4 h-16 flex items-center gap-3">

<button onclick="history.back()">
    <span class="material-symbols-outlined">
        arrow_back
    </span>
</button>

<div>
    <h1 class="font-extrabold text-lg">
        <?= t('tracking.track_order') ?>
    </h1>

    <p class="text-xs text-stone-400">
        <?= $data['kode_pesanan'] ?>
    </p>
</div>

</div>

</header>

<main class="max-w-md mx-auto px-4 py-6 space-y-5">

<!-- KARTU STATUS -->
<div class="bg-gradient-to-br from-[#b22204] to-red-700 rounded-3xl p-5 text-white shadow-xl">

<div class="flex justify-between items-start">

<div>
    <p class="text-xs uppercase tracking-widest font-bold text-red-100">
        <?= t('tracking.order_status') ?>
    </p>

    <h2 class="text-2xl font-extrabold mt-1">
        <?= $data['status'] ?>
    </h2>

    <p class="text-sm text-red-100 mt-2">
        <?= date('d M Y • H:i', strtotime($data['tanggal'])) ?>
    </p>
</div>

<div class="bg-white/20 px-4 py-2 rounded-2xl backdrop-blur">

<p class="text-[10px] uppercase font-black">
Kode
</p>

<p class="font-black">
<?= $data['kode_pesanan'] ?>
</p>

</div>

</div>

</div>

<!-- KANTIN -->
<div class="bg-white rounded-3xl p-5 shadow-sm">

<div class="flex items-center gap-4">

<img
    src="<?= kk_upload_url($data['logo'] ?? '', 'logo') ?>"
    class="w-16 h-16 rounded-2xl object-cover border"
    onerror="this.src='../../public/assets/img/default-logo.svg'"
>

<div class="flex-1">

<p class="text-xs text-stone-400 uppercase font-bold">
<?= t('tracking.kantin') ?>
</p>

<h2 class="text-xl font-extrabold">
<?= $data['nama_kantin'] ?>
</h2>

<p class="text-sm text-stone-500 mt-1">
<?= $data['metode_pembayaran'] ?>
</p>

</div>

</div>

</div>

<!-- PROGRESS -->
<?php if($status != 'Dibatalkan'): ?>

<div class="bg-white rounded-3xl p-6 shadow-sm">

<h3 class="font-extrabold text-lg mb-6">
<?= t('tracking.order_progress') ?>
</h3>

<div class="flex items-center justify-between">

<div class="flex flex-col items-center flex-1">

<div class="w-12 h-12 rounded-full flex items-center justify-center font-black <?= $step1 ?>">
1
</div>

<p class="text-xs font-bold mt-2 text-center">
<?= t('tracking.pending') ?>
</p>

</div>

<div class="h-1 flex-1 <?= $line1 ?>"></div>

<div class="flex flex-col items-center flex-1">

<div class="w-12 h-12 rounded-full flex items-center justify-center font-black <?= $step2 ?>">
2
</div>

<p class="text-xs font-bold mt-2 text-center">
<?= t('tracking.processing') ?>
</p>

</div>

<div class="h-1 flex-1 <?= $line2 ?>"></div>

<div class="flex flex-col items-center flex-1">

<div class="w-12 h-12 rounded-full flex items-center justify-center font-black <?= $step3 ?>">
3
</div>

<p class="text-xs font-bold mt-2 text-center">
<?= t('tracking.ready') ?>
</p>

</div>

</div>

</div>

<?php endif; ?>

<!-- DETAIL -->
<div class="bg-white rounded-3xl p-5 shadow-sm">

<div class="flex items-center justify-between mb-5">

<h3 class="font-extrabold text-lg">
<?= t('checkout.order_detail') ?>
</h3>

<p class="text-xs font-bold uppercase tracking-widest text-stone-400">
<?= mysqli_num_rows($detail) ?> Item
</p>

</div>

<div class="space-y-5">

<?php while($d = mysqli_fetch_assoc($detail)): ?>

<div class="flex gap-4">

<img
    src="<?= kk_upload_url($d['foto'] ?? '', 'menu') ?>"
    class="w-20 h-20 rounded-2xl object-cover"
    onerror="this.src='../../public/assets/img/default-food.svg'"
>

<div class="flex-1 min-w-0">

<h4 class="font-bold text-stone-800">
<?= $d['nama_menu'] ?>
</h4>

<p class="text-sm text-stone-400 mt-1">
<?= $d['qty'] ?>x • Rp <?= number_format($d['harga'],0,',','.') ?>
</p>

<?php if(!empty($d['catatan'])): ?>

<div class="mt-2 bg-orange-50 text-[#b22204] text-xs px-3 py-2 rounded-xl italic">
<?= $d['catatan'] ?>
</div>

<?php endif; ?>

</div>

<div class="text-right">

<p class="font-black text-[#b22204]">
Rp <?= number_format($d['subtotal'],0,',','.') ?>
</p>

</div>

</div>

<?php endwhile; ?>

</div>

</div>

<!-- PEMBAYARAN -->
<div class="bg-white rounded-3xl p-5 shadow-sm">

<h3 class="font-extrabold text-lg mb-5">
<?= t('tracking.payment_summary') ?>
</h3>

<div class="space-y-4">

<div class="flex justify-between">

<span class="text-stone-500">
<?= t('tracking.payment_method') ?>
</span>

<span class="font-bold">
<?= $data['metode_pembayaran'] ?>
</span>

</div>

<div class="flex justify-between">

<span class="text-stone-500">
<?= t('tracking.order_date') ?>
</span>

<span class="font-bold text-right">
<?= date('d M Y H:i', strtotime($data['tanggal'])) ?>
</span>

</div>

<div class="border-t border-dashed pt-4 flex justify-between items-center">

<span class="font-extrabold text-lg">
<?= t('tracking.total_payment') ?>
</span>

<span class="font-black text-2xl text-[#b22204]">
Rp <?= number_format($data['total_harga'],0,',','.') ?>
</span>

</div>

</div>

</div>

<!-- ACTION -->
<div class="grid grid-cols-2 gap-3">

<a
href="download_bukti.php?id=<?= $data['id_pesanan'] ?>"
download="bukti_pesanan_<?= htmlspecialchars($data['kode_pesanan']) ?>.txt"
class="bg-stone-900 text-white rounded-2xl py-4 flex items-center justify-center gap-2 font-bold"
>

<span class="material-symbols-outlined">
download
</span>

<?= t('tracking.download') ?>

</a>

<?php if($status == 'Pending'): ?>

<button
onclick="if(confirm('<?= t('buyer.cancel_confirm') ?>')){window.location.href='batalkan_pesanan.php?id=<?= $data['id_pesanan'] ?>'}"
class="bg-red-100 text-red-700 rounded-2xl py-4 flex items-center justify-center gap-2 font-bold"
>

<span class="material-symbols-outlined">
close
</span>

<?= t('tracking.cancel_order') ?>

</button>

<?php else: ?>

<button
class="bg-stone-100 text-stone-400 rounded-2xl py-4 flex items-center justify-center gap-2 font-bold cursor-not-allowed"
disabled
>

<span class="material-symbols-outlined">
lock
</span>

<?= t('tracking.cannot_cancel') ?>

</button>

<?php endif; ?>

</div>

</main>

</body>
</html>
