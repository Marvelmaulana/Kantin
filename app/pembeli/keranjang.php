<?php
session_start();
include(__DIR__ . '/../../config/config.php');

// 1. Proteksi Halaman
if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

// 2. Logika Hapus Item
if (isset($_GET['hapus'])) {
    $id_keranjang = mysqli_real_escape_string($koneksi, $_GET['hapus']);
    mysqli_query($koneksi, "DELETE FROM keranjang WHERE id_keranjang = '$id_keranjang' AND id_user = '$id_user'");
    header("Location: keranjang.php");
    exit();
}

// 3. Ambil Data Keranjang (Pastikan nama kolom foto sesuai database)
$sql = "SELECT keranjang.*, menu.nama_menu, menu.harga, menu.foto 
        FROM keranjang 
        JOIN menu ON keranjang.id_menu = menu.id_menu 
        WHERE keranjang.id_user = '$id_user'";
$query = mysqli_query($koneksi, $sql);
$total_items = mysqli_num_rows($query);
?>

<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Keranjang Belanja - Kantin Kita</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800&family=Be+Vietnam+Pro:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#b22204",
                        "surface": "#fff8f6",
                        "on-surface": "#271815",
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Be Vietnam Pro', sans-serif; }
        h1, h3 { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-surface text-on-surface antialiased min-h-screen">

<header class="fixed top-0 w-full z-50 bg-white/80 backdrop-blur-md shadow-sm">
    <div class="flex justify-between items-center px-6 py-4 w-full max-w-7xl mx-auto">
        <div class="flex items-center gap-4">
            <button class="material-symbols-outlined text-stone-500 p-2 rounded-full hover:bg-stone-100" onclick="window.location.href='dashboard.php'">arrow_back</button>
            <span class="text-xl font-extrabold tracking-tighter text-orange-700">Kantin Kita</span>
        </div>
    </div>
</header>

<main class="pt-24 pb-48 px-4 max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-2xl font-bold tracking-tight">Keranjang Belanja</h1>
        <span class="text-sm font-medium text-stone-500 bg-orange-100 px-3 py-1 rounded-full"><?= $total_items ?> Items</span>
    </div>

    <div class="space-y-4">
        <?php 
        $subtotal = 0;
        if ($total_items > 0):
            while($item = mysqli_fetch_assoc($query)): 
                $item_total = $item['harga'] * $item['qty'];
                $subtotal += $item_total;
        ?>
        <div class="flex items-center gap-4 bg-white p-4 rounded-2xl shadow-sm border border-orange-50 relative group">
            <div class="w-24 h-24 rounded-xl overflow-hidden flex-shrink-0 bg-stone-100 border border-stone-100">
                <img alt="<?= $item['nama_menu'] ?>" class="w-full h-full object-cover" 
                     src="../../uploads/<?= $item['foto'] ?>" 
                     onerror="this.src='https://via.placeholder.com/150?text=No+Image'"/>
            </div>
            
            <div class="flex-1 min-w-0">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-bold text-on-surface truncate text-lg"><?= $item['nama_menu'] ?></h3>
                        <?php if(!empty($item['catatan'])): ?>
                            <p class="text-[11px] text-orange-600 bg-orange-50 px-2 py-0.5 rounded-md inline-block mt-1 italic">
                                "<?= $item['catatan'] ?>"
                            </p>
                        <?php else: ?>
                            <p class="text-xs text-stone-400 mt-1">Tanpa catatan</p>
                        <?php endif; ?>
                    </div>
                    <a href="?hapus=<?= $item['id_keranjang'] ?>" onclick="return confirm('Hapus dari keranjang?')" class="material-symbols-outlined text-stone-300 hover:text-red-600 transition-colors">delete</a>
                </div>
                
                <div class="mt-4 flex justify-between items-center">
                    <span class="font-bold text-primary">Rp <?= number_format($item_total, 0, ',', '.') ?></span>
                    
                    <div class="flex items-center bg-stone-100 rounded-full p-1 border border-stone-200">
                        <button onclick="window.location.href='kurangi_keranjang.php?id=<?= $item['id_menu'] ?>'" class="w-8 h-8 flex items-center justify-center rounded-full bg-white text-stone-500 shadow-sm active:scale-90 transition-transform">
                            <span class="material-symbols-outlined text-sm">remove</span>
                        </button>
                        <span class="px-3 text-sm font-bold"><?= $item['qty'] ?></span>
                        <button onclick="window.location.href='tambah_keranjang.php?id=<?= $item['id_menu'] ?>'" class="w-8 h-8 flex items-center justify-center rounded-full bg-primary text-white shadow-sm active:scale-90 transition-transform">
                            <span class="material-symbols-outlined text-sm">add</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php 
            endwhile; 
        else:
        ?>
            <div class="text-center py-20 bg-white rounded-3xl border-2 border-dashed border-stone-200">
                <span class="material-symbols-outlined text-6xl text-stone-200">shopping_cart</span>
                <p class="mt-4 text-stone-500 font-medium">Wah, keranjangmu masih kosong nih.</p>
                <button onclick="window.location.href='dashboard.php'" class="mt-4 text-primary font-bold text-sm underline underline-offset-4">Mulai Belanja Sekarang</button>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($total_items > 0): ?>
    <button class="w-full mt-8 flex items-center justify-center gap-2 py-4 border-2 border-dashed border-orange-200 rounded-2xl text-stone-400 font-semibold hover:border-primary hover:text-primary transition-all group" onclick="window.location.href='dashboard.php'">
        <span class="material-symbols-outlined group-hover:scale-110 transition-transform">add_circle</span>
        Tambah Menu Lainnya
    </button>
    <?php endif; ?>
</main>

<?php if ($total_items > 0): ?>
<div class="fixed bottom-0 left-0 w-full bg-white/95 backdrop-blur-xl shadow-[0_-10px_30px_rgba(0,0,0,0.05)] px-6 pb-8 pt-6 z-50 border-t border-stone-100">
    <div class="max-w-2xl mx-auto space-y-4">
        <div class="flex flex-col gap-2">
            <div class="pt-3 flex justify-between items-end mt-2">
                <span class="text-on-surface font-bold text-lg">Total Pembayaran</span>
                <span class="text-primary text-2xl font-extrabold tracking-tight">Rp <?= number_format($subtotal, 0, ',', '.') ?></span>
            </div>
        </div>
        
        <button class="w-full bg-gradient-to-r from-[#b22204] to-[#d63c1e] text-white py-4 rounded-full font-bold text-lg shadow-lg shadow-primary/20 active:scale-95 transition-all flex items-center justify-center gap-3" onclick="window.location.href='checkout.php'">
            <span>Lanjut ke Pembayaran</span>
            <span class="material-symbols-outlined">shopping_cart_checkout</span>
        </button>
    </div>
</div>
<?php endif; ?>

</body>
</html>