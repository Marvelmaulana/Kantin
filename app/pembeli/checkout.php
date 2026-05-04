<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user'])) { header("Location: ../auth/login.php"); exit(); }
$id_user = $_SESSION['id_user'];

// Ambil ID Menu dan QTY dari URL (untuk Pesan Sekarang)
$id_menu_langsung = isset($_GET['id_menu']) ? mysqli_real_escape_string($koneksi, $_GET['id_menu']) : null;
$qty_langsung = isset($_GET['qty']) ? (int)$_GET['qty'] : 1;

if ($id_menu_langsung) {
    // MODE: PESAN SEKARANG
    // Mengambil data menu langsung dan menggunakan QTY dari URL
    $query = mysqli_query($koneksi, "SELECT menu.*, '$qty_langsung' AS qty, '' AS catatan, kantin.nama_kantin 
                                     FROM menu 
                                     JOIN kantin ON menu.id_kantin = kantin.id_kantin 
                                     WHERE menu.id_menu = '$id_menu_langsung'");
} else {
    // MODE: KERANJANG
    $query = mysqli_query($koneksi, "SELECT keranjang.*, menu.nama_menu, menu.harga, menu.foto, kantin.nama_kantin 
                                     FROM keranjang 
                                     JOIN menu ON keranjang.id_menu = menu.id_menu 
                                     JOIN kantin ON menu.id_kantin = kantin.id_kantin 
                                     WHERE keranjang.id_user = '$id_user'");
}

if (mysqli_num_rows($query) == 0) {
    echo "<script>alert('Pesanan kosong!'); window.location='dashboard.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Checkout - Kantin Kita</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700;800&family=Be+Vietnam+Pro:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { "primary": "#b22204", "surface": "#fff8f6" } } }
        }
    </script>
    <style>
        /* Style untuk border yang aktif */
        .payment-card.active {
            border-color: #b22204;
            background-color: #fff8f6;
        }
    </style>
</head>
<body class="bg-surface pb-32">
    <header class="bg-white/80 backdrop-blur-md sticky top-0 z-50 shadow-sm px-6 py-4 flex items-center gap-4">
        <button onclick="history.back()" class="material-symbols-outlined">arrow_back</button>
        <h1 class="text-primary font-bold text-lg">Checkout</h1>
    </header>

    <main class="max-w-2xl mx-auto px-6 py-8">
        <h2 class="text-2xl font-extrabold mb-6 italic font-headline">Ringkasan Pesanan</h2>
        
        <?php 
        $subtotal = 0;
        while($row = mysqli_fetch_assoc($query)) : 
            $item_total = $row['harga'] * $row['qty'];
            $subtotal += $item_total;
        ?>
        <div class="bg-white rounded-2xl p-6 mb-4 shadow-sm border-l-4 border-primary flex justify-between">
            <div class="flex gap-4">
                <img class="w-16 h-16 rounded-xl object-cover" src="../../uploads/<?= $row['foto']; ?>" onerror="this.src='https://placehold.co/100x100?text=Food'"/>
                <div>
                    <p class="font-bold text-stone-900"><?= $row['nama_menu']; ?></p>
                    <p class="text-sm text-stone-500"><?= $row['qty']; ?>x - <?= $row['catatan'] ?: 'Tanpa catatan'; ?></p>
                    <p class="text-xs text-primary font-bold uppercase"><?= $row['nama_kantin']; ?></p>
                </div>
            </div>
            <p class="font-bold text-primary">Rp <?= number_format($item_total, 0, ',', '.'); ?></p>
        </div>
        <?php endwhile; ?>

        <h2 class="text-2xl font-extrabold mt-10 mb-6 italic font-headline">Metode Pembayaran</h2>
        <div class="space-y-4">
            <label id="label-ewallet" class="payment-card flex items-center p-4 bg-white rounded-2xl border-2 border-primary gap-4 cursor-pointer active">
                <input type="radio" name="payment" value="ewallet" checked class="hidden" onchange="togglePaymentStyle()">
                <div class="w-6 h-6 border-2 border-primary rounded-full flex items-center justify-center p-1">
                    <div id="dot-ewallet" class="w-full h-full bg-primary rounded-full"></div>
                </div>
                <span class="font-bold">E-Wallet (Saldo)</span>
            </label>

            <label id="label-cash" class="payment-card flex items-center p-4 bg-white rounded-2xl border-2 border-transparent gap-4 cursor-pointer">
                <input type="radio" name="payment" value="cash" class="hidden" onchange="togglePaymentStyle()">
                <div class="w-6 h-6 border-2 border-stone-300 rounded-full flex items-center justify-center p-1">
                    <div id="dot-cash" class="w-full h-full bg-transparent rounded-full"></div>
                </div>
                <span class="font-bold">Tunai (Bayar di Kantin)</span>
            </label>
        </div>

        <div class="mt-10 p-8 bg-white rounded-3xl shadow-lg border border-stone-100">
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <div>
                    <p class="text-xs font-bold text-stone-400 uppercase">Total Bayar</p>
                    <p class="text-3xl font-black text-primary">Rp <?= number_format($subtotal, 0, ',', '.'); ?></p>
                </div>
                <button onclick="handlePayment()" class="w-full md:w-auto bg-primary text-white px-10 py-4 rounded-full font-bold shadow-lg shadow-red-900/20 active:scale-95 transition-all">
                    Konfirmasi Pembayaran
                </button>
            </div>
        </div>
    </main>

    <script>
        function togglePaymentStyle() {
            const ewalletLabel = document.getElementById('label-ewallet');
            const cashLabel = document.getElementById('label-cash');
            const ewalletDot = document.getElementById('dot-ewallet');
            const cashDot = document.getElementById('dot-cash');
            const isEwallet = document.querySelector('input[value="ewallet"]').checked;

            if (isEwallet) {
                // Ewallet Aktif
                ewalletLabel.classList.add('active', 'border-primary');
                ewalletLabel.classList.remove('border-transparent');
                ewalletDot.classList.replace('bg-transparent', 'bg-primary');
                
                // Cash Mati
                cashLabel.classList.remove('active', 'border-primary');
                cashLabel.classList.add('border-transparent');
                cashDot.classList.replace('bg-primary', 'bg-transparent');
            } else {
                // Cash Aktif
                cashLabel.classList.add('active', 'border-primary');
                cashLabel.classList.remove('border-transparent');
                cashDot.classList.replace('bg-transparent', 'bg-primary');

                // Ewallet Mati
                ewalletLabel.classList.remove('active', 'border-primary');
                ewalletLabel.classList.add('border-transparent');
                ewalletDot.classList.replace('bg-primary', 'bg-transparent');
            }
        }

        function handlePayment() {
            const selected = document.querySelector('input[name="payment"]:checked').value;
            window.location.href = `proses_checkout.php?metode=${selected}`;
        }
    </script>
</body>
</html>