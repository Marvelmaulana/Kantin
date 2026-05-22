<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/language_helper.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'penjual') {
    header("Location: ../auth/login.php");
    exit;
}

$id_k = $_SESSION['id_kantin'] ?? 0;

$query = mysqli_query($koneksi, "
SELECT 
    pesanan.*, 
    users.username,
    kantin.nama_kantin,
    kantin.logo
FROM pesanan
JOIN users  ON pesanan.id_user   = users.id_user
JOIN kantin ON pesanan.id_kantin = kantin.id_kantin
WHERE pesanan.id_kantin = '$id_k'
  AND pesanan.status IN ('Pending', 'Diproses', 'Siap Diambil')
ORDER BY pesanan.id_pesanan DESC
");

$total_pending  = 0;
$total_diproses = 0;
$total_siap     = 0;
$rows = [];

while ($r = mysqli_fetch_assoc($query)) {
    $rows[] = $r;
    if ($r['status'] == 'Pending')      $total_pending++;
    if ($r['status'] == 'Diproses')     $total_diproses++;
    if ($r['status'] == 'Siap Diambil') $total_siap++;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= t('seller.incoming_orders') ?> — <?= t('buyer.title') ?></title>

<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>

<style>
:root {
    --brand:    #b22204;
    --brand-light: #ff6b35;
    --brand-dk: #8b1a03;
    --brand-lt: #fff1ee;
    --brand-md: #fcd5cc;
    --ink:      #1a1714;
    --muted:    #7c746e;
    --line:     #ede9e5;
    --surface:  #fff9f7;
}
* { box-sizing: border-box; }

body { font-family:'Plus Jakarta Sans',sans-serif; background:var(--surface); color:var(--ink); }

/* GRADIENT BACKGROUND */
body::before {
    content: '';
    position: fixed;
    top: 0; left: 0; right: 0;
    height: 400px;
    background: linear-gradient(135deg, rgba(249,115,22,0.08) 0%, rgba(178,34,4,0.04) 50%, transparent 100%);
    z-index: -1;
}

/* STATUS BADGE */
.badge { display:inline-flex; align-items:center; gap:.3rem; padding:.3rem .8rem; border-radius:999px; font-size:.7rem; font-weight:800; }
.badge-pending  { background:linear-gradient(135deg,#fef3c7,#fde68a); color:#92400e; border:1px solid #fcd34d; box-shadow:0 4px 12px rgba(253,230,138,0.3); }
.badge-diproses { background:linear-gradient(135deg,#dbeafe,#bfdbfe); color:#1e40af; border:1px solid #93c5fd; box-shadow:0 4px 12px rgba(191,219,254,0.3); }
.badge-siap     { background:linear-gradient(135deg,#d1fae5,#a7f3d0); color:#065f46; border:1px solid #6ee7b7; box-shadow:0 4px 12px rgba(167,243,208,0.3); }

/* KARTU */
.order-card {
    background:#fff;
    border:1.5px solid var(--line);
    border-radius:1.2rem;
    overflow:hidden;
    transition: all .25s cubic-bezier(0.4,0,0.2,1);
    min-height:100%;
    box-shadow: 0 4px 15px rgba(0,0,0,0.04);
}
.order-card:hover { box-shadow:0 12px 35px rgba(178,34,4,.12); border-color:var(--brand-md); transform:translateY(-2px); }

.top-summary {
    display:grid;
    grid-template-columns:repeat(3, minmax(0, 1fr));
    gap:.65rem;
}

.meta-chip {
    display:inline-flex;
    align-items:center;
    gap:.35rem;
    min-height:1.65rem;
}

/* STEP BAR */
.stepbar { display:flex; align-items:center; }
.step-item { display:flex; flex-direction:column; align-items:center; gap:.25rem; }
.step-circle {
    width:1.75rem; height:1.75rem; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:.65rem; font-weight:900;
}
.step-lbl { font-size:.58rem; font-weight:700; white-space:nowrap; }
.s-done  .step-circle { background:linear-gradient(135deg,#d1fae5,#a7f3d0); color:#065f46; }
.s-active .step-circle { background:linear-gradient(135deg,#b22204,#ff6b35); color:#fff; box-shadow:0 0 0 4px rgba(252,213,204,.5); }
.s-wait  .step-circle { background:#f5f5f4; color:#c0bab5; }
.s-done  .step-lbl { color:#059669; }
.s-active .step-lbl { color:var(--brand); }
.s-wait  .step-lbl { color:#c0bab5; }
.step-line { flex:1; height:2px; min-width:1.5rem; border-radius:2px; margin-bottom:.9rem; }
.sl-done { background:linear-gradient(90deg,#a7f3d0,#d1fae5); }
.sl-wait { background:#e7e5e4; }

/* TOMBOL */
.btn-aksi {
    display:inline-flex; align-items:center; gap:.4rem;
    padding:.65rem 1.1rem; border-radius:.8rem;
    font-size:.8rem; font-weight:700; border:none; cursor:pointer;
    transition: all .15s;
    white-space:nowrap;
    box-shadow:0 4px 12px rgba(0,0,0,0.1);
}
.btn-proses  { background:linear-gradient(135deg,#dbeafe,#bfdbfe); color:#1e40af; }
.btn-proses:hover  { background:#bfdbfe; transform:translateY(-1px); box-shadow:0 6px 16px rgba(59,130,246,0.25); }
.btn-siap    { background:linear-gradient(135deg,#d1fae5,#a7f3d0); color:#065f46; }
.btn-siap:hover    { background:#a7f3d0; transform:translateY(-1px); box-shadow:0 6px 16px rgba(16,185,129,0.25); }
.btn-selesai { background:linear-gradient(135deg,#b22204,#ff6b35); color:#fff; }
.btn-selesai:hover { transform:translateY(-1px); box-shadow:0 8px 20px rgba(178,34,4,0.3); }

/* MODAL */
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

/* TOAST */
.toast {
    position:fixed; bottom:1.5rem; right:1.5rem; z-index:99;
    background:var(--ink); color:#fff;
    padding:.7rem 1.2rem; border-radius:.9rem;
    font-size:.8rem; font-weight:700;
    display:flex; align-items:center; gap:.5rem;
    box-shadow:0 8px 24px rgba(0,0,0,.2);
    animation: slideUp .3s ease;
}
@keyframes slideUp { from{transform:translateY(10px);opacity:0} to{transform:translateY(0);opacity:1} }

/* PRINT */
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
#print-area { display:none; }

.struk-dash { border:none; border-top:1px dashed #bbb; margin:.4rem 0; }
.no-print { }
</style>
</head>

<body class="flex min-h-screen bg-gradient-to-br from-[#fff9f7] via-white to-orange-50">

<?php include '../../includes/sidebar_penjual.php'; ?>

<main class="flex-1 lg:ml-72 p-4 md:p-6 xl:p-8 w-full">

<!-- HEADER -->
<header class="mt-14 lg:mt-0 mb-7">
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <span class="text-[9px] font-black uppercase tracking-[.2em] text-[var(--muted)]"><?= t('buyer.title') ?></span>
            <h1 class="text-[1.9rem] font-extrabold mt-0.5 leading-none"><?= t('seller.incoming_orders') ?></h1>
            <p class="text-[var(--muted)] text-xs mt-1.5"><?= date('l, d F Y') ?> · Sinkron dengan pembeli</p>
        </div>

        <div class="top-summary w-full sm:w-auto sm:min-w-[360px]">
            <?php
            $cs = [
                [$total_pending, 'Pending', 'bg-orange-50','border-orange-200','text-orange-600'],
                [$total_diproses,'Diproses','bg-blue-50',  'border-blue-200',  'text-blue-600'],
                [$total_siap,    'Siap',    'bg-green-50', 'border-green-200', 'text-green-600'],
            ];
            foreach($cs as [$v,$l,$bg,$bd,$tx]): ?>
            <div class="<?=$bg?> border <?=$bd?> rounded-xl px-4 py-2.5 text-center">
                <p class="text-2xl font-extrabold <?=$tx?> leading-none"><?=$v?></p>
                <p class="text-[9px] font-black uppercase tracking-wider <?=$tx?> opacity-70"><?=$l?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="mt-3 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
        <div class="flex items-center gap-1.5 bg-blue-50 border border-blue-100 rounded-lg px-3.5 py-2 text-xs text-blue-700 font-semibold w-fit">
            <span class="material-symbols-outlined" style="font-size:14px;font-variation-settings:'FILL' 1">sync</span>
            Perubahan status langsung terlihat oleh pembeli secara real-time
        </div>

        <div class="no-print flex flex-wrap gap-2">
            <button onclick="location.reload()"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-[var(--line)] rounded-xl text-xs font-bold text-[var(--ink)] hover:border-[var(--brand-md)] hover:text-[var(--brand)] transition">
                <span class="material-symbols-outlined" style="font-size:15px">refresh</span>
                <?= t('action.refresh') ?>
            </button>
            <a href="riwayat_penjual.php"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-[var(--line)] rounded-xl text-xs font-bold text-[var(--ink)] hover:border-[var(--brand-md)] hover:text-[var(--brand)] transition">
                <span class="material-symbols-outlined" style="font-size:15px">history</span>
                <?= t('nav.history') ?>
            </a>
            <a href="kelola_menu_penjual.php"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-[var(--brand)] rounded-xl text-xs font-bold text-white hover:bg-[var(--brand-dk)] transition">
                <span class="material-symbols-outlined" style="font-size:15px">restaurant_menu</span>
                <?= t('nav.manage_menu') ?>
            </a>
        </div>
    </div>
</header>

<!-- LIST -->
<div class="grid grid-cols-1 xl:grid-cols-2 gap-4 max-w-none items-start">

<?php if (count($rows) > 0): ?>
<?php foreach ($rows as $p):
    $id_p   = $p['id_pesanan'];
    $status = $p['status'];

    $badgeClass = 'badge-pending';
    if ($status == 'Diproses')     $badgeClass = 'badge-diproses';
    if ($status == 'Siap Diambil') $badgeClass = 'badge-siap';

    $stepIdx = 0;
    if ($status == 'Diproses')     $stepIdx = 1;
    if ($status == 'Siap Diambil') $stepIdx = 2;

    /* ─ Detail menu: ambil harga dari tabel menu ─ */
    $dq = mysqli_query($koneksi,"
        SELECT
            dp.id_detail,
            dp.qty,
            dp.subtotal,
            dp.catatan,
            COALESCE(dp.nama_menu, m.nama_menu) AS nama_menu,
            COALESCE(NULLIF(dp.harga, 0), m.harga, 0) AS harga,
            m.foto
        FROM detail_pesanan dp
        LEFT JOIN menu m ON dp.id_menu = m.id_menu
        WHERE dp.id_pesanan = '$id_p'
    ");
    $drows = []; $jml = 0;
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
        $drows[] = $d;
    }

    $total_tampil = (float)($p['total_harga'] ?? 0);
    if ($total_tampil <= 0) {
        $total_tampil = array_sum(array_column($drows, 'subtotal'));
    }
    $subtotal_items = array_sum(array_column($drows, 'subtotal'));
    $pajak = (int)($p['pajak'] ?? 0);
    if ($pajak <= 0 && $total_tampil > $subtotal_items) {
        $pajak = (int)($total_tampil - $subtotal_items);
    }
    $pendapatan = max(0, $total_tampil - $pajak);
?>

<div class="order-card">

    <!-- Header kartu -->
    <div class="flex items-center justify-between gap-3 px-5 pt-4 pb-3 border-b border-[var(--line)]">
        <div class="flex items-center gap-3">
<?php include(__DIR__ . '/../../includes/pembeli_helpers.php'); ?>
            <img
                src="<?= kk_upload_url($p['logo'] ?? '', 'logo') ?>"
                class="w-9 h-9 rounded-lg object-cover border border-stone-100 shrink-0"
                onerror="this.src='../../public/assets/img/default-logo.svg'"
            >
            <div>
                <p class="text-[9px] font-black uppercase tracking-widest text-[var(--muted)] leading-none mb-0.5">
                    <?= htmlspecialchars($p['nama_kantin']) ?>
                </p>
                <p class="font-extrabold text-base leading-none">
                    #<?= htmlspecialchars($p['kode_pesanan']) ?>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <span class="badge <?= $badgeClass ?>">
                <span class="material-symbols-outlined" style="font-size:11px;font-variation-settings:'FILL' 1">
                    <?= $status=='Diproses'?'skillet':($status=='Siap Diambil'?'check_circle':'hourglass_empty') ?>
                </span>
                <?= $status ?>
            </span>
            <button onclick="bukaStruk('<?= $id_p ?>')"
                    class="no-print flex items-center gap-1 px-2.5 py-1 rounded-lg border border-stone-200 text-[10px] font-bold text-[var(--muted)] hover:bg-stone-50 transition">
                <span class="material-symbols-outlined" style="font-size:13px">receipt_long</span>
                Struk
            </button>
        </div>
    </div>

    <!-- Info pembeli -->
    <div class="px-5 py-2.5 flex flex-wrap gap-x-3 gap-y-1.5 bg-[var(--brand-lt)] border-b border-[var(--line)]">
        <span class="meta-chip text-xs font-bold">
            <span class="material-symbols-outlined text-[var(--brand)]" style="font-size:13px;font-variation-settings:'FILL' 1">person</span>
            <?= htmlspecialchars($p['username']) ?>
        </span>
        <span class="meta-chip text-xs text-[var(--muted)]">
            <span class="material-symbols-outlined text-[var(--brand)]" style="font-size:13px">schedule</span>
            <?= date('d M Y, H:i', strtotime($p['tanggal'])) ?>
        </span>
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

    <!-- Step tracker -->
    <div class="px-5 pt-3 pb-2 border-b border-[var(--line)]">
        <div class="stepbar gap-0">
            <?php
            $sdef  = ['Pending','Diproses','Siap Diambil'];
            $sicon = ['hourglass_empty','skillet','check_circle'];
            foreach ($sdef as $si => $sl):
                $cls = $si < $stepIdx ? 's-done' : ($si == $stepIdx ? 's-active' : 's-wait');
            ?>
            <div class="step-item <?= $cls ?>">
                <div class="step-circle">
                    <span class="material-symbols-outlined" style="font-size:13px;font-variation-settings:'FILL' 1">
                        <?= $si < $stepIdx ? 'check' : $sicon[$si] ?>
                    </span>
                </div>
                <span class="step-lbl"><?= $sl ?></span>
            </div>
            <?php if ($si < count($sdef)-1): ?>
            <div class="step-line <?= $si < $stepIdx ? 'sl-done' : 'sl-wait' ?>"></div>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Daftar item -->
    <div class="divide-y divide-[var(--line)]">
        <?php foreach ($drows as $d):
            $sat = (float)$d['harga'];
            $subtotal = (float)$d['subtotal'];
        ?>
        <div class="flex items-center gap-3 px-5 py-2.5 hover:bg-stone-50/70 transition">

            <div class="relative shrink-0">
                <img src="../../uploads/<?= htmlspecialchars($d['foto'] ?? '') ?>"
                     class="w-11 h-11 rounded-xl object-cover bg-stone-100"
                     onerror="this.src='../../assets/img/default-food.jpg'">
                <span class="absolute -top-1 -right-1 bg-[var(--brand)] text-white font-black rounded-full flex items-center justify-center"
                      style="width:1rem;height:1rem;font-size:.55rem;">
                    <?= $d['qty'] ?>
                </span>
            </div>

            <div class="flex-1 min-w-0">
                <p class="font-bold text-sm"><?= htmlspecialchars($d['nama_menu']) ?></p>
                <?php if ($sat > 0): ?>
                <p class="text-[11px] text-[var(--muted)]"><?= $d['qty'] ?> x Rp <?= number_format($sat, 0, ',', '.') ?></p>
                <?php else: ?>
                <p class="text-[11px] text-[var(--muted)]"><?= $d['qty'] ?> item</p>
                <?php endif; ?>
                <?php if (!empty($d['catatan'])): ?>
                <p class="text-[10px] text-amber-600 mt-0.5">
                    ✎ <?= htmlspecialchars($d['catatan']) ?>
                </p>
                <?php endif; ?>
            </div>

            <?php if ($subtotal > 0): ?>
            <div class="text-sm font-extrabold text-[var(--brand)] shrink-0 tabular-nums">
                Rp <?= number_format($subtotal, 0, ',', '.') ?>
            </div>
            <?php endif; ?>

        </div>
        <?php endforeach; ?>
    </div>

    <!-- Footer: total + tombol -->
    <div class="px-5 py-3.5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-stone-50/60 border-t border-[var(--line)]">
        <div>
            <p class="text-[9px] font-black uppercase tracking-widest text-[var(--muted)]">Total Pendapatan</p>
            <p class="text-xl font-extrabold text-[var(--brand)] tabular-nums">
                <?= $pendapatan > 0 ? 'Rp '.number_format($pendapatan, 0, ',', '.') : t('msg.no_orders') ?>
            </p>
            <p class="text-[11px] text-[var(--muted)]"><?= $jml ?> item</p>
        </div>

        <div class="no-print flex gap-2 flex-wrap justify-start sm:justify-end">
            <?php if ($status == 'Pending'): ?>
                <form action="update_status.php" method="POST">
                    <input type="hidden" name="id_pesanan" value="<?= $id_p ?>">
                    <input type="hidden" name="status_baru" value="Diproses">
                    <button type="submit" class="btn-aksi btn-proses">
                        <span class="material-symbols-outlined" style="font-size:15px">skillet</span>
                        <?= t('action.confirm') ?> &amp; <?= t('status.processing') ?>
                    </button>
                </form>
            <?php elseif ($status == 'Diproses'): ?>
                <form action="update_status.php" method="POST">
                    <input type="hidden" name="id_pesanan" value="<?= $id_p ?>">
                    <input type="hidden" name="status_baru" value="Siap Diambil">
                    <button type="submit" class="btn-aksi btn-siap">
                        <span class="material-symbols-outlined" style="font-size:15px">check_circle</span>
                        <?= t('tracking.ready') ?> — <?= t('action.confirm') ?>
                    </button>
                </form>
            <?php elseif ($status == 'Siap Diambil'): ?>
                <form action="update_status.php" method="POST"
                      onsubmit="return confirm('<?= t('action.confirm') ?> #' + '<?= htmlspecialchars($p['kode_pesanan']) ?>')">
                    <input type="hidden" name="id_pesanan" value="<?= $id_p ?>">
                    <input type="hidden" name="status_baru" value="Selesai">
                    <button type="submit" class="btn-aksi btn-selesai">
                        <span class="material-symbols-outlined" style="font-size:15px">task_alt</span>
                        <?= t('status.completed') ?> &amp; Berikan ke Pembeli
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /.order-card -->

<!-- DATA STRUK (tersembunyi) -->
<div id="struk-data-<?= $id_p ?>" class="hidden">
<div style="font-family:'Courier New',monospace;font-size:.72rem;line-height:1.7;color:#111;">

    <div style="text-align:center;margin-bottom:.6rem;">
        <p style="font-size:1rem;font-weight:900;letter-spacing:.06em;">★ <?= strtoupper(htmlspecialchars($p['nama_kantin'])) ?> ★</p>
        <p style="font-size:.65rem;color:#555;">Kantin Sekolah</p>
        <p style="font-size:.6rem;color:#888;"><?= date('d/m/Y H:i:s', strtotime($p['tanggal'])) ?></p>
    </div>

    <hr class="struk-dash">

    <table style="width:100%;border-collapse:collapse;font-size:.7rem;">
        <tr><td>No. Pesanan</td><td style="text-align:right;font-weight:900;">#<?= htmlspecialchars($p['kode_pesanan']) ?></td></tr>
        <tr><td>Pembeli</td><td style="text-align:right;font-weight:700;"><?= htmlspecialchars($p['username']) ?></td></tr>
        <?php if (!empty($p['metode_pembayaran'])): ?>
        <tr><td>Bayar via</td><td style="text-align:right;"><?= htmlspecialchars($p['metode_pembayaran']) ?></td></tr>
        <?php endif; ?>
        <?php if (!empty($p['catatan'])): ?>
        <tr><td colspan="2" style="color:#666;font-size:.65rem;">Catatan: <?= htmlspecialchars($p['catatan']) ?></td></tr>
        <?php endif; ?>
    </table>

    <hr class="struk-dash">
    <p style="font-weight:900;margin-bottom:.25rem;">DETAIL PESANAN</p>

    <?php foreach ($drows as $d):
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
        <tr><td>Jumlah Item</td><td style="text-align:right;"><?= $jml ?> item</td></tr>
        <tr><td>Subtotal</td><td style="text-align:right;"><?= $subtotal_items > 0 ? 'Rp '.number_format($subtotal_items,0,',','.') : '-' ?></td></tr>
        <tr><td>Biaya Admin (Pajak)</td><td style="text-align:right;"><?= $pajak > 0 ? 'Rp '.number_format($pajak,0,',','.') : '-' ?></td></tr>
        <tr>
            <td style="font-size:.85rem;font-weight:900;padding-top:.2rem;">TOTAL PENDAPATAN</td>
            <td style="text-align:right;font-size:.85rem;font-weight:900;"><?= $pendapatan > 0 ? 'Rp '.number_format($pendapatan,0,',','.') : '-' ?></td>
        </tr>
    </table>

    <hr class="struk-dash">
    <p style="text-align:center;font-size:.63rem;color:#666;margin-top:.35rem;">
        <?= t('seller.thanks') ?><br>🍱
    </p>

</div>
</div>

<?php endforeach; ?>

<?php else: ?>
<div class="bg-white border border-[var(--line)] rounded-2xl py-14 px-8 text-center max-w-3xl xl:col-span-2">
    <div class="w-14 h-14 bg-[var(--brand-lt)] rounded-xl flex items-center justify-center mx-auto mb-4">
        <span class="material-symbols-outlined text-[var(--brand)]" style="font-size:32px;font-variation-settings:'FILL' 1">receipt_long</span>
    </div>
    <h3 class="text-lg font-extrabold"><?= t('msg.no_orders') ?></h3>
    <p class="text-[var(--muted)] text-sm mt-1"><?= t('msg.no_orders_desc') ?></p>
    <button onclick="location.reload()"
            class="mt-5 px-5 py-2 bg-[var(--brand-lt)] text-[var(--brand)] rounded-xl text-sm font-bold hover:bg-[var(--brand-md)] transition">
        <?= t('action.refresh') ?>
    </button>
</div>
<?php endif; ?>

</div>
</main>

<!-- MODAL STRUK -->
<div id="overlay-struk" class="overlay no-print" onclick="if(event.target===this)tutupStruk()">
    <div class="modal-box">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-extrabold text-base flex items-center gap-1.5">
                <span class="material-symbols-outlined text-[var(--brand)]" style="font-size:17px;font-variation-settings:'FILL' 1">receipt_long</span>
                Preview <?= t('action.print') ?>
            </h3>
            <button onclick="tutupStruk()" class="w-7 h-7 rounded-lg bg-stone-100 hover:bg-stone-200 flex items-center justify-center transition">
                <span class="material-symbols-outlined" style="font-size:15px">close</span>
            </button>
        </div>
        <div id="modal-struk-isi" class="bg-stone-50 border border-stone-200 rounded-xl p-4 mb-4 max-h-80 overflow-y-auto"></div>
        <div class="flex gap-2">
            <button onclick="doCetak()" class="flex-1 btn-aksi btn-selesai justify-center text-sm py-2.5">
                <span class="material-symbols-outlined" style="font-size:15px">print</span>
                <?= t('action.print') ?>
            </button>
            <button onclick="tutupStruk()" class="px-4 py-2.5 border border-stone-200 rounded-xl text-sm font-bold text-[var(--muted)] hover:bg-stone-50 transition">
                <?= t('action.cancel') ?>
            </button>
        </div>
    </div>
</div>

<!-- AREA PRINT -->
<div id="print-area"></div>

<!-- TOAST -->
<?php if (isset($_GET['success'])): ?>
<div id="toast" class="toast no-print">
    <span class="material-symbols-outlined text-green-400" style="font-size:16px;font-variation-settings:'FILL' 1">check_circle</span>
    <?= t('msg.order_updated') ?>
</div>
<script>
    setTimeout(()=>{ const t=document.getElementById('toast'); if(t){t.style.transition='opacity .4s';t.style.opacity='0';setTimeout(()=>t.remove(),400);} }, 3500);
</script>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
<div id="toast" class="toast no-print" style="background:#b91c1c;">
    <span class="material-symbols-outlined" style="font-size:16px">error</span>
    <?= t('msg.error') ?>: <?= htmlspecialchars($_GET['error']) ?>
</div>
<script>
    setTimeout(()=>{ const t=document.getElementById('toast'); if(t){t.style.transition='opacity .4s';t.style.opacity='0';setTimeout(()=>t.remove(),400);} }, 4000);
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
