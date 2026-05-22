<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'penjual') {
    header("Location: ../auth/login.php");
    exit;
}

$id_k = (int)($_SESSION['id_kantin'] ?? 0);

$query = mysqli_query($koneksi, "
    SELECT
        p.*,
        u.username,
        u.email,
        k.nama_kantin,
        k.logo
    FROM pesanan p
    JOIN users u ON p.id_user = u.id_user
    JOIN kantin k ON p.id_kantin = k.id_kantin
    WHERE p.id_kantin = $id_k
      AND p.status IN ('Selesai', 'Dibatalkan')
    ORDER BY p.id_pesanan DESC
");

$rows = [];
$total_selesai = 0;
$total_batal = 0;
$total_pendapatan = 0;

while ($r = mysqli_fetch_assoc($query)) {
    $rows[] = $r;
    if ($r['status'] === 'Selesai') {
        $total_selesai++;
        $total_pendapatan += (float)($r['total_harga'] ?? 0) - (float)($r['pajak'] ?? 0);
    }
    if ($r['status'] === 'Dibatalkan') {
        $total_batal++;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Riwayat Penjualan - Kantin Kita</title>

<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>

<style>
:root {
    --brand:    #b22204;
    --brand-dk: #8b1a03;
    --brand-lt: #fff1ee;
    --brand-md: #fcd5cc;
    --ink:      #1a1714;
    --muted:    #7c746e;
    --line:     #ede9e5;
    --surface:  #faf8f6;
}
* { box-sizing:border-box; }
body { font-family:'DM Sans',sans-serif; background:var(--surface); color:var(--ink); }
h1,h2,h3,h4 { font-family:'Plus Jakarta Sans',sans-serif; }

.summary-grid {
    display:grid;
    grid-template-columns:repeat(3, minmax(0, 1fr));
    gap:.65rem;
}
.history-card {
    background:#fff;
    border:1.5px solid var(--line);
    border-radius:1.1rem;
    overflow:hidden;
    min-height:100%;
    transition:box-shadow .2s, border-color .2s;
}
.history-card:hover { box-shadow:0 6px 24px rgba(178,34,4,.08); border-color:var(--brand-md); }
.badge {
    display:inline-flex;
    align-items:center;
    gap:.3rem;
    padding:.25rem .75rem;
    border-radius:999px;
    font-size:.68rem;
    font-weight:900;
    white-space:nowrap;
}
.badge-selesai { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; }
.badge-batal { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }
.meta-chip {
    display:inline-flex;
    align-items:center;
    gap:.35rem;
    min-height:1.65rem;
}
.toast {
    position:fixed; bottom:1.5rem; right:1.5rem; z-index:99;
    background:var(--ink); color:#fff;
    padding:.7rem 1.2rem; border-radius:.9rem;
    font-size:.8rem; font-weight:700;
    display:flex; align-items:center; gap:.5rem;
    box-shadow:0 8px 24px rgba(0,0,0,.2);
}
.overlay {
    position:fixed; inset:0; z-index:60;
    background:rgba(15,15,15,.55);
    backdrop-filter:blur(6px);
    display:flex; align-items:center; justify-content:center; padding:1rem;
    opacity:0; pointer-events:none;
    transition:opacity .2s;
}
.overlay.show { opacity:1; pointer-events:all; }
.modal-box {
    background:#fff; border-radius:1.2rem;
    width:100%; max-width:420px; padding:1.5rem;
    transform:translateY(20px) scale(.96); transition:all .25s cubic-bezier(.34,1.56,.64,1);
    box-shadow:0 24px 60px rgba(0,0,0,.18);
}
.overlay.show .modal-box { transform:translateY(0) scale(1); }
.btn-print {
    display:inline-flex; align-items:center; justify-content:center; gap:.4rem;
    padding:.65rem 1rem; border-radius:.8rem;
    font-size:.8rem; font-weight:800; transition:all .15s;
}
.struk-dash { border:none; border-top:1px dashed #bbb; margin:.4rem 0; }
#print-area { display:none; }
@media print {
    body > *:not(#print-area) { display:none !important; }
    #print-area {
        display:block !important;
        position:fixed; inset:0;
        width:80mm; margin:0 auto; padding:8mm;
        background:#fff; font-family:'Courier New',monospace;
        font-size:11px; color:#000;
    }
}
</style>
</head>

<body class="flex min-h-screen">

<?php include '../../includes/sidebar_penjual.php'; ?>

<main class="flex-1 lg:ml-72 p-4 md:p-6 xl:p-8 w-full">

<header class="mt-14 lg:mt-0 mb-7">
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <span class="text-[9px] font-black uppercase tracking-[.2em] text-[var(--muted)]">Arsip Transaksi</span>
            <h1 class="text-[1.9rem] font-extrabold mt-0.5 leading-none">Riwayat Penjualan</h1>
            <p class="text-[var(--muted)] text-xs mt-1.5">Pesanan selesai dan dibatalkan tersimpan di sini</p>
        </div>

        <div class="summary-grid w-full sm:w-auto sm:min-w-[430px]">
            <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-2.5 text-center">
                <p class="text-2xl font-extrabold text-green-600 leading-none"><?= $total_selesai ?></p>
                <p class="text-[9px] font-black uppercase tracking-wider text-green-600 opacity-70">Selesai</p>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-2.5 text-center">
                <p class="text-2xl font-extrabold text-red-600 leading-none"><?= $total_batal ?></p>
                <p class="text-[9px] font-black uppercase tracking-wider text-red-600 opacity-70">Batal</p>
            </div>
            <div class="bg-orange-50 border border-orange-200 rounded-xl px-4 py-2.5 text-center">
                <p class="text-lg font-extrabold text-[var(--brand)] leading-none">Rp <?= number_format($total_pendapatan,0,',','.') ?></p>
                <p class="text-[9px] font-black uppercase tracking-wider text-[var(--brand)] opacity-70">Pendapatan</p>
            </div>
        </div>
    </div>

    <div class="mt-3 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
        <div class="flex items-center gap-1.5 bg-green-50 border border-green-100 rounded-lg px-3.5 py-2 text-xs text-green-700 font-semibold w-fit">
            <span class="material-symbols-outlined" style="font-size:14px;font-variation-settings:'FILL' 1">check_circle</span>
            Pesanan yang ditandai selesai otomatis pindah dari Pesanan Masuk ke Riwayat
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="pesanan_masuk.php"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-[var(--line)] rounded-xl text-xs font-bold text-[var(--ink)] hover:border-[var(--brand-md)] hover:text-[var(--brand)] transition">
                <span class="material-symbols-outlined" style="font-size:15px">arrow_back</span>
                Pesanan Masuk
            </a>
            <button onclick="location.reload()"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-[var(--brand)] rounded-xl text-xs font-bold text-white hover:bg-[var(--brand-dk)] transition">
                <span class="material-symbols-outlined" style="font-size:15px">refresh</span>
                Refresh
            </button>
        </div>
    </div>
</header>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-4 max-w-none items-start">

<?php if (count($rows) > 0): ?>
<?php foreach ($rows as $p):
    $id_p = (int)$p['id_pesanan'];

    $dq = mysqli_query($koneksi, "
        SELECT
            dp.qty,
            dp.subtotal,
            dp.catatan,
            COALESCE(dp.nama_menu, m.nama_menu) AS nama_menu,
            COALESCE(NULLIF(dp.harga, 0), m.harga, 0) AS harga,
            m.foto,
            u.rating,
            u.komentar
        FROM detail_pesanan dp
        LEFT JOIN menu m ON dp.id_menu = m.id_menu
        LEFT JOIN ulasan u ON u.id_menu = dp.id_menu AND u.id_user = ".(int)$p['id_user']."
        WHERE dp.id_pesanan = $id_p
    ");

    $items = [];
    $jml = 0;
    while ($d = mysqli_fetch_assoc($dq)) {
        $d['qty'] = (int)($d['qty'] ?? 0);
        $d['harga'] = (float)($d['harga'] ?? 0);
        $d['subtotal'] = (float)($d['subtotal'] ?? 0);

        if ($d['subtotal'] <= 0 && $d['harga'] > 0 && $d['qty'] > 0) {
            $d['subtotal'] = $d['harga'] * $d['qty'];
        }
        if ($d['harga'] <= 0 && $d['subtotal'] > 0 && $d['qty'] > 0) {
            $d['harga'] = $d['subtotal'] / $d['qty'];
        }

        $jml += $d['qty'];
        $items[] = $d;
    }

    $total_tampil = (float)($p['total_harga'] ?? 0);
    if ($total_tampil <= 0) {
        $total_tampil = array_sum(array_column($items, 'subtotal'));
    }
    $subtotal_items = array_sum(array_column($items, 'subtotal'));
    $pajak = (int)($p['pajak'] ?? 0);
    if ($pajak <= 0 && $total_tampil > $subtotal_items) {
        $pajak = (int)($total_tampil - $subtotal_items);
    }

    $pendapatan = max(0, $total_tampil - $pajak);

    $is_selesai = $p['status'] === 'Selesai';
    $badge_class = $is_selesai ? 'badge-selesai' : 'badge-batal';
    $badge_icon = $is_selesai ? 'task_alt' : 'cancel';
?>

<article id="pesanan-<?= $id_p ?>" class="history-card">
    <div class="flex items-center justify-between gap-3 px-5 pt-4 pb-3 border-b border-[var(--line)]">
        <div class="flex items-center gap-3 min-w-0">
            <img src="../../uploads/logo/<?= htmlspecialchars($p['logo'] ?? '') ?>"
                 class="w-10 h-10 rounded-lg object-cover border border-stone-100 shrink-0"
                 onerror="this.src='../../uploads/logo/logo_1778890101.png'">
            <div class="min-w-0">
                <p class="text-[9px] font-black uppercase tracking-widest text-[var(--muted)] leading-none mb-0.5 truncate">
                    <?= htmlspecialchars($p['nama_kantin']) ?>
                </p>
                <p class="font-extrabold text-base leading-none truncate">
                    #<?= htmlspecialchars($p['kode_pesanan'] ?: str_pad($id_p, 4, '0', STR_PAD_LEFT)) ?>
                </p>
            </div>
        </div>

        <span class="badge <?= $badge_class ?>">
            <span class="material-symbols-outlined" style="font-size:13px;font-variation-settings:'FILL' 1"><?= $badge_icon ?></span>
            <?= htmlspecialchars($p['status']) ?>
        </span>
    </div>

    <div class="px-5 py-2.5 flex flex-wrap gap-x-3 gap-y-1.5 bg-[var(--brand-lt)] border-b border-[var(--line)]">
        <span class="meta-chip text-xs font-bold">
            <span class="material-symbols-outlined text-[var(--brand)]" style="font-size:13px;font-variation-settings:'FILL' 1">person</span>
            <?= htmlspecialchars($p['username']) ?>
        </span>
        <span class="meta-chip text-xs text-[var(--muted)]">
            <span class="material-symbols-outlined text-[var(--brand)]" style="font-size:13px">schedule</span>
            <?= date('d M Y, H:i', strtotime($p['tanggal'])) ?>
        </span>
        <?php if (!empty($p['nomor_antrean'])): ?>
        <span class="meta-chip text-xs font-bold text-[var(--brand)]">
            <span class="material-symbols-outlined" style="font-size:13px">confirmation_number</span>
            Antrean <?= htmlspecialchars($p['nomor_antrean']) ?>
        </span>
        <?php endif; ?>
        <?php if (!empty($p['metode_pembayaran'])): ?>
        <span class="meta-chip text-xs text-[var(--muted)]">
            <span class="material-symbols-outlined text-[var(--brand)]" style="font-size:13px">payments</span>
            <?= htmlspecialchars($p['metode_pembayaran']) ?>
        </span>
        <?php endif; ?>
    </div>

    <?php if (!empty($p['catatan'])): ?>
    <div class="px-5 py-2 bg-amber-50 border-b border-amber-100 flex items-start gap-1.5">
        <span class="material-symbols-outlined text-amber-500 shrink-0" style="font-size:13px;margin-top:1px">sticky_note_2</span>
        <p class="text-xs text-amber-800"><span class="font-bold">Catatan:</span> <?= htmlspecialchars($p['catatan']) ?></p>
    </div>
    <?php endif; ?>

    <div class="divide-y divide-[var(--line)]">
        <?php foreach ($items as $d): ?>
        <div class="flex items-center gap-3 px-5 py-2.5">
            <div class="relative shrink-0">
                <img src="../../uploads/<?= htmlspecialchars($d['foto'] ?? '') ?>"
                     class="w-11 h-11 rounded-xl object-cover bg-stone-100"
                     onerror="this.src='../../public/assets/img/default-food.svg'">
                <span class="absolute -top-1 -right-1 bg-[var(--brand)] text-white font-black rounded-full flex items-center justify-center"
                      style="width:1rem;height:1rem;font-size:.55rem;">
                    <?= $d['qty'] ?>
                </span>
            </div>

            <div class="flex-1 min-w-0">
                <p class="font-bold text-sm truncate"><?= htmlspecialchars($d['nama_menu']) ?></p>
                <p class="text-[11px] text-[var(--muted)]">
                    <?= $d['qty'] ?><?= $d['harga'] > 0 ? ' x Rp '.number_format($d['harga'],0,',','.') : ' item' ?>
                </p>
                <?php if (!empty($d['catatan'])): ?>
                <p class="text-[10px] text-amber-600 mt-0.5">Catatan: <?= htmlspecialchars($d['catatan']) ?></p>
                <?php endif; ?>
                <?php if (!empty($d['rating'])): ?>
                <p class="text-[10px] text-yellow-700 mt-0.5">
                    <?= str_repeat('★', (int)$d['rating']) ?> <?= htmlspecialchars($d['komentar'] ?: 'Diulas pembeli') ?>
                </p>
                <?php endif; ?>
            </div>

            <?php if ($d['subtotal'] > 0): ?>
            <div class="text-sm font-extrabold text-[var(--brand)] shrink-0 tabular-nums">
                Rp <?= number_format($d['subtotal'],0,',','.') ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="px-5 py-3.5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-stone-50/60 border-t border-[var(--line)]">
        <div>
            <p class="text-[9px] font-black uppercase tracking-widest text-[var(--muted)]">Total Pendapatan</p>
            <p class="text-xl font-extrabold text-[var(--brand)] tabular-nums">
                <?= $pendapatan > 0 ? 'Rp '.number_format($pendapatan,0,',','.') : 'Total belum tersedia' ?>
            </p>
            <p class="text-[11px] text-[var(--muted)]"><?= $jml ?> item</p>
        </div>

        <button type="button" onclick="bukaStruk('<?= $id_p ?>')"
           class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-white border border-[var(--line)] rounded-xl text-xs font-bold text-[var(--ink)] hover:border-[var(--brand-md)] hover:text-[var(--brand)] transition">
            <span class="material-symbols-outlined" style="font-size:15px">receipt_long</span>
            Struk
        </button>
    </div>
</article>

<div id="struk-data-<?= $id_p ?>" class="hidden">
<div style="font-family:'Courier New',monospace;font-size:.72rem;line-height:1.7;color:#111;">
    <div style="text-align:center;margin-bottom:.6rem;">
        <p style="font-size:1rem;font-weight:900;letter-spacing:.06em;"><?= strtoupper(htmlspecialchars($p['nama_kantin'])) ?></p>
        <p style="font-size:.65rem;color:#555;">Kantin Kita</p>
        <p style="font-size:.6rem;color:#888;"><?= date('d/m/Y H:i:s', strtotime($p['tanggal'])) ?></p>
    </div>

    <hr class="struk-dash">

    <table style="width:100%;border-collapse:collapse;font-size:.7rem;">
        <tr><td>No. Pesanan</td><td style="text-align:right;font-weight:900;">#<?= htmlspecialchars($p['kode_pesanan'] ?: str_pad($id_p, 4, '0', STR_PAD_LEFT)) ?></td></tr>
        <tr><td>Pembeli</td><td style="text-align:right;font-weight:700;"><?= htmlspecialchars($p['username']) ?></td></tr>
        <tr><td>Status</td><td style="text-align:right;"><?= htmlspecialchars($p['status']) ?></td></tr>
        <?php if (!empty($p['metode_pembayaran'])): ?>
        <tr><td>Bayar via</td><td style="text-align:right;"><?= htmlspecialchars($p['metode_pembayaran']) ?></td></tr>
        <?php endif; ?>
        <?php if (!empty($p['catatan'])): ?>
        <tr><td colspan="2" style="color:#666;font-size:.65rem;">Catatan: <?= htmlspecialchars($p['catatan']) ?></td></tr>
        <?php endif; ?>
    </table>

    <hr class="struk-dash">
    <p style="font-weight:900;margin-bottom:.25rem;">DETAIL PESANAN</p>

    <?php foreach ($items as $d):
        $sat2 = (float)$d['harga'];
        $subtotal2 = (float)$d['subtotal'];
    ?>
    <table style="width:100%;border-collapse:collapse;margin-bottom:.25rem;">
        <tr><td colspan="2" style="font-weight:700;"><?= htmlspecialchars($d['nama_menu']) ?></td></tr>
        <tr>
            <td style="padding-left:.35rem;color:#555;">
                <?= $d['qty'] ?><?= $sat2 > 0 ? ' x Rp '.number_format($sat2,0,',','.') : ' item' ?>
            </td>
            <td style="text-align:right;font-weight:700;"><?= $subtotal2 > 0 ? 'Rp '.number_format($subtotal2,0,',','.') : '' ?></td>
        </tr>
        <?php if (!empty($d['catatan'])): ?>
        <tr><td colspan="2" style="padding-left:.35rem;color:#888;font-size:.62rem;">* <?= htmlspecialchars($d['catatan']) ?></td></tr>
        <?php endif; ?>
    </table>
    <?php endforeach; ?>

    <hr class="struk-dash">

    <table style="width:100%;border-collapse:collapse;font-size:.7rem;">
        <tr><td>Subtotal</td><td style="text-align:right;"><?= $subtotal_items > 0 ? 'Rp '.number_format($subtotal_items,0,',','.') : '-' ?></td></tr>
        <tr><td>Biaya Admin</td><td style="text-align:right;"><?= $pajak > 0 ? 'Rp '.number_format($pajak,0,',','.') : '-' ?></td></tr>
        <tr><td>Jumlah Item</td><td style="text-align:right;"><?= $jml ?> item</td></tr>
        <tr>
            <td style="font-size:.85rem;font-weight:900;padding-top:.2rem;">TOTAL PENDAPATAN</td>
            <td style="text-align:right;font-size:.85rem;font-weight:900;"><?= $pendapatan > 0 ? 'Rp '.number_format($pendapatan,0,',','.') : '-' ?></td>
        </tr>
    </table>

    <hr class="struk-dash">
    <p style="text-align:center;font-size:.63rem;color:#666;margin-top:.35rem;">
        Terima kasih sudah berbelanja.
    </p>
</div>
</div>

<?php endforeach; ?>

<?php else: ?>
<div class="bg-white border border-[var(--line)] rounded-2xl py-14 px-8 text-center max-w-3xl xl:col-span-2">
    <div class="w-14 h-14 bg-[var(--brand-lt)] rounded-xl flex items-center justify-center mx-auto mb-4">
        <span class="material-symbols-outlined text-[var(--brand)]" style="font-size:32px;font-variation-settings:'FILL' 1">history</span>
    </div>
    <h3 class="text-lg font-extrabold">Belum Ada Riwayat</h3>
    <p class="text-[var(--muted)] text-sm mt-1">Pesanan selesai dan dibatalkan akan muncul di sini.</p>
    <a href="pesanan_masuk.php"
       class="inline-flex items-center justify-center gap-1.5 mt-5 px-5 py-2 bg-[var(--brand-lt)] text-[var(--brand)] rounded-xl text-sm font-bold hover:bg-[var(--brand-md)] transition">
        <span class="material-symbols-outlined" style="font-size:15px">arrow_back</span>
        Kembali ke Pesanan Masuk
    </a>
</div>
<?php endif; ?>

</div>
</main>

<div id="overlay-struk" class="overlay" onclick="if(event.target===this)tutupStruk()">
    <div class="modal-box">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-extrabold text-base flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[var(--brand)]" style="font-size:17px;font-variation-settings:'FILL' 1">receipt_long</span>
                Preview Struk
            </h3>
            <button onclick="tutupStruk()" class="w-7 h-7 rounded-lg bg-stone-100 hover:bg-stone-200 flex items-center justify-center transition">
                <span class="material-symbols-outlined" style="font-size:15px">close</span>
            </button>
        </div>
        <div id="modal-struk-isi" class="bg-stone-50 border border-stone-200 rounded-xl p-4 mb-4 max-h-80 overflow-y-auto"></div>
        <div class="flex gap-2">
            <button onclick="doCetak()" class="flex-1 btn-print bg-[var(--brand)] text-white hover:bg-[var(--brand-dk)]">
                <span class="material-symbols-outlined" style="font-size:15px">print</span>
                Cetak
            </button>
            <button onclick="tutupStruk()" class="px-4 py-2.5 border border-stone-200 rounded-xl text-sm font-bold text-[var(--muted)] hover:bg-stone-50 transition">
                Batal
            </button>
        </div>
    </div>
</div>

<div id="print-area"></div>

<?php if (isset($_GET['success']) && $_GET['success'] === 'selesai'): ?>
<div id="toast" class="toast">
    <span class="material-symbols-outlined text-green-400" style="font-size:16px;font-variation-settings:'FILL' 1">check_circle</span>
    Pesanan selesai dan sudah masuk riwayat.
</div>
<script>
setTimeout(() => {
    const t = document.getElementById('toast');
    if (t) {
        t.style.transition = 'opacity .4s';
        t.style.opacity = '0';
        setTimeout(() => t.remove(), 400);
    }
}, 3500);
</script>
<?php endif; ?>

<script>
let _sid = null;

function bukaStruk(id) {
    _sid = id;
    const src = document.getElementById('struk-data-' + id);
    if (!src) return;
    document.getElementById('modal-struk-isi').innerHTML = src.innerHTML;
    document.getElementById('overlay-struk').classList.add('show');
}

function tutupStruk() {
    document.getElementById('overlay-struk').classList.remove('show');
    _sid = null;
}

function doCetak() {
    if (!_sid) return;
    const src = document.getElementById('struk-data-' + _sid);
    if (!src) return;
    document.getElementById('print-area').innerHTML = src.innerHTML;
    window.print();
}
</script>
</body>
</html>
