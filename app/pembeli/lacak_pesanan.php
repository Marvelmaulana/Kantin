<?php
include __DIR__ . '/../../config/config.php';

$id = $_GET['id'] ?? 0;

// ambil pesanan
$pesanan = mysqli_query($conn, "SELECT * FROM pesanan WHERE id_pesanan='$id'");
$data = mysqli_fetch_assoc($pesanan);

// VALIDASI WAJIB
if (!$data) {
    echo "Pesanan tidak ditemukan";
    exit;
}

// ambil detail
$detail = mysqli_query($conn, "
SELECT dp.*, m.nama_menu, m.harga, m.foto 
FROM detail_pesanan dp
JOIN menu m ON dp.id_menu = m.id_menu
WHERE dp.id_pesanan='$id'
");
?>

<!DOCTYPE html>

<html class="light" lang="id"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Kantin Kita - Pembayaran Berhasil</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&amp;family=Be_Vietnam_Pro:wght@400;500;600&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .success-gradient {
            background: linear-gradient(135deg, #b22204 0%, #d63c1e 100%);
        }
        .glass-header {
            -webkit-backdrop-filter: blur(12px);
            backdrop-filter: blur(12px);
        }
    </style>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "on-tertiary-fixed-variant": "#004d67",
                        "outline-variant": "#e3beb6",
                        "error-container": "#ffdad6",
                        "secondary-fixed": "#ffdad3",
                        "on-tertiary-fixed": "#001e2c",
                        "on-primary-fixed": "#3e0500",
                        "outline": "#8f7069",
                        "surface-tint": "#b62506",
                        "on-secondary-container": "#762616",
                        "surface-bright": "#fff8f6",
                        "surface-dim": "#f0d4ce",
                        "secondary": "#9c4230",
                        "on-error-container": "#93000a",
                        "tertiary": "#006385",
                        "surface-variant": "#f9dcd6",
                        "secondary-container": "#ff8f77",
                        "primary-fixed": "#ffdad3",
                        "primary": "#b22204",
                        "secondary-fixed-dim": "#ffb4a4",
                        "on-tertiary-container": "#fbfcff",
                        "on-secondary-fixed": "#3e0500",
                        "inverse-primary": "#ffb4a4",
                        "primary-fixed-dim": "#ffb4a4",
                        "on-surface": "#271815",
                        "on-secondary-fixed-variant": "#7d2c1b",
                        "on-secondary": "#ffffff",
                        "tertiary-fixed-dim": "#76d1ff",
                        "on-primary": "#ffffff",
                        "surface-container-low": "#fff0ee",
                        "on-background": "#271815",
                        "inverse-surface": "#3e2c29",
                        "on-error": "#ffffff",
                        "on-primary-container": "#fffbff",
                        "inverse-on-surface": "#ffede9",
                        "on-tertiary": "#ffffff",
                        "on-primary-fixed-variant": "#8d1600",
                        "on-surface-variant": "#5b403b",
                        "surface": "#fff8f6",
                        "tertiary-fixed": "#c2e8ff",
                        "tertiary-container": "#007ea7",
                        "surface-container": "#ffe9e5",
                        "surface-container-highest": "#f9dcd6",
                        "error": "#ba1a1a",
                        "primary-container": "#d63c1e",
                        "surface-container-lowest": "#ffffff",
                        "surface-container-high": "#ffe2dc",
                        "background": "#fff8f6"
                    },
                    "fontFamily": {
                        "headline": ["Plus Jakarta Sans"],
                        "display": ["Plus Jakarta Sans"],
                        "body": ["Be Vietnam Pro"],
                        "label": ["Be Vietnam Pro"]
                    }
                }
            }
        }
    </script>
<style>
    body {
      min-height: max(884px, 100dvh);
    }
  </style>
