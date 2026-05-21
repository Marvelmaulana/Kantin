<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');

// Ambil ID Detail Pesanan (supaya tahu makanan apa yang mau dinilai)
$id_detail = isset($_GET['id_detail']) ? mysqli_real_escape_string($koneksi, $_GET['id_detail']) : '';

// Query untuk ambil nama menu dan foto berdasarkan pesanan
$query = mysqli_query($koneksi, "SELECT detail_pesanan.*, menu.nama_menu, menu.foto, kantin.nama_kantin 
    FROM detail_pesanan 
    JOIN menu ON detail_pesanan.id_menu = menu.id_menu 
    JOIN kantin ON menu.id_kantin = kantin.id_kantin
    WHERE detail_pesanan.id_detail = '$id_detail'");
$d = mysqli_fetch_assoc($query);

if (!$d) { echo "Data tidak ditemukan"; exit; }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/><meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@700;800&family=Be+Vietnam+Pro:wght@400;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <style>
        .star-btn.active .material-symbols-outlined { font-variation-settings: 'FILL' 1; color: #b22204; }
        .star-btn .material-symbols-outlined { font-variation-settings: 'FILL' 0; color: #e3beb6; }
    </style>
</head>
<body class="bg-surface text-on-surface min-h-screen">

<form action="proses_ulasan.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="id_menu" value="<?= $d['id_menu'] ?>">
    <input type="hidden" name="id_detail" value="<?= $id_detail ?>">
    <input type="hidden" name="rating" id="rating_value" value="5">

    <header class="fixed top-0 w-full flex justify-between items-center px-6 h-16 bg-white/80 backdrop-blur-xl z-50 shadow-sm">
        <div class="flex items-center gap-4">
            <button type="button" onclick="history.back()"><span class="material-symbols-outlined">close</span></button>
            <h1 class="text-xl font-bold text-orange-700">Kantin Kita</h1>
        </div>
    </header>

    <main class="pt-24 pb-32 px-6 max-w-2xl mx-auto">
        <section class="mb-12 flex items-start gap-6">
            <div class="w-32 h-32 rounded-xl overflow-hidden shadow-lg">
                <img class="w-full h-full object-cover" src="<?= kk_upload_url($d['foto'] ?? '', 'menu') ?>" onerror="this.src='../../public/assets/img/default-food.svg'">
            </div>
            <div class="flex-1 pt-2">
                <h2 class="text-2xl font-extrabold"><?= $d['nama_menu'] ?></h2>
                <p class="text-stone-500 text-sm"><?= $d['nama_kantin'] ?></p>
            </div>
        </section>

        <section class="mb-10 text-center">
            <h3 class="text-lg font-bold mb-4">Bagaimana rasa makanannya?</h3>
            <div class="flex justify-center gap-3" id="star-container">
                <?php for($i=1; $i<=5; $i++): ?>
                    <button type="button" onclick="setRating(<?= $i ?>)" class="star-btn active transition-all" data-star="<?= $i ?>">
                        <span class="material-symbols-outlined !text-4xl">star</span>
                    </button>
                <?php endfor; ?>
            </div>
        </section>

        <section class="space-y-6">
            <textarea name="komentar" class="w-full min-h-[160px] bg-stone-100 border-none rounded-xl p-5" placeholder="Ceritakan pengalaman rasa Anda..."></textarea>
            
            <div class="grid grid-cols-2 gap-4">
                <label class="aspect-square bg-stone-100 rounded-xl flex flex-col items-center justify-center gap-2 border-2 border-dashed border-stone-300 cursor-pointer">
                    <input type="file" name="foto_ulasan" class="hidden">
                    <span class="material-symbols-outlined text-stone-400">add_a_photo</span>
                    <span class="text-[10px] font-bold uppercase text-stone-400">Tambah Foto</span>
                </label>
            </div>
        </section>
    </main>

    <footer class="fixed bottom-0 w-full p-6 bg-gradient-to-t from-surface to-transparent">
        <div class="max-w-2xl mx-auto">
            <button type="submit" class="w-full py-4 bg-[#b22204] text-white rounded-full font-bold text-lg shadow-lg">
                Kirim Review
            </button>
        </div>
    </footer>
</form>

<script>
function setRating(n) {
    document.getElementById('rating_value').value = n;
    const stars = document.querySelectorAll('.star-btn');
    stars.forEach((star, index) => {
        if (index < n) star.classList.add('active');
        else star.classList.remove('active');
    });
}
</script>
</body>
</html>
