<?php
// Selalu mulai session di paling atas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Gunakan path absolut untuk meminimalisir error file tidak ditemukan
include(__DIR__ . '/../../config/config.php');

// DEBUG: Aktifkan 2 baris di bawah ini jika masih mental ke login 
// untuk melihat isi session kamu yang sebenarnya.
// print_r($_SESSION); 
// die();

// 1. CEK LOGIN DENGAN LEBIH TELITI
// Pastikan role yang dicek sama persis (kecil/besar hurufnya) dengan saat login
if (!isset($_SESSION['id_user']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'pembeli') {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

// 2. QUERY AMBIL PESANAN AKTIF
$sql = "SELECT * FROM pesanan WHERE id_user='$id_user' AND status NOT IN ('Selesai', 'Dibatalkan') ORDER BY id_pesanan DESC";
$query = mysqli_query($koneksi, $sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Pesanan Aktif - Kantin Kita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@700;800&family=Be+Vietnam+Pro:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#b22204",
                        "surface": "#fffdfc",
                        "on-surface": "#271815",
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Be Vietnam Pro', sans-serif; background-color: #fffdfc; }
        .font-headline { font-family: 'Plus Jakarta Sans', sans-serif; }
        .floating-nav {
            position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%);
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);
            height: 75px; display: flex; justify-content: space-around; align-items: center;
            z-index: 50; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); border-radius: 25px; width: 90%; max-width: 450px;
        }
    </style>
</head>
<body class="text-stone-800 pb-32">

<header class="bg-white sticky top-0 z-40 px-6 py-4 flex items-center gap-4 shadow-sm">
    <button onclick="window.location.href='dashboard.php'" class="material-symbols-outlined text-stone-400">arrow_back</button>
    <h1 class="text-lg font-extrabold font-headline italic uppercase tracking-tighter text-primary">Pesanan Aktif</h1>
</header>

<main class="max-w-xl mx-auto px-6 py-8">
    <div class="space-y-4">
        <?php if (mysqli_num_rows($query) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($query)): 
                $status = $row['status'];
                // Warna status khusus
                $statusColor = ($status == 'Siap Diambil') ? "bg-green-100 text-green-700" : "bg-orange-100 text-orange-700";
                if ($status == 'Diproses') $statusColor = "bg-blue-100 text-blue-700";
            ?>
            
            <div class="bg-white rounded-[2rem] p-5 shadow-sm border border-stone-100 relative overflow-hidden transition-all active:scale-[0.98]">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-orange-50 flex items-center justify-center text-primary">
                            <span class="material-symbols-outlined <?= ($status != 'Menunggu') ? 'animate-pulse' : '' ?>">restaurant</span>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-stone-400 uppercase tracking-widest">Order #<?= $row['id_pesanan'] ?></p>
                            <p class="text-sm font-semibold text-stone-600 italic">
                                <?= ($status == 'Siap Diambil') ? 'Pesanan siap di meja!' : 'Sedang disiapkan...'; ?>
                            </p>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter <?= $statusColor ?>">
                        <?= $status ?>
                    </span>
                </div>

                <div class="flex items-end justify-between mt-4 pt-4 border-t border-dashed border-stone-100">
                    <div>
                        <p class="text-[10px] text-stone-400 font-bold uppercase mb-1">Total Pembayaran</p>
                        <p class="text-xl font-black text-primary">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></p>
                    </div>
                    
                    <button onclick="window.location.href='lacak_pesanan.php?id=<?= $row['id_pesanan'] ?>'" 
                            class="bg-stone-900 text-white px-5 py-2.5 rounded-xl text-xs font-bold shadow-lg shadow-stone-200 active:scale-90 transition-transform flex items-center gap-2">
                        <span>Lacak</span>
                        <span class="material-symbols-outlined text-sm">near_me</span>
                    </button>
                </div>
            </div>
            <?php endwhile; ?>

        <?php else: ?>
            <div class="text-center py-24">
                <div class="w-20 h-20 bg-stone-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-4xl text-stone-200">moped</span>
                </div>
                <h3 class="font-bold text-stone-400 uppercase text-sm tracking-widest italic">Tidak Ada Pesanan</h3>
                <p class="text-xs text-stone-300 mt-1">Semua pesananmu sudah sampai atau belum ada pesanan baru.</p>
                <button onclick="window.location.href='dashboard.php'" class="mt-6 px-8 py-3 bg-primary text-white rounded-full font-bold text-xs shadow-xl shadow-red-900/10 active:scale-95 transition-all uppercase tracking-widest">Pesan Sekarang</button>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php 
  $current_page = 'orders';
  include(__DIR__ . '/../../includes/navbar.php'); 
?>

</body>
</html>