</head>
<body class="bg-background text-on-surface font-body selection:bg-secondary-container antialiased">
<!-- TopAppBar -->
<header class="bg-white/80 dark:bg-zinc-950/80 backdrop-blur-md sticky top-0 z-50 docked full-width">
<div class="flex items-center justify-between px-4 h-16 w-full max-w-md mx-auto">
<div class="flex items-center gap-4">
<button class="active:scale-95 duration-150 ease-out transition-colors hover:bg-zinc-100/50 p-2 rounded-full">
<span class="material-symbols-outlined text-[#b22204] dark:text-[#ee4d2d]">arrow_back</span>
</button>
<h1 class="font-['Plus_Jakarta_Sans'] font-bold text-lg tracking-tight text-on-surface">Pembayaran</h1>
</div>
<div class="flex items-center">
<span class="font-['Plus_Jakarta_Sans'] font-extrabold text-[#b22204] dark:text-[#ee4d2d] text-xl">Kantin Kita</span>
</div>
</div>
<div class="bg-zinc-100 dark:bg-zinc-900 h-[1px] w-full"></div>
</header>
<main class="max-w-md mx-auto px-6 pt-8 pb-32">
<!-- Success State Hero -->
<section class="mb-10 text-center animate-in fade-in slide-in-from-bottom-4 duration-700">
<div class="relative inline-block mb-6">
<div class="absolute inset-0 bg-primary/20 blur-2xl rounded-full scale-150"></div>
<div class="relative w-24 h-24 success-gradient rounded-full flex items-center justify-center shadow-lg shadow-primary/30">
<span class="material-symbols-outlined text-white text-5xl" style="font-variation-settings: 'FILL' 1;">check_circle</span>
</div>
</div>
<h2 class="font-display text-2xl font-extrabold text-on-surface tracking-tight mb-2">Pembayaran Berhasil</h2>
<?= $data['kode_pesanan'] ?? '-' ?>
</section>
<!-- Receipt Card (The Editorial Shell) -->
<div class="surface-container-lowest rounded-3xl overflow-hidden shadow-[0_20px_50px_rgba(39,24,21,0.08)] mb-8 border border-outline-variant/10">
<div class="p-6">
<!-- Receipt Header -->
<div class="flex justify-between items-start mb-8">
<div>
<p class="font-label text-[10px] font-bold tracking-[0.1em] uppercase text-on-surface-variant mb-1">Receipt for</p>
<?= $data['nama_pembeli']; ?>
</div>
<div class="text-right">
<p class="font-label text-[10px] font-bold tracking-[0.1em] uppercase text-on-surface-variant mb-1">Status</p>
<span class="inline-flex items-center px-3 py-1 rounded-full bg-secondary-container text-on-secondary-container text-xs font-bold">
                            <?= $data['status']; ?>
                        </span>
</div>
</div>
<!-- Receipt Items -->
<div class="space-y-4 mb-8">
<?php while($d = mysqli_fetch_assoc($detail)) { 
    $jumlah = $d['jumlah'] ?? 0;
    $harga = $d['harga'] ?? 0;
    $subtotal = $harga * $jumlah;
?>
<div class="flex justify-between items-center">
<div class="flex gap-4 items-center">

<div class="w-12 h-12 rounded-xl overflow-hidden bg-surface-container">
<img class="w-full h-full object-cover"
src="../../public/assets/img/<?= $d['foto']; ?>">
</div>

<div>
<h4 class="font-headline font-bold text-on-surface">
<?= $d['nama_menu']; ?>
</h4>

<p class="text-xs text-on-surface-variant">
<?= $d['jumlah']; ?>x 
<?= $d['catatan']; ?>
</p>
</div>

</div>

<p class="font-headline font-bold text-on-surface">
Rp <?= number_format($subtotal); ?>
</p>

</div>
<?php } ?>
</div>
<!-- Payment Details -->
<div class="pt-6 border-t border-dashed border-outline-variant/30 space-y-3">
<div class="flex justify-between text-sm text-on-surface-variant">
<span>Subtotal</span>
<span>Rp <?= number_format($data['total']); ?></span>
</div>
<div class="flex justify-between items-center pt-2">
<span class="font-headline font-bold text-on-surface">Total Pembayaran</span>
<span class="font-headline font-black text-xl text-primary">Rp <?= number_format($data['total']); ?></span>
</div>
</div>
<!-- Payment Method -->
<div class="mt-8 p-4 bg-surface-container-low rounded-2xl flex items-center justify-between">
<div class="flex items-center gap-3">
<div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-sm">
<span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">account_balance_wallet</span>
</div>
<div>
<p class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant leading-none mb-1">Paid With</p>
<p class="font-headline font-bold text-on-surface"><?= $data['metode_pembayaran']; ?></p>
</div>
</div>
<p class="text-xs text-on-surface-variant font-medium"><?= date('d M Y, H:i', strtotime($data['tanggal'])); ?></p>
</div>
</div>
<!-- Asymmetrical Card Accents -->
<div class="relative h-2 success-gradient w-full"></div>
</div>
<!-- Action Grid (Bento Style) -->
<div class="grid grid-cols-2 gap-4">
<a href="download_bukti.php?id=<?= $id; ?>" class="col-span-2 py-4 px-6 rounded-full success-gradient text-white font-headline font-bold flex items-center justify-center gap-2 active:scale-[0.98] transition-transform shadow-lg shadow-primary/20">
<a href="download_bukti.php?id=<?= $id; ?>" class="...">
    <span class="material-symbols-outlined text-xl">download</span>
    Simpan Bukti
