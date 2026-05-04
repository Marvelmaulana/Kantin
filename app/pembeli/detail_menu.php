<?php
session_start();
include(__DIR__ . '/../../config/config.php');

// 1. Ambil ID dari URL dengan proteksi
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$id_menu = mysqli_real_escape_string($koneksi, $_GET['id']);

// 2. Query data menu lengkap dengan nama kantin
$query = mysqli_query($koneksi, "SELECT menu.*, kantin.nama_kantin 
                                  FROM menu 
                                  JOIN kantin ON menu.id_kantin = kantin.id_kantin 
                                  WHERE menu.id_menu = '$id_menu'");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    echo "<script>alert('Menu tidak ditemukan!'); window.location='dashboard.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= $data['nama_menu']; ?> - Detail Menu</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&family=Be+Vietnam+Pro:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { "primary": "#b22204", "surface": "#fff8f6" }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Be Vietnam Pro', sans-serif; }
        h1, h3 { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-surface text-stone-900 antialiased">

<header class="fixed top-0 w-full z-50 bg-white/80 backdrop-blur-md shadow-sm">
    <div class="flex justify-between items-center px-6 py-4 w-full max-w-7xl mx-auto">
        <div class="flex items-center gap-4">
            <button class="material-symbols-outlined p-2 rounded-full hover:bg-stone-100" onclick="window.location.href='dashboard.php'">arrow_back</button>
            <span class="text-xl font-extrabold text-orange-700">Kantin Kita</span>
        </div>
    </div>
</header>

<main class="pt-20 pb-32 max-w-5xl mx-auto lg:px-8 lg:pt-24">
    <div class="grid grid-cols-1 lg:grid-cols-2 lg:gap-12">
        <div class="relative">
            <div class="aspect-square lg:aspect-[4/5] overflow-hidden lg:rounded-3xl shadow-xl bg-stone-200">
                <img class="w-full h-full object-cover" src="../../uploads/<?= $data['foto']; ?>" onerror="this.src='https://via.placeholder.com/500x600?text=No+Image'"/>
            </div>
        </div>

        <div class="px-6 py-8 lg:px-0">
            <div class="flex flex-col gap-2">
                <span class="bg-primary/10 text-primary px-3 py-1 rounded-full text-[10px] font-bold uppercase w-fit"><?= $data['nama_kantin']; ?></span>
                <h1 class="text-3xl lg:text-4xl font-extrabold tracking-tight"><?= $data['nama_menu']; ?></h1>
                <p class="text-3xl font-bold text-primary mt-2">Rp <?= number_format($data['harga'], 0, ',', '.'); ?></p>
            </div>

            <div class="mt-8 border-t border-stone-100 pt-6">
                <h3 class="text-sm font-bold uppercase tracking-widest text-stone-500 mb-3">Deskripsi Menu</h3>
                <p class="text-stone-600 leading-relaxed italic">
                    <?= !empty($data['deskripsi']) ? $data['deskripsi'] : "Sajian lezat higienis dari " . $data['nama_kantin'] . "."; ?>
                </p>
            </div>

            <div class="mt-10 space-y-8">
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-widest text-stone-500 mb-3">Catatan Pesanan</h3>
                    <textarea id="input_catatan" placeholder="Contoh: Gak pakai sambal, bungkus terpisah..." class="w-full bg-white border border-stone-200 rounded-2xl p-4 text-sm focus:ring-2 focus:ring-primary focus:border-transparent transition-all"></textarea>
                </div>

                <div class="flex items-center justify-between bg-white p-4 rounded-2xl border border-stone-100 shadow-sm">
                    <h3 class="text-sm font-bold uppercase tracking-widest text-stone-500">Jumlah</h3>
                    <div class="flex items-center bg-stone-100 rounded-full p-1">
                        <button onclick="changeQty(-1)" class="w-10 h-10 flex items-center justify-center rounded-full bg-white text-stone-800 shadow-sm active:scale-90">-</button>
                        <span id="display_qty" class="w-12 text-center font-bold text-lg">1</span>
                        <button onclick="changeQty(1)" class="w-10 h-10 flex items-center justify-center rounded-full bg-primary text-white shadow-sm active:scale-90">+</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="fixed bottom-0 left-0 w-full bg-white/90 backdrop-blur-xl border-t border-stone-100 px-6 pb-8 pt-4 z-50 lg:static lg:bg-transparent lg:border-none lg:mt-12">
    <div class="max-w-5xl mx-auto flex gap-4">
        <button type="button" class="flex-1 flex items-center justify-center gap-2 border-2 border-primary text-primary font-extrabold py-4 rounded-full active:scale-95 transition-all" onclick="prosesKeKeranjang('cart')">
            <span class="material-symbols-outlined">shopping_cart</span>
            Tambah
        </button>

        <button type="button" onclick="pesanSekarang()" class="flex-1 bg-primary text-white font-extrabold py-4 rounded-full shadow-lg active:scale-95 transition-all text-center flex items-center justify-center">
            Pesan Sekarang
        </button>
    </div>
</div>

<script>
    let currentQty = 1;

    // Fungsi update angka jumlah
    function changeQty(n) {
        currentQty += n;
        if (currentQty < 1) currentQty = 1;
        document.getElementById('display_qty').innerText = currentQty;
    }

    // Fungsi Pesan Sekarang (Langsung ke Checkout)
    function pesanSekarang() {
        const catatan = document.getElementById('input_catatan').value;
        const idMenu = "<?= $id_menu ?>"; 
        
        // Kirim id_menu, qty, dan catatan langsung ke checkout.php
        window.location.href = `checkout.php?id_menu=${idMenu}&qty=${currentQty}&catatan=${encodeURIComponent(catatan)}`;
    }

    // Fungsi Tambah ke Keranjang (Masuk ke Database Keranjang dulu)
    function prosesKeKeranjang(type) {
        const catatan = document.getElementById('input_catatan').value;
        const idMenu = "<?= $id_menu ?>";
        
        let url = `tambah_keranjang.php?id=${idMenu}&qty=${currentQty}&catatan=${encodeURIComponent(catatan)}`;
        
        if (type === 'checkout') {
            url += '&action=checkout';
        }

        window.location.href = url;
    }
</script>

</body>
</html>