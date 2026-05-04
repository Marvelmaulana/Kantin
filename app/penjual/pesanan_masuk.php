<?php
session_start();
include(__DIR__ . '/../../config/config.php');

// 1. PROTEKSI HALAMAN
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'penjual') {
    header("Location: ../auth/login.php");
    exit;
}

$id_k = $_SESSION['id_kantin'] ?? 0;

// 2. AMBIL DATA PESANAN (Status selain Selesai & Dibatalkan agar fokus ke pesanan aktif)
$query = mysqli_query($koneksi, "SELECT pesanan.*, users.username 
                                 FROM pesanan 
                                 JOIN users ON pesanan.id_user = users.id_user 
                                 WHERE pesanan.id_kantin = '$id_k' 
                                 AND pesanan.status != 'Selesai' 
                                 AND pesanan.status != 'Dibatalkan'
                                 ORDER BY pesanan.id_pesanan DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Pesanan Masuk - Kantin Kita</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: { 
                extend: { 
                    colors: { 
                        "primary": "#b22204", 
                        "surface": "#fff8f6",
                        "soft-orange": "#fff0ee"
                    } 
                } 
            }
        }
    </script>
    <style>
        body { font-family: 'Be Vietnam Pro', sans-serif; }
        h1, h2, h3, h4 { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-surface text-stone-800 flex min-h-screen relative">

    <?php include '../../includes/sidebar_penjual.php'; ?>

    <main class="flex-1 w-full lg:ml-72 p-4 md:p-8 transition-all">
        <header class="mb-10 mt-14 lg:mt-0">
            <h2 class="text-3xl font-extrabold tracking-tight text-stone-900">Pesanan Masuk</h2>
            <p class="text-stone-500 text-sm mt-1">Pantau dan update status pesanan pelanggan secara real-time.</p>
        </header>

        <div class="max-w-5xl space-y-6">
            <?php if(mysqli_num_rows($query) > 0): ?>
                <?php while($p = mysqli_fetch_assoc($query)): ?>
                    <div class="bg-white rounded-[2.5rem] border border-orange-50 shadow-sm overflow-hidden transition-all hover:shadow-xl">
                        
                        <div class="p-6 md:p-8 border-b border-stone-50 flex flex-wrap justify-between items-center gap-4">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 bg-soft-orange rounded-2xl flex items-center justify-center text-primary font-black text-lg border border-orange-100">
                                    #<?= $p['id_pesanan'] ?>
                                </div>
                                <div>
                                    <h3 class="font-bold text-xl text-stone-900"><?= $p['username'] ?></h3>
                                    <div class="flex items-center gap-3 mt-1">
                                        <span class="text-[10px] bg-stone-100 px-2.5 py-1 rounded-lg text-stone-500 font-black uppercase tracking-widest border border-stone-200">
                                            <?= $p['metode_pembayaran'] ?>
                                        </span>
                                        <span class="text-xs text-stone-400 font-medium flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[14px]">schedule</span>
                                            <?= date('H:i', strtotime($p['tanggal'])) ?> WIB
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <span class="px-5 py-2 bg-orange-50 text-primary text-[11px] font-black uppercase rounded-full border border-primary/20 shadow-sm">
                                    <?= $p['status'] ?>
                                </span>
                            </div>
                        </div>

                        <div class="p-6 md:p-8 bg-stone-50/40 space-y-5">
                            <?php
                            $id_p = $p['id_pesanan'];
                            $query_detail = mysqli_query($koneksi, "SELECT dp.*, m.nama_menu, m.foto 
                                                                   FROM detail_pesanan dp
                                                                   JOIN menu m ON dp.id_menu = m.id_menu 
                                                                   WHERE dp.id_pesanan = '$id_p'");
                            
                            while($d = mysqli_fetch_assoc($query_detail)):
                            ?>
                                <div class="flex items-center gap-5">
                                    <div class="relative group">
                                        <img src="../../uploads/<?= $d['foto'] ?>" 
                                             class="w-20 h-20 rounded-[1.5rem] object-cover shadow-md border-2 border-white transition-transform group-hover:scale-105" 
                                             onerror="this.src='https://placehold.co/200x200?text=Food'">
                                        <span class="absolute -top-2 -right-2 bg-primary text-white text-[10px] font-black w-7 h-7 flex items-center justify-center rounded-full border-2 border-white shadow-lg">
                                            <?= $d['qty'] ?>
                                        </span>
                                    </div>
                                    
                                    <div class="flex-1">
                                        <p class="font-bold text-md text-stone-800 leading-tight">
                                            <?= $d['nama_menu'] ?> 
                                        </p>
                                        <div class="flex items-start gap-1 mt-1 text-stone-400">
                                            <span class="material-symbols-outlined text-[16px] mt-0.5">notes</span>
                                            <p class="text-xs italic font-medium">
                                                <?= $d['catatan'] ?: 'Tanpa catatan khusus' ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-black text-stone-700">Rp <?= number_format($d['subtotal'], 0, ',', '.') ?></p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>

                        <div class="p-6 md:p-8 flex flex-col md:flex-row justify-between items-center gap-8 bg-white">
                            <div>
                                <span class="text-[10px] text-stone-400 font-bold uppercase tracking-widest block mb-1">Ringkasan Pembayaran</span>
                                <p class="text-3xl font-black text-primary tracking-tight">Rp <?= number_format($p['total_harga'], 0, ',', '.') ?></p>
                            </div>

                            <form action="update_status.php" method="POST" class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                                <input type="hidden" name="id_pesanan" value="<?= $p['id_pesanan'] ?>">
                                <select name="status_baru" class="rounded-2xl border-stone-200 text-sm font-bold text-stone-600 focus:ring-primary focus:border-primary transition-all px-4 py-3 bg-stone-50 min-w-[200px]">
                                    <option value="Proses Masak" <?= $p['status'] == 'Proses Masak' ? 'selected' : '' ?>>👨‍🍳 Proses Masak</option>
                                    <option value="Siap Diambil" <?= $p['status'] == 'Siap Diambil' ? 'selected' : '' ?>>🥡 Siap Diambil</option>
                                    <option value="Selesai" <?= $p['status'] == 'Selesai' ? 'selected' : '' ?>>✅ Selesai (Selesai)</option>
                                    <option value="Dibatalkan" <?= $p['status'] == 'Dibatalkan' ? 'selected' : '' ?>>❌ Batalkan</option>
                                </select>
                                <button type="submit" class="bg-primary text-white px-10 py-3 rounded-2xl font-bold text-sm shadow-xl shadow-red-900/20 hover:bg-red-800 active:scale-95 transition-all flex items-center justify-center gap-2">
                                    <span class="material-symbols-outlined text-[18px]">sync_alt</span>
                                    Update
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="bg-white rounded-[3rem] p-20 text-center border-2 border-dashed border-orange-100 flex flex-col items-center">
                    <div class="w-24 h-24 bg-soft-orange rounded-full flex items-center justify-center mb-6">
                        <span class="material-symbols-outlined text-primary text-5xl">order_approve</span>
                    </div>
                    <h3 class="text-stone-800 font-extrabold text-2xl" style="font-family: 'Plus Jakarta Sans', sans-serif;">Dapur Sedang Tenang</h3>
                    <p class="text-stone-400 text-sm mt-2 max-w-sm">Belum ada pesanan masuk saat ini. Pastikan kantin kamu sudah dalam status 'Buka' di pengaturan menu.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>