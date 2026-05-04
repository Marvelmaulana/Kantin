<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'penjual') {
    header("Location: login.php");
    exit;
}

$id_t = $_GET['id'];
$id_k = $_SESSION['id_kantin'];

// Ambil info utama transaksi
$query_t = mysqli_query($koneksi, "SELECT transaksi.*, users.username FROM transaksi 
    JOIN users ON transaksi.id_user = users.id_user 
    WHERE transaksi.id_transaksi = '$id_t' AND transaksi.id_kantin = '$id_k'");
$data_t = mysqli_fetch_assoc($query_t);

// Ambil rincian menu yang dibeli
$query_d = mysqli_query($koneksi, "SELECT detail_transaksi.*, menu.nama_menu, menu.foto_menu 
    FROM detail_transaksi 
    JOIN menu ON detail_transaksi.id_menu = menu.id_menu 
    WHERE detail_transaksi.id_transaksi = '$id_t'");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rincian Pesanan #<?= $id_t ?></title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&family=Be+Vietnam+Pro:wght@400;500&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#b22204",
                        "on-surface": "#271815",
                        "on-surface-variant": "#5b403b",
                        "surface": "#fff8f6",
                        "surface-container-low": "#fff0ee",
                        "surface-container-high": "#ffe2dc",
                        "surface-container-highest": "#f9dcd6",
                        "tertiary-fixed": "#c2e8ff",
                        "secondary-fixed": "#ffdad3"
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Be Vietnam Pro', sans-serif; overflow-x: hidden; }
        h1, h2, h3 { font-family: 'Plus Jakarta Sans', sans-serif; }
        #sidebar { transition: transform 0.3s ease-in-out; }
    </style>
</head>
<body class="bg-surface text-on-surface">

<div class="flex min-h-screen relative">
    <button onclick="toggleSidebar()" class="lg:hidden fixed top-4 left-4 z-[60] bg-primary text-white p-2 rounded-xl shadow-lg">
        <span class="material-symbols-outlined">menu</span>
    </button>

    <div id="overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>

    <aside id="sidebar" class="h-screen w-64 fixed left-0 top-0 flex flex-col bg-white border-r border-orange-50 z-50 -translate-x-full lg:translate-x-0">
        <div class="flex flex-col h-full p-4 gap-2">
            <div class="px-4 py-6 mb-4 flex justify-between items-center">
                <div>
                    <h1 class="text-xl font-black text-primary">Kantin Kita</h1>
                    <p class="text-xs font-medium text-on-surface-variant uppercase tracking-widest">Seller Center</p>
                </div>
                <button onclick="toggleSidebar()" class="lg:hidden text-on-surface-variant">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <nav class="flex-1 space-y-1">
                <a class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-high rounded-xl text-sm transition-all" href="dashboard_penjual.php">
                    <span class="material-symbols-outlined text-[20px]">dashboard</span> Dashboard
                </a>
                <a class="flex items-center gap-3 px-4 py-3 text-on-surface-variant hover:bg-surface-container-high rounded-xl text-sm transition-all" href="kelola_menu_penjual.php">
                    <span class="material-symbols-outlined text-[20px]">restaurant_menu</span> Kelola Menu
                </a>
                <a class="flex items-center gap-3 px-4 py-3 bg-primary/10 text-primary rounded-xl font-bold text-sm" href="pesanan_masuk.php">
                    <span class="material-symbols-outlined text-[20px]">pending_actions</span> Pesanan Masuk
                </a>
            </nav>

            <div class="mt-auto pt-4 border-t border-orange-100">
                <a class="flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-xl font-bold text-sm transition-all" href="../logout.php">
                    <span class="material-symbols-outlined text-[20px]">logout</span> Logout
                </a>
            </div>
        </div>
    </aside>

    <main class="flex-1 w-full lg:ml-64 p-6 md:p-8">
        <!-- HEADER -->
        <div class="mb-6 mt-12 lg:mt-0">
            <h2 class="text-2xl font-extrabold">Rincian Pesanan</h2>
            <p class="text-sm text-on-surface-variant">
                Detail transaksi pelanggan
            </p>
        </div>

        <!-- CARD UTAMA -->
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-orange-50">

            <!-- INFO ATAS -->
            <div class="flex justify-between items-center border-b pb-4 mb-4">
                <div>
                    <p class="text-xs text-on-surface-variant">Pesanan Dari</p>
                    <h3 class="font-bold text-lg"><?= strtoupper($data_t['username']) ?></h3>
                    <p class="text-xs text-on-surface-variant">
                        ID #<?= $id_t ?> • <?= $data_t['created_at'] ?>
                    </p>
                </div>

                <span class="px-3 py-1 rounded-full text-xs font-bold 
                    <?= $data_t['status']=='pending' ? 'bg-yellow-100 text-yellow-700' : '' ?>
                    <?= $data_t['status']=='dibayar' ? 'bg-blue-100 text-blue-700' : '' ?>
                    <?= $data_t['status']=='selesai' ? 'bg-green-100 text-green-700' : '' ?>">
                    <?= strtoupper($data_t['status']) ?>
                </span>
            </div>

            <!-- TABEL -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-on-surface-variant border-b">
                            <th class="py-3">Menu</th>
                            <th>Jumlah</th>
                            <th>Harga</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($d = mysqli_fetch_assoc($query_d)) : ?>
                        <tr class="border-b hover:bg-orange-50">
                            <td class="py-3 flex items-center gap-3">
                                <img src="../../uploads/<?= $d['foto_menu'] ?>" 
                                     class="w-12 h-12 object-cover rounded-lg">
                                <span class="font-semibold"><?= $d['nama_menu'] ?></span>
                            </td>
                            <td><?= $d['jumlah'] ?>x</td>
                            <td>Rp <?= number_format($d['subtotal']/$d['jumlah'],0,',','.') ?></td>
                            <td class="font-bold text-primary">
                                Rp <?= number_format($d['subtotal'],0,',','.') ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- TOTAL -->
            <div class="flex justify-between items-center mt-6">
                <a href="pesanan_masuk.php" 
                   class="px-4 py-2 rounded-xl bg-gray-100 text-sm font-bold hover:bg-gray-200">
                    Kembali
                </a>

                <div class="text-right">
                    <p class="text-xs text-on-surface-variant">Total</p>
                    <p class="text-xl font-black text-primary">
                        Rp <?= number_format($data_t['total'],0,',','.') ?>
                    </p>
                </div>
            </div>

            <!-- BUTTON AKSI -->
            <div class="mt-6 flex justify-end">
                <?php if($data_t['status'] == 'pending') : ?>
                    <a href="proses_status.php?id=<?= $id_t ?>&status=dibayar"
                       class="bg-primary text-white px-6 py-3 rounded-xl font-bold hover:opacity-90">
                        Konfirmasi Pembayaran
                    </a>

                <?php elseif($data_t['status'] == 'dibayar') : ?>
                    <a href="proses_status.php?id=<?= $id_t ?>&status=selesai"
                       class="bg-blue-600 text-white px-6 py-3 rounded-xl font-bold hover:opacity-90">
                        Selesaikan Pesanan
                    </a>
                <?php endif; ?>
            </div>

        </div>
    </main>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        
        if (sidebar.classList.contains('-translate-x-full')) {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        } else {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }
    }
</script>

</body>
</html>

