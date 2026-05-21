<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');
kk_ensure_buyer_schema($koneksi);

if (!isset($_SESSION['id_user']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'pembeli') {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = (int)$_SESSION['id_user'];
$current_page = 'orders';

$sql = "
SELECT
    p.*,
    k.nama_kantin,
    k.logo,
    (
        SELECT m.foto
        FROM detail_pesanan dp
        LEFT JOIN menu m ON dp.id_menu = m.id_menu
        WHERE dp.id_pesanan = p.id_pesanan
        LIMIT 1
    ) AS foto_menu,
    COALESCE((
        SELECT SUM(dp.qty)
        FROM detail_pesanan dp
        WHERE dp.id_pesanan = p.id_pesanan
    ), 0) AS total_item
FROM pesanan p
JOIN kantin k ON p.id_kantin = k.id_kantin
WHERE p.id_user = $id_user
  AND p.status IN ('Pending', 'Diproses', 'Siap Diambil')
ORDER BY p.id_pesanan DESC
";

$query = mysqli_query($koneksi, $sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?= t('buyer.active_orders') ?> - Kantin Kita</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800;900&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<style>
* { scrollbar-width: thin; scrollbar-color: #fed7aa transparent; }
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-thumb { background: #fed7aa; border-radius: 10px; }

body {
    font-family: 'Be Vietnam Pro', sans-serif;
    background: linear-gradient(135deg, #fff7ed 0%, #fff5eb 30%, #ffe4e1 60%, #f0f4ff 100%);
    min-height: 100vh;
}
body::before {
    content: '';
    position: fixed;
    inset: 0;
    background:
        radial-gradient(circle at 15% 15%, rgba(249,115,22,0.07) 0%, transparent 40%),
        radial-gradient(circle at 85% 85%, rgba(139,92,246,0.05) 0%, transparent 40%);
    z-index: -1;
}

.headline { font-family: 'Plus Jakarta Sans', sans-serif; }
.material-symbols-outlined { font-variation-settings: 'FILL' 1,'wght' 500,'GRAD' 0,'opsz' 24; }

.gradient-text {
    background: linear-gradient(135deg, #f97316, #ea580c);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.order-card {
    background: rgba(255,255,255,0.97);
    backdrop-filter: blur(10px);
    border: 2px solid transparent;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.order-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(249,115,22,0.15);
    border-color: #fed7aa;
}

.status-badge {
    transition: all 0.2s;
}

.floating { animation: floating 3s ease-in-out infinite; }
@keyframes floating {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}

.status-step { height: 4px; border-radius: 999px; background: #e7e5e4; flex: 1; transition: background 0.3s; }
.status-step.active { background: linear-gradient(135deg, #f97316, #ea580c); }
</style>
</head>

<body class="text-gray-800 pb-32">

<!-- HEADER -->
<header class="bg-white/90 backdrop-blur-xl sticky top-0 z-40 border-b-2 border-orange-100 shadow-sm">
    <div class="max-w-[1400px] mx-auto px-4 py-4 flex items-center justify-between gap-3">
        <div class="flex items-center gap-4">
            <a href="dashboard.php" class="w-11 h-11 rounded-2xl bg-gradient-to-br from-orange-500 to-orange-600 text-white flex items-center justify-center shadow-lg shadow-orange-200 hover:scale-105 transition-all">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <div>
                <h1 class="headline font-black text-xl gradient-text">Pesanan Aktif</h1>
                <p class="text-xs text-gray-500 font-semibold">Pantau pesananmu di sini</p>
            </div>
        </div>
        <a href="riwayat_pembeli.php" class="w-11 h-11 rounded-2xl bg-orange-50 text-orange-600 flex items-center justify-center hover:bg-orange-100 transition-all">
            <i class="fa-solid fa-clock-rotate-left text-lg"></i>
        </a>
    </div>
</header>

<main class="max-w-[1400px] mx-auto px-4 py-6">
    <?php if (mysqli_num_rows($query) > 0): ?>
    <div class="space-y-4">
        <?php while ($row = mysqli_fetch_assoc($query)): ?>
        <?php
            $status = $row['status'];
            $statusColor = 'bg-amber-100 text-amber-700 border-amber-200';
            $statusIcon = 'hourglass_empty';
            $step = 1;
            $stepLabel = ['Pending', 'Diproses', 'Siap Diambil'];

            if ($status === 'Diproses') {
                $statusColor = 'bg-blue-100 text-blue-700 border-blue-200';
                $statusIcon = 'skillet';
                $step = 2;
            }
            if ($status === 'Siap Diambil') {
                $statusColor = 'bg-green-100 text-green-700 border-green-200';
                $statusIcon = 'check_circle';
                $step = 3;
            }

            $ringkasan = mysqli_query($koneksi, "
                SELECT COALESCE(dp.nama_menu, m.nama_menu) AS nama_menu, dp.qty
                FROM detail_pesanan dp
                LEFT JOIN menu m ON dp.id_menu = m.id_menu
                WHERE dp.id_pesanan = ".(int)$row['id_pesanan']."
                LIMIT 2
            ");
            $items = [];
            while ($r = mysqli_fetch_assoc($ringkasan)) {
                $items[] = trim($r['nama_menu'] . ' x' . (int)$r['qty']);
            }
        ?>

        <article class="order-card rounded-3xl overflow-hidden">
            <!-- Header -->
            <div class="p-5 flex gap-4 bg-gradient-to-r from-orange-50 to-transparent border-b-2 border-orange-100">
                <img src="<?= kk_upload_url($row['foto_menu'] ?? '', 'menu') ?>"
                     class="w-20 h-20 rounded-2xl object-cover bg-orange-50 border-2 border-white shadow-md"
                     onerror="this.src='../../public/assets/img/default-food.svg'">
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 truncate">
                                <?= htmlspecialchars($row['kode_pesanan'] ?: '#'.str_pad($row['id_pesanan'], 5, '0', STR_PAD_LEFT)) ?>
                            </p>
                            <h2 class="headline font-extrabold text-lg leading-tight text-gray-900 mt-1">
                                <?= htmlspecialchars($row['nama_kantin']) ?>
                            </h2>
                            <p class="text-xs text-gray-400 mt-1 font-semibold">
                                <?= (int)$row['total_item'] ?> item • <?= date('d M, H:i', strtotime($row['tanggal'])) ?>
                            </p>
                        </div>
                        <span class="status-badge px-3 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wide flex items-center gap-1.5 border <?= $statusColor ?>">
                            <span class="material-symbols-outlined text-sm"><?= $statusIcon ?></span>
                            <?= htmlspecialchars($status) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Items Preview -->
            <?php if (!empty($items)): ?>
            <div class="px-5 py-3 bg-gray-50 border-b border-gray-100">
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($items as $item): ?>
                    <span class="bg-white border border-gray-200 text-xs font-semibold px-3 py-1.5 rounded-xl text-gray-600">
                        <i class="fa-solid fa-bowl-food text-orange-400 text-[10px] mr-1"></i><?= htmlspecialchars($item) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Catatan -->
            <?php if (!empty($row['catatan'])): ?>
            <div class="px-5 py-3 bg-amber-50 border-b border-amber-100 flex items-start gap-2">
                <i class="fa-solid fa-note-sticky text-amber-500 text-sm mt-0.5"></i>
                <p class="text-xs text-amber-800 font-semibold"><?= htmlspecialchars($row['catatan']) ?></p>
            </div>
            <?php endif; ?>

            <!-- Step Progress -->
            <div class="px-5 pt-4 pb-2 flex items-center gap-2">
                <?php foreach ($stepLabel as $i => $lbl): ?>
                <div class="flex items-center gap-2 flex-1">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black <?= $i+1 <= $step ? 'bg-gradient-to-br from-orange-500 to-orange-600 text-white shadow-lg shadow-orange-200' : 'bg-gray-200 text-gray-400' ?>">
                        <?php if ($i+1 < $step): ?>
                        <i class="fa-solid fa-check text-[10px]"></i>
                        <?php else: ?>
                        <span><?= $i+1 ?></span>
                        <?php endif; ?>
                    </div>
                    <span class="text-[10px] font-bold <?= $i+1 <= $step ? 'text-orange-600' : 'text-gray-400' ?> hidden sm:block"><?= $lbl ?></span>
                    <?php if ($i < 2): ?>
                    <div class="status-step flex-1 <?= $i+1 < $step ? 'active' : '' ?>"></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Footer -->
            <div class="px-5 pb-5 pt-2">
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <p class="text-[10px] uppercase font-black text-gray-400 tracking-widest">Total</p>
                        <p class="headline font-black text-2xl gradient-text">Rp <?= number_format($row['total_harga'],0,',','.') ?></p>
                    </div>
                    <div class="flex gap-2">
                        <a href="lacak_pesanan.php?id=<?= (int)$row['id_pesanan'] ?>"
                           class="flex items-center gap-2 px-5 py-3 bg-gradient-to-r from-orange-500 to-orange-600 text-white font-bold rounded-2xl text-sm shadow-lg shadow-orange-200 hover:shadow-xl hover:scale-105 transition-all">
                            Lacak <i class="fa-solid fa-chevron-right text-xs"></i>
                        </a>
                        <?php if ($row['status'] === 'Pending'): ?>
                        <button onclick="if(confirm('Batalkan pesanan ini?')){ location.href='batalkan_pesanan.php?id=<?= (int)$row['id_pesanan'] ?>'; }"
                                class="flex items-center gap-1 px-4 py-3 bg-red-50 text-red-600 font-bold rounded-2xl text-sm border border-red-100 hover:bg-red-100 transition-all">
                            <i class="fa-solid fa-xmark text-xs"></i> Batal
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </article>
        <?php endwhile; ?>
    </div>

    <?php else: ?>
    <!-- EMPTY STATE -->
    <div class="text-center py-24">
        <div class="floating">
            <div class="w-28 h-28 mx-auto mb-6 rounded-full bg-gradient-to-br from-orange-100 to-orange-200 flex items-center justify-center">
                <i class="fa-solid fa-receipt text-5xl text-orange-300"></i>
            </div>
        </div>
        <h3 class="headline font-black text-2xl text-gray-700 mb-2">Belum Ada Pesanan</h3>
        <p class="text-gray-400 mb-8 max-w-xs mx-auto">Yuk mulai pesan makanan favoritmu dan nikmati kemudahan!</p>
        <a href="dashboard.php" class="inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-orange-500 to-orange-600 text-white font-bold rounded-full shadow-xl shadow-orange-200 hover:shadow-2xl hover:scale-105 transition-all">
            <i class="fa-solid fa-utensils"></i> Pesan Sekarang
        </a>
    </div>
    <?php endif; ?>
</main>

<?php
$current_page = 'orders';
include(__DIR__ . '/../../includes/navbar.php');
?>

</body>
</html>