</a>

                Simpan Bukti
</a>


<button class="py-4 px-6 rounded-full bg-surface-container-high text-on-surface font-headline font-bold flex items-center justify-center gap-2 active:scale-[0.98] transition-transform">
<span class="material-symbols-outlined text-xl">share</span>
                Bagikan
            </button>
<button class="py-4 px-6 rounded-full bg-surface-container-high text-on-surface font-headline font-bold flex items-center justify-center gap-2 active:scale-[0.98] transition-transform">
<span class="material-symbols-outlined text-xl">list_alt</span>
                Detail
            </button>
<button class="col-span-2 py-4 px-6 rounded-full border-2 border-outline-variant/30 text-primary font-headline font-bold flex items-center justify-center gap-2 active:scale-[0.98] transition-transform mt-2">
<a href="lacak_pesanan.php?id=<?= $id; ?>" class="col-span-2 py-4 px-6 rounded-full border-2 border-outline-variant/30 text-primary font-headline font-bold flex items-center justify-center gap-2 active:scale-[0.98] transition-transform mt-2">
<span class="material-symbols-outlined text-xl">visibility</span>
                Lihat Status
</a>

</div>
</main>
<!-- BottomNavBar -->
<nav class="fixed bottom-0 left-0 w-full h-20 z-50 flex justify-around items-center px-6 pb-safe bg-white/90 dark:bg-zinc-950/90 backdrop-blur-xl shadow-[0_-4px_24px_rgba(39,24,21,0.06)] rounded-t-3xl border-t border-transparent">
<a class="flex flex-col items-center justify-center text-zinc-400 dark:text-zinc-600 scale-98 active:opacity-80 transition-transform hover:text-[#b22204]" href="#">
<span class="material-symbols-outlined mb-1">home</span>
<span class="font-['Be_Vietnam_Pro'] text-[11px] font-medium tracking-wide uppercase">Beranda</span>
</a>
<a class="flex flex-col items-center justify-center text-[#b22204] dark:text-[#ee4d2d] relative after:content-[''] after:absolute after:-bottom-1 after:w-1 after:h-1 after:bg-[#b22204] after:rounded-full scale-98 active:opacity-80 transition-transform" href="#">
<span class="material-symbols-outlined mb-1" style="font-variation-settings: 'FILL' 1;">receipt_long</span>
<span class="font-['Be_Vietnam_Pro'] text-[11px] font-medium tracking-wide uppercase">Pesanan</span>
</a>
<a class="flex flex-col items-center justify-center text-zinc-400 dark:text-zinc-600 scale-98 active:opacity-80 transition-transform hover:text-[#b22204]" href="#">
<span class="material-symbols-outlined mb-1">account_balance_wallet</span>
<span class="font-['Be_Vietnam_Pro'] text-[11px] font-medium tracking-wide uppercase">Dompet</span>
</a>
<a class="flex flex-col items-center justify-center text-zinc-400 dark:text-zinc-600 scale-98 active:opacity-80 transition-transform hover:text-[#b22204]" href="#">
<span class="material-symbols-outlined mb-1">person</span>
<span class="font-['Be_Vietnam_Pro'] text-[11px] font-medium tracking-wide uppercase">Profil</span>
</a>
</nav>
</body></html>