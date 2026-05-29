<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');
kk_ensure_buyer_schema($koneksi);

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'pembeli') {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = (int)$_SESSION['id_user'];
$current_page = 'history';

$methodConfig = [
    'GOPAY' => ['color' => '#00AED6', 'bg' => '#e6f7fb', 'icon' => 'payments', 'label' => 'GoPay'],
    'OVO'   => ['color' => '#4C3494', 'bg' => '#ede8f5', 'icon' => 'wallet', 'label' => 'OVO'],
    'DANA'  => ['color' => '#108BE3', 'bg' => '#e3f1fc', 'icon' => 'account_balance_wallet', 'label' => 'DANA'],
];

$sql = "
SELECT
    p.*,
    k.nama_kantin,
    k.logo,
    u.username AS nama_pembeli,
    COALESCE((
        SELECT SUM(dp.qty)
        FROM detail_pesanan dp
        WHERE dp.id_pesanan = p.id_pesanan
    ), 0) AS total_item,
    (
        SELECT m.foto
        FROM detail_pesanan dp
        LEFT JOIN menu m ON dp.id_menu = m.id_menu
        WHERE dp.id_pesanan = p.id_pesanan
        LIMIT 1
    ) AS foto_menu
FROM pesanan p
JOIN kantin k ON p.id_kantin = k.id_kantin
JOIN users u ON p.id_user = u.id_user
WHERE p.id_user = $id_user
  AND p.status IN ('Selesai', 'Dibatalkan')
ORDER BY p.id_pesanan DESC
";

$query = mysqli_query($koneksi, $sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Riwayat Pesanan</title>

<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@700;800;900&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1" rel="stylesheet"/>

<style>
* { box-sizing:border-box; }
html, body { overflow-x:hidden; max-width:100%; }
body {
    font-family:'Be Vietnam Pro',sans-serif;
    background:
        radial-gradient(circle at top left, rgba(255,107,53,.13), transparent 28rem),
        radial-gradient(circle at top right, rgba(16,185,129,.10), transparent 25rem),
        #fffdfc;
}
.font-headline { font-family:'Plus Jakarta Sans',sans-serif; }
.material-symbols-outlined {
    font-variation-settings:'FILL' 1,'wght' 500,'GRAD' 0,'opsz' 24;
    display:inline-flex; align-items:center; justify-content:center; line-height:1;
}
.receipt-modal {
    position:fixed; inset:0; z-index:80; display:none; align-items:center; justify-content:center;
    padding:18px; background:rgba(28,22,19,.48); backdrop-filter:blur(8px);
}
.receipt-modal.show { display:flex; }
.receipt-box { width:100%; max-width:430px; max-height:88vh; overflow:auto; }
.divider { border-top:2px dashed #e7e5e4; }
@media print {
    body > *:not(#print-area) { display:none !important; }
    #print-area { display:block !important; padding:12px; background:white; }
    #print-area .receipt-print { box-shadow:none !important; border:1px solid #eee; }
}
#print-area { display:none; }
</style>
</head>

<body class="text-stone-800 pb-32">

<header class="bg-white/90 backdrop-blur-xl sticky top-0 z-40 border-b-2 border-orange-100 shadow-sm">
    <div class="max-w-[1400px] mx-auto px-4 py-4 flex items-center justify-between gap-3">
        <div class="flex items-center gap-4">
            <a href="dashboard.php" class="w-11 h-11 rounded-2xl bg-gradient-to-br from-orange-500 to-orange-600 text-white flex items-center justify-center shadow-lg shadow-orange-200 hover:scale-105 transition-all">
                <span class="material-symbols-outlined text-xl">arrow_back</span>
            </a>
            <div>
                <h1 class="headline font-black text-xl gradient-text">Riwayat Pesanan</h1>
                <p class="text-xs text-gray-500 font-semibold">Pesanan selesai &amp; batal</p>
            </div>
        </div>
        <a href="pesanan.php" class="w-11 h-11 rounded-2xl bg-orange-50 text-orange-600 flex items-center justify-center hover:bg-orange-100 transition-all">
            <i class="fa-solid fa-clock-rotate-left text-lg"></i>
        </a>
    </div>
</header>

<main class="max-w-[1400px] mx-auto px-4 md:px-6 py-5">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 md:gap-4">
        <?php if (mysqli_num_rows($query) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($query)): ?>
        <?php
            $id_pesanan = (int)$row['id_pesanan'];
            $metode = strtoupper($row['metode_pembayaran'] ?: 'DANA');
            $cfg = $methodConfig[$metode] ?? $methodConfig['DANA'];
            $ref_number = strtoupper(substr($metode, 0, 3)) . '-' . date('Ymd', strtotime($row['tanggal'])) . '-' . str_pad($id_pesanan, 5, '0', STR_PAD_LEFT);
            $isSelesai = $row['status'] === 'Selesai';
            $statusColor = $isSelesai ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
            $statusIcon = $isSelesai ? 'check_circle' : 'cancel';

            $q_detail = mysqli_query($koneksi, "
                SELECT
                    dp.qty,
                    dp.id_detail,
                    dp.id_menu,
                    dp.subtotal,
                    dp.catatan,
                    COALESCE(dp.nama_menu, m.nama_menu) AS nama_menu,
                    COALESCE(NULLIF(dp.harga, 0), m.harga, 0) AS harga,
                    m.foto,
                    k.nama_kantin,
                    u.rating,
                    u.komentar
                FROM detail_pesanan dp
                LEFT JOIN menu m ON dp.id_menu = m.id_menu
                LEFT JOIN kantin k ON m.id_kantin = k.id_kantin
                LEFT JOIN ulasan u ON u.id_menu = dp.id_menu AND u.id_user = $id_user
                WHERE dp.id_pesanan = $id_pesanan
            ");

            $items = [];
            $total_tampil = (float)($row['total_harga'] ?? 0);
            while ($item = mysqli_fetch_assoc($q_detail)) {
                $item['qty'] = (int)($item['qty'] ?? 0);
                $item['harga'] = (float)($item['harga'] ?? 0);
                $item['subtotal'] = (float)($item['subtotal'] ?? 0);
                if ($item['subtotal'] <= 0 && $item['harga'] > 0 && $item['qty'] > 0) {
                    $item['subtotal'] = $item['harga'] * $item['qty'];
                }
                if ($item['harga'] <= 0 && $item['subtotal'] > 0 && $item['qty'] > 0) {
                    $item['harga'] = $item['subtotal'] / $item['qty'];
                }
                $items[] = $item;
            }
            if ($total_tampil <= 0) {
                $total_tampil = array_sum(array_column($items, 'subtotal'));
            }
            $subtotal_items = array_sum(array_column($items, 'subtotal'));
            $pajak = (int)($row['pajak'] ?? kk_checkout_tax());
            if ($pajak <= 0 && $total_tampil > $subtotal_items) {
                $pajak = (int)($total_tampil - $subtotal_items);
            }
            if ($total_tampil <= $subtotal_items) {
                $total_tampil = $subtotal_items + $pajak;
            }
        ?>

        <article class="bg-white/95 rounded-3xl border border-stone-100 shadow-sm overflow-hidden hover:shadow-xl hover:shadow-red-900/5 transition-all">
            <div class="p-4 flex gap-3">
                <img src="<?= kk_upload_url($row['foto_menu'] ?? '', 'menu') ?>"
                     class="w-20 h-20 rounded-2xl object-cover bg-stone-100 shrink-0"
                     onerror="this.src='../../public/assets/img/default-food.svg'">

                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-[10px] font-black uppercase tracking-[0.15em] text-stone-400 truncate">
                                <?= htmlspecialchars($row['kode_pesanan'] ?: '#'.str_pad($id_pesanan, 5, '0', STR_PAD_LEFT)) ?>
                            </p>
                            <h2 class="font-headline font-extrabold text-base leading-tight text-stone-800 truncate mt-1">
                                <?= htmlspecialchars($row['nama_kantin']) ?>
                            </h2>
                            <div class="text-[11px] text-stone-500 mt-1 flex flex-col gap-1">
                                <?php foreach ($items as $idx => $itm): if($idx >= 2) { echo '<span class="text-stone-400 italic">+'.(count($items)-2).' menu lainnya</span>'; break; } ?>
                                <a href="detail_menu.php?id=<?= $itm['id_menu'] ?>" class="hover:text-orange-600 hover:underline truncate inline-block">
                                    <?= (int)$itm['qty'] ?>x <?= htmlspecialchars($itm['nama_menu']) ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            <p class="text-[11px] text-stone-400 mt-2">
                                <?= date('d M Y, H:i', strtotime($row['tanggal'])) ?>
                            </p>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wide flex items-center gap-1 whitespace-nowrap <?= $statusColor ?>">
                            <span class="material-symbols-outlined text-xs"><?= $statusIcon ?></span>
                            <?= htmlspecialchars($row['status']) ?>
                        </span>
                    </div>

                    <div class="mt-3 flex items-end justify-between gap-3">
                        <div>
                            <p class="text-[10px] uppercase font-bold text-stone-400">Total</p>
                            <p class="text-lg font-black text-[#b22204]">Rp <?= number_format($total_tampil,0,',','.') ?></p>
                        </div>
                        <button onclick="openReceipt('receipt-<?= $id_pesanan ?>')"
                                class="bg-stone-900 text-white px-4 py-2.5 rounded-2xl text-xs font-black active:scale-95 transition-all flex items-center gap-2">
                            Struk
                            <span class="material-symbols-outlined text-sm">receipt_long</span>
                        </button>
                    </div>
                </div>
            </div>

            <div id="receipt-<?= $id_pesanan ?>" class="hidden">
                <div class="receipt-print bg-white rounded-[28px] overflow-hidden shadow-xl">
                    <div class="p-5" style="background:<?= $cfg['bg'] ?>;">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined" style="color:<?= $cfg['color'] ?>;font-variation-settings:'FILL' 1;"><?= $cfg['icon'] ?></span>
                                <span class="font-bold text-sm" style="color:<?= $cfg['color'] ?>"><?= $cfg['label'] ?></span>
                            </div>
                            <div class="px-3 py-1 rounded-full text-[11px] font-bold <?= $isSelesai ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                <?= strtoupper(htmlspecialchars($row['status'])) ?>
                            </div>
                        </div>
                        <p class="text-[11px] text-zinc-500 mt-3 font-mono"><?= $ref_number ?></p>
                    </div>

                    <div class="divider"></div>

                    <div class="p-5 space-y-4">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-zinc-400">Nama Kantin</p>
                            <h3 class="font-headline font-black text-xl text-stone-800 mt-1"><?= htmlspecialchars($row['nama_kantin']) ?></h3>
                        </div>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-widest text-zinc-400">Tanggal Pesanan</p>
                                <p class="font-semibold mt-1"><?= date('d M Y H:i', strtotime($row['tanggal'])) ?></p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-widest text-zinc-400">Nama Pembeli</p>
                                <p class="font-semibold mt-1 text-right"><?= htmlspecialchars($row['nama_pembeli'] ?? 'Pembeli') ?></p>
                            </div>
                        </div>
                        <div class="divider"></div>
                        <p class="text-[10px] font-bold uppercase tracking-widest text-zinc-400">Detail Menu</p>
                        <?php foreach ($items as $item): ?>
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="min-w-0">
                                    <p class="font-bold text-sm truncate"><?= htmlspecialchars($item['nama_menu']) ?></p>
                                    <p class="text-[11px] text-zinc-400 truncate">
                                        <?= (int)$item['qty'] ?> x Rp <?= number_format($item['harga'],0,',','.') ?>
                                    </p>
                                </div>
                            </div>
                            <div class="font-bold text-sm whitespace-nowrap">
                                Rp <?= number_format($item['subtotal'],0,',','.') ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (false && $isSelesai): ?>
                    <div class="px-5 pb-5">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-zinc-400 mb-3">Rating & Ulasan</p>
                        <div class="space-y-2">
                            <?php
                            $q_review_items = mysqli_query($koneksi, "
                                SELECT dp.id_detail, dp.id_menu, COALESCE(dp.nama_menu, m.nama_menu) AS nama_menu,
                                       u.rating, u.komentar
                                FROM detail_pesanan dp
                                LEFT JOIN menu m ON dp.id_menu = m.id_menu
                                LEFT JOIN ulasan u ON u.id_menu = dp.id_menu AND u.id_user = $id_user
                                WHERE dp.id_pesanan = $id_pesanan
                            ");
                            while ($rv = mysqli_fetch_assoc($q_review_items)):
                            ?>
                            <div class="flex items-center justify-between gap-3 rounded-2xl bg-zinc-50 border border-zinc-100 p-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold truncate"><?= htmlspecialchars($rv['nama_menu']) ?></p>
                                    <?php if (!empty($rv['rating'])): ?>
                                    <p class="text-[11px] text-yellow-600 font-bold mt-0.5">
                                        <?= str_repeat('★', (int)$rv['rating']) ?> <span class="text-zinc-400"><?= htmlspecialchars($rv['komentar'] ?: 'Sudah diulas') ?></span>
                                    </p>
                                    <?php else: ?>
                                    <p class="text-[11px] text-zinc-400">Bagikan pengalamanmu ke penjual.</p>
                                    <?php endif; ?>
                                </div>
                                <a href="ulasan.php?id_detail=<?= (int)$rv['id_detail'] ?>"
                                   class="shrink-0 inline-flex items-center gap-1 rounded-xl bg-[#b22204] text-white px-3 py-2 text-xs font-black">
                                    <span class="material-symbols-outlined text-sm">star</span>
                                    <?= !empty($rv['rating']) ? 'Edit' : 'Ulas' ?>
                                </a>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="divider"></div>

                    <div class="p-5 space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <span class="text-zinc-500">Subtotal</span>
                            <span class="font-semibold text-right">Rp <?= number_format($subtotal_items,0,',','.') ?></span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-zinc-500">Biaya Layanan</span>
                            <span class="font-semibold text-right">Rp <?= number_format($pajak,0,',','.') ?></span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-zinc-500">Metode Pembayaran</span>
                            <span class="font-bold" style="color:<?= $cfg['color'] ?>"><?= $cfg['label'] ?></span>
                        </div>
                        <?php if (!empty($row['catatan'])): ?>
                        <div class="pt-3">
                            <p class="text-zinc-500 mb-2">Catatan Pembeli</p>
                            <div class="bg-zinc-100 rounded-2xl p-3 text-sm text-zinc-700">
                                <?= nl2br(htmlspecialchars($row['catatan'])) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="p-5">
                        <div class="rounded-2xl p-4" style="background:<?= $cfg['bg'] ?>;">
                            <div class="flex justify-between items-center gap-4">
                                <span class="font-headline font-extrabold text-lg">Total Dibayar</span>
                                <span class="font-headline font-black text-2xl whitespace-nowrap" style="color:<?= $cfg['color'] ?>">
                                    Rp <?= number_format($total_tampil,0,',','.') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($isSelesai): ?>
            <div class="px-4 pb-4 -mt-1 flex flex-col gap-2 border-t border-stone-100 pt-3">
                <?php foreach ($items as $item): ?>
                <div class="flex items-center justify-between bg-zinc-50 rounded-xl p-2.5">
                    <a href="detail_menu.php?id=<?= $item['id_menu'] ?>" class="text-xs font-bold text-stone-700 hover:text-orange-600 truncate flex-1 pr-2">
                        <?= htmlspecialchars($item['nama_menu']) ?>
                    </a>
                    
                    <?php if (!empty($item['rating'])): ?>
                    <div class="flex flex-col items-end shrink-0">
                        <div class="flex items-center gap-1 text-yellow-500">
                            <span class="material-symbols-outlined text-[14px]">star</span>
                            <span class="text-xs font-black text-stone-700"><?= (int)$item['rating'] ?></span>
                        </div>
                        <?php if(!empty($item['komentar'])): ?>
                        <p class="text-[10px] text-stone-500 max-w-[150px] truncate">"<?= htmlspecialchars($item['komentar']) ?>"</p>
                        <?php endif; ?>
                    </div>
                    <a href="ulasan.php?id_detail=<?= (int)$item['id_detail'] ?>"
                       class="ml-3 shrink-0 inline-flex items-center gap-1 rounded-lg bg-stone-200 text-stone-600 px-2 py-1 text-[10px] font-black hover:bg-stone-300">
                        Edit
                    </a>
                    <?php else: ?>
                    <a href="ulasan.php?id_detail=<?= (int)$item['id_detail'] ?>"
                       class="shrink-0 inline-flex items-center gap-1 rounded-lg bg-yellow-100 text-yellow-700 px-3 py-1.5 text-[11px] font-black hover:bg-yellow-200">
                        <span class="material-symbols-outlined text-[14px]">star</span>
                        Ulas
                    </a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </article>
        <?php endwhile; ?>

        <?php else: ?>
        <div class="text-center py-24 lg:col-span-2">
            <div class="w-24 h-24 bg-stone-100 rounded-full flex items-center justify-center mx-auto mb-5">
                <span class="material-symbols-outlined text-5xl text-stone-300">history</span>
            </div>
            <h3 class="font-headline font-extrabold text-lg text-stone-500">Belum Ada Riwayat</h3>
            <p class="text-sm text-stone-400 mt-2 max-w-xs mx-auto">Pesanan selesai dan dibatalkan akan muncul di sini.</p>
            <button onclick="window.location.href='dashboard.php'"
                    class="mt-7 bg-[#b22204] text-white px-8 py-4 rounded-full text-sm font-black shadow-xl shadow-red-200 active:scale-95 transition-all">
                Pesan Sekarang
            </button>
        </div>
        <?php endif; ?>
    </div>
</main>

<div id="receipt-modal" class="receipt-modal" onclick="if(event.target===this)closeReceipt()">
    <div class="receipt-box">
        <div id="receipt-content"></div>
        <div class="grid grid-cols-2 gap-2 mt-3">
            <button onclick="printReceipt()" class="h-12 rounded-full bg-[#b22204] text-white font-black text-sm">Print Struk</button>
            <button onclick="closeReceipt()" class="h-12 rounded-full bg-white text-stone-700 font-black text-sm">Tutup</button>
        </div>
    </div>
</div>

<div id="print-area"></div>

<?php
// $current_page is already set at the top
include(__DIR__ . '/../../includes/navbar.php');
?>

<script>
let activeReceipt = '';

function openReceipt(id) {
    const source = document.getElementById(id);
    if (!source) return;
    activeReceipt = source.innerHTML;
    document.getElementById('receipt-content').innerHTML = activeReceipt;
    document.getElementById('receipt-modal').classList.add('show');
}

function closeReceipt() {
    document.getElementById('receipt-modal').classList.remove('show');
}

function printReceipt() {
    if (!activeReceipt) return;
    document.getElementById('print-area').innerHTML = activeReceipt;
    window.print();
}
</script>

</body>
</html>
