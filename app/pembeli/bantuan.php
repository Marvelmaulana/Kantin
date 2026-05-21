<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Pusat Bantuan</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@700;800;900&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1" rel="stylesheet"/>
<style>body{font-family:'Be Vietnam Pro',sans-serif;background:#fffdfc}.headline{font-family:'Plus Jakarta Sans',sans-serif}</style>
</head>
<body class="pb-28 text-stone-800">
<header class="bg-white sticky top-0 z-40 px-5 py-4 border-b border-stone-100">
    <div class="max-w-3xl mx-auto flex items-center gap-3">
        <button onclick="history.back()" class="w-10 h-10 rounded-2xl bg-stone-100 flex items-center justify-center"><span class="material-symbols-outlined">arrow_back</span></button>
        <h1 class="headline font-black text-lg text-[#b22204]">Pusat Bantuan</h1>
    </div>
</header>
<main class="max-w-3xl mx-auto px-5 py-6 space-y-4">
    <section class="rounded-[2rem] bg-gradient-to-r from-[#b22204] to-[#ff6b35] text-white p-6">
        <p class="text-xs font-bold uppercase tracking-widest text-white/70">Help Center</p>
        <h2 class="headline text-2xl font-black mt-2">Ada kendala pesanan?</h2>
        <p class="text-sm text-white/80 mt-2">Cek panduan cepat di bawah atau hubungi admin kantin/sekolah.</p>
    </section>
    <?php
    $faqs = [
        ['Pesanan belum diproses', 'Pastikan pembayaran berhasil. Jika status masih Pending terlalu lama, hubungi penjual dari halaman Pesanan Aktif.'],
        ['Salah pilih level atau catatan', 'Jika status masih Pending, batalkan lalu pesan ulang. Jika sudah diproses, tanyakan langsung ke penjual.'],
        ['Harga checkout berbeda', 'Total dihitung dari harga asli menu dikali jumlah. Opsi level saat ini tidak menambah harga kecuali penjual mengubah harga menu.'],
        ['Ingin memberi ulasan', 'Buka Riwayat Pesanan setelah status Selesai, lalu tekan tombol Ulas di menu yang dibeli.'],
        ['Lupa password', 'Gunakan menu Lupa Password di halaman login, masukkan email akun, lalu pakai token reset yang dibuat sistem.'],
    ];
    foreach ($faqs as [$title, $body]):
    ?>
    <article class="bg-white border border-orange-100 rounded-3xl p-5 shadow-sm">
        <h3 class="headline font-black flex items-center gap-2"><span class="material-symbols-outlined text-[#b22204]">help</span><?= $title ?></h3>
        <p class="text-sm text-stone-500 mt-2 leading-relaxed"><?= $body ?></p>
    </article>
    <?php endforeach; ?>
</main>
<?php $current_page = 'profile'; include(__DIR__ . '/../../includes/navbar.php'); ?>
</body>
</html>
