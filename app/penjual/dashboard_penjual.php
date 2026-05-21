<?php
// ================================
// SESSION + CONFIG
// ================================
if (session_status() === PHP_SESSION_NONE) session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php"); exit();
}

$id_user  = (int)$_SESSION['id_user'];
$q_user   = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = $id_user");
$user_data = mysqli_fetch_assoc($q_user);

if (!$user_data || $user_data['role'] !== 'penjual' || empty($user_data['id_kantin'])) {
    header("Location: ../auth/login.php?error=penjual_saja"); exit();
}


$id_kantin = (int)$user_data['id_kantin'];
$_SESSION['id_kantin']    = $id_kantin;
$_SESSION['nama_penjual'] = $user_data['username'];


// ================================
// DATA KANTIN
// ================================
$q_kantin   = mysqli_query($koneksi, "
    SELECT k.*, u.username as nama_penjual
    FROM kantin k LEFT JOIN users u ON k.id_user = u.id_user
    WHERE k.id_kantin = $id_kantin LIMIT 1
");
$data_kantin = mysqli_fetch_assoc($q_kantin);
if (!$data_kantin) die("❌ Kantin tidak ditemukan");

$nama_kantin  = $data_kantin['nama_kantin'] ?? 'Kantin Saya';
$deskripsi    = $data_kantin['deskripsi']   ?? '';
$rating       = (float)($data_kantin['rating']       ?? 0);
$total_ulasan = (int)($data_kantin['total_ulasan']   ?? 0);
$jam_buka     = $data_kantin['jam_buka']  ?? '07:00:00';
$jam_tutup    = $data_kantin['jam_tutup'] ?? '15:00:00';
$status_buka  = $data_kantin['status_buka'] ?? 'Buka';

$banner = (!empty($data_kantin['banner']) && file_exists("../../uploads/" . $data_kantin['banner']))
    ? '../../uploads/' . $data_kantin['banner']
    : '../../uploads/default/default-banner.svg';

$logo = (!empty($data_kantin['logo']) && file_exists("../../uploads/" . $data_kantin['logo']))
    ? '../../uploads/' . $data_kantin['logo']
    : '../../uploads/default/default-logo.svg';

date_default_timezone_set('Asia/Jakarta');
$jam_sekarang = date('H:i:s');
$kantin_buka  = kk_is_kantin_open($data_kantin);

// ================================
// STATISTIK
// ================================
$tgl = date('Y-m-d');

$pesanan_hari_ini    = (int)(mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT COUNT(*) as t FROM pesanan WHERE id_kantin=$id_kantin AND DATE(tanggal)='$tgl'"))['t'] ?? 0);

$pendapatan_hari_ini = (float)(mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT COALESCE(SUM(total_harga - COALESCE(pajak,0)),0) as t FROM pesanan
     WHERE id_kantin=$id_kantin AND DATE(tanggal)='$tgl' AND status IN('Selesai','Siap Diambil')"))['t'] ?? 0);

$menu_aktif = (int)(mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT COUNT(*) as t FROM menu WHERE id_kantin=$id_kantin AND status='Tersedia' AND COALESCE(stok,0) > 0"))['t'] ?? 0);

$total_pesanan = (int)(mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT COUNT(*) as t FROM pesanan WHERE id_kantin=$id_kantin
     AND status IN('Pending','Diproses')"))['t'] ?? 0);

// ================================
// PESANAN MASUK (5 TERBARU, STATUS PENDING/DIPROSES)
// Fetch pesanan dulu, lalu detail per pesanan secara terpisah
// ================================
$pesanan_masuk = [];

$q_p = mysqli_query($koneksi, "
    SELECT p.id_pesanan, p.kode_pesanan, p.nomor_antrean,
           p.total_harga, p.status, p.tanggal,
           p.metode_pembayaran, p.catatan,
           u.username, u.email
    FROM pesanan p
    LEFT JOIN users u ON p.id_user = u.id_user
    WHERE p.id_kantin = $id_kantin
    AND   p.status IN ('Pending','Diproses')
    ORDER BY p.tanggal DESC
    LIMIT 5
");

if ($q_p && mysqli_num_rows($q_p) > 0) {
    while ($row = mysqli_fetch_assoc($q_p)) {
        $id_p = (int)$row['id_pesanan'];

      $q_d = mysqli_query($koneksi, "
    SELECT 
        m.nama_menu,
        m.foto,
        m.harga,
        dp.qty,
        (m.harga * dp.qty) as subtotal
    FROM detail_pesanan dp
    LEFT JOIN menu m ON dp.id_menu = m.id_menu
    WHERE dp.id_pesanan = $id_p
    ORDER BY dp.id_detail ASC
");

        $row['items'] = [];
        if ($q_d && mysqli_num_rows($q_d) > 0) {
            while ($d = mysqli_fetch_assoc($q_d)) {
                $row['items'][] = $d;
            }
        }

        $pesanan_masuk[] = $row;
    }
}

// ================================
// MENU SAYA (5 TERBARU)
// ================================
$menu_list = [];
$q_menu = mysqli_query($koneksi, "
    SELECT id_menu, nama_menu, harga, foto, status, stok, kategori, deskripsi
    FROM menu WHERE id_kantin = $id_kantin
    ORDER BY id_menu DESC LIMIT 5
");
if ($q_menu) while ($r = mysqli_fetch_assoc($q_menu)) $menu_list[] = $r;

// ================================
// CHART 7 HARI
// ================================
$chart_labels = $chart_data = [];
$q_chart = mysqli_query($koneksi, "
    SELECT DATE(tanggal) as tgl, COALESCE(SUM(total_harga - COALESCE(pajak,0)),0) as total
    FROM pesanan
    WHERE id_kantin = $id_kantin AND status IN('Selesai','Siap Diambil')
    AND   DATE(tanggal) BETWEEN DATE_SUB(NOW(), INTERVAL 6 DAY) AND CURDATE()
    GROUP BY DATE(tanggal) ORDER BY DATE(tanggal) ASC
");
if ($q_chart && mysqli_num_rows($q_chart) > 0) {
    while ($r = mysqli_fetch_assoc($q_chart)) {
        $chart_labels[] = date('d M', strtotime($r['tgl']));
        $chart_data[]   = (float)$r['total'];
    }
}
if (empty($chart_labels)) {
    for ($i = 6; $i >= 0; $i--) {
        $chart_labels[] = date('d M', strtotime("-$i days"));
        $chart_data[]   = 0;
    }
}

// Encode untuk JS (modal struk)
$pesanan_json = json_encode($pesanan_masuk, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= t('seller.dashboard_title') ?> — <?= htmlspecialchars($nama_kantin) ?></title>

<!-- Tailwind + Chart.js + FontAwesome -->
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<script>
tailwind.config = { theme: { extend: { colors: { primary: '#f97316' } } } }
</script>

<style>
* { font-family: 'Plus Jakarta Sans', sans-serif; }
.stat-card{background:white;border-radius:1.5rem;padding:1.5rem;border:1.5px solid #f3f4f6;transition:all .25s cubic-bezier(0.4,0,0.2,1);position:relative;overflow:hidden}
.stat-card::before{content:'';position:absolute;top:0;right:0;width:120px;height:120px;border-radius:50%;opacity:.06;transform:translate(30%,-30%);transition:all .3s}
.stat-card:hover::before{transform:translate(20%,-20%) scale(1.1)}
.stat-card:hover{transform:translateY(-4px);box-shadow:0 20px 40px rgba(178,34,4,.12);border-color:#fed7aa}
.stat-card.blue::before{background:#3b82f6}
.stat-card.green::before{background:#10b981}
.stat-card.orange::before{background:#f97316}
.stat-card.purple::before{background:#8b5cf6}
.pesanan-card{cursor:pointer;transition:all .22s cubic-bezier(.4,0,.2,1);border:1.5px solid #f3f4f6;border-radius:1.2rem;overflow:hidden;background:white}
.pesanan-card:hover{transform:translateY(-3px);box-shadow:0 16px 32px rgba(249,115,22,.13);border-color:#fb923c}
.badge-pending{background:linear-gradient(135deg,#fef3c7,#fde68a);color:#92400e;border:1px solid #fcd34d}
.badge-diproses{background:linear-gradient(135deg,#dbeafe,#bfdbfe);color:#1e40af;border:1px solid #93c5fd}
#modalStruk{display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,15,15,.65);backdrop-filter:blur(6px);align-items:center;justify-content:center;padding:1rem;animation:fadeIn .2s}
#modalStruk.active{display:flex}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
#strutBox{animation:slideUp .25s cubic-bezier(.34,1.56,.64,1)}
@keyframes slideUp{from{opacity:0;transform:translateY(40px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}
.struk-font{font-family:'Courier New','Courier',monospace}
.struk-dash{border:none;border-top:2px dashed #d1d5db;margin:12px 0}
@media print{body>*:not(#modalStruk){display:none!important}#modalStruk{display:flex!important;position:static;background:white;backdrop-filter:none}#btnTutup,#btnCetak,#btnTutup2{display:none!important}#strutBox{box-shadow:none!important;max-width:320px;border:1px solid #eee}}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
.float-anim{animation:float 3s ease-in-out infinite}
.glow-brand{box-shadow:0 0 30px rgba(178,34,4,.3)}
.chart-container{background:white;border-radius:1.5rem;border:1.5px solid #f3f4f6;padding:1.5rem;position:relative}
</style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-orange-50 min-h-screen">

<!-- SIDEBAR -->
<?php include(__DIR__ . '/../../includes/sidebar_penjual.php'); ?>

<!-- Tombol mobile -->
<button id="sidebarToggle"
        class="lg:hidden fixed top-4 left-4 z-40 p-2.5 bg-white rounded-2xl shadow-lg text-gray-700">
    <i class="fa-solid fa-bars text-lg"></i>
</button>

<!-- ==================================
     MAIN CONTENT
================================== -->
<main class="lg:ml-64 p-6 pb-16 transition-all">

    <!-- HERO BANNER -->
<div class="relative rounded-3xl overflow-hidden mb-8 shadow-2xl h-72 lg:h-80">

    <!-- Banner -->
    <img 
        src="<?= htmlspecialchars($banner) ?>"
        class="w-full h-full object-cover"
        alt="Banner"
        onerror="this.onerror=null;this.src='../../uploads/default/default-banner.svg';"
    >

    <!-- Overlay -->
    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/25 to-transparent"></div>

    <!-- Content -->
    <div class="absolute bottom-6 left-6 right-6">
        <div class="flex flex-col lg:flex-row lg:items-end gap-5">

            <!-- Logo -->
            <img 
                src="<?= htmlspecialchars($logo) ?>"
                class="w-20 h-20 lg:w-28 lg:h-28 rounded-2xl border-4 border-white/80 shadow-2xl object-cover bg-white"
                alt="Logo"
                onerror="this.onerror=null;this.src='../../uploads/default/default-logo.svg';"
            >

            <!-- Info Kantin -->
            <div class="flex-1">
                <p class="text-white/70 text-xs mb-1 uppercase tracking-widest">
                    <?= t('seller.dashboard_title') ?>
                </p>

                <h1 class="text-2xl lg:text-4xl font-black text-white mb-2">
                    <?= htmlspecialchars($nama_kantin) ?>
                </h1>

                <p class="text-white/80 text-sm mb-4 line-clamp-1">
                    <?= htmlspecialchars($deskripsi) ?>
                </p>

                <!-- Badge -->
                <div class="flex flex-wrap gap-2">

                    <!-- Jam -->
                    <span class="flex items-center gap-1.5 bg-white/20 backdrop-blur-sm text-white text-xs font-semibold px-3 py-2 rounded-xl">
                        <i class="fa-solid fa-clock text-orange-300 text-xs"></i>
                        <?= date('H:i', strtotime($jam_buka)) ?> – <?= date('H:i', strtotime($jam_tutup)) ?>
                    </span>

                    <!-- Rating -->
                    <span class="flex items-center gap-1.5 bg-white/20 backdrop-blur-sm text-white text-xs font-semibold px-3 py-2 rounded-xl">
                        <i class="fa-solid fa-star text-yellow-300 text-xs"></i>
                        <?= number_format($rating,1) ?> (<?= $total_ulasan ?>)
                    </span>

                    <!-- Status -->
                    <span class="flex items-center gap-1.5 backdrop-blur-sm text-white text-xs font-semibold px-3 py-2 rounded-xl
                        <?= $kantin_buka ? 'bg-green-500/70' : 'bg-red-500/70' ?>">

                        <span class="w-2 h-2 rounded-full bg-white <?= $kantin_buka ? 'animate-pulse' : '' ?>"></span>

                        <?= $kantin_buka ? t('dashboard.open') : t('dashboard.closed') ?>
                    </span>

                </div>
            </div>
        </div>
    </div>
</div>

    <!-- STATISTIK CARDS -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <?php
        $stats = [
            ['label'=> t('seller.orders_today'), 'value'=>number_format($pesanan_hari_ini),
             'icon'=>'fa-bag-shopping', 'from'=>'from-blue-500','to'=>'to-blue-600','shadow'=>'shadow-blue-200'],
            ['label'=> t('seller.revenue_today'), 'value'=>'Rp '.number_format($pendapatan_hari_ini,0,',','.'),
             'icon'=>'fa-coins', 'from'=>'from-emerald-500','to'=>'to-emerald-600','shadow'=>'shadow-emerald-200'],
            ['label'=> t('seller.menu_available'), 'value'=>number_format($menu_aktif),
             'icon'=>'fa-utensils', 'from'=>'from-orange-500','to'=>'to-orange-600','shadow'=>'shadow-orange-200'],
            ['label'=> t('seller.total_orders'), 'value'=>number_format($total_pesanan),
             'icon'=>'fa-list-check', 'from'=>'from-purple-500','to'=>'to-purple-600','shadow'=>'shadow-purple-200'],
        ];
        foreach ($stats as $s): ?>
        <div class="group bg-white rounded-2xl lg:rounded-3xl p-5 lg:p-7 shadow-lg hover:shadow-xl transition-all border border-gray-100 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 rounded-full bg-gradient-to-br from-blue-100/50 to-transparent -mr-8 -mt-8 opacity-0 group-hover:opacity-100 transition-opacity"></div>
            <div class="flex items-start justify-between gap-3 relative z-10">
                <div class="flex-1 min-w-0">
                    <p class="text-gray-500 text-xs font-semibold mb-2 leading-tight"><?= $s['label'] ?></p>
                    <p class="text-2xl lg:text-4xl font-black text-gray-900 leading-none"><?= $s['value'] ?></p>
                </div>
                <div class="w-12 h-12 lg:w-14 lg:h-14 flex-shrink-0 bg-gradient-to-br <?= $s['from'] ?> <?= $s['to'] ?>
                            rounded-xl lg:rounded-2xl flex items-center justify-center text-white text-lg shadow-lg <?= $s['shadow'] ?>
                            group-hover:scale-110 transition-transform duration-300">
                    <i class="fa-solid <?= $s['icon'] ?>"></i>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- PESANAN MASUK + MENU SAYA -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

        <!-- ===== PESANAN MASUK ===== -->
        <div class="bg-white rounded-3xl p-6 shadow-lg border border-gray-100">

            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-orange-100 flex items-center justify-center">
                        <i class="fa-solid fa-bell text-orange-500"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-gray-900"><?= t('seller.incoming_orders') ?></h2>
                        <p class="text-xs text-gray-400 mt-0.5">
                            <?= count($pesanan_masuk) ?> <?= t('seller.active') ?> ·
                            <span class="text-orange-500 font-semibold"><?= t('seller.click_receipt') ?></span>
                        </p>
                    </div>
                </div>
                <a href="pesanan_masuk.php"
                   class="text-sm text-orange-500 font-bold hover:text-orange-600 flex items-center gap-1.5">
                    Semua <i class="fa-solid fa-arrow-right text-xs"></i>
                </a>
            </div>

            <?php if (empty($pesanan_masuk)): ?>
            <!-- Empty -->
            <div class="text-center py-16">
                <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-orange-50 flex items-center justify-center">
                    <i class="fa-solid fa-inbox text-3xl text-orange-300"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-600 mb-1"><?= t('seller.no_orders') ?></h3>
                <p class="text-sm text-gray-400"><?= t('seller.no_orders_desc') ?></p>
            </div>

            <?php else: ?>
            <div class="space-y-3">
            <?php foreach ($pesanan_masuk as $idx => $pesan):
                $sc = match($pesan['status']) {
                    'Pending'  => ['bg'=>'bg-amber-100',  'text'=>'text-amber-700',  'dot'=>'bg-amber-500'],
                    'Diproses' => ['bg'=>'bg-blue-100',   'text'=>'text-blue-700',   'dot'=>'bg-blue-500'],
                    default    => ['bg'=>'bg-gray-100',   'text'=>'text-gray-600',   'dot'=>'bg-gray-400'],
                };
                $total_item = array_sum(array_column($pesan['items'], 'qty'));
            ?>
            <!-- Kartu — onclick buka struk -->
            <div class="pesanan-card rounded-2xl p-4 bg-white select-none"
                 onclick="bukaStruk(<?= $idx ?>)">

                <!-- Baris atas: badge + info user + harga -->
                <div class="flex items-center gap-3">
                    <!-- Badge order -->
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-orange-400 to-orange-600
                                flex flex-col items-center justify-center text-white shadow-md shadow-orange-200 flex-shrink-0">
                        <span class="text-[9px] font-bold opacity-75 leading-none">ORDER</span>
                        <span class="font-black text-sm leading-tight">#<?= str_pad($pesan['id_pesanan'],4,'0',STR_PAD_LEFT) ?></span>
                    </div>

                    <!-- Info user -->
                    <div class="flex-1 min-w-0">
                        <h3 class="font-black text-gray-900 truncate"><?= htmlspecialchars($pesan['username'] ?? t('general.anonymous')) ?></h3>
                        <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($pesan['email'] ?? '') ?></p>
                        <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold
                                         <?= $sc['bg'] ?> <?= $sc['text'] ?>">
                                <span class="w-1.5 h-1.5 rounded-full <?= $sc['dot'] ?>"></span>
                                <?= $pesan['status'] ?>
                            </span>
                            <?php if ($pesan['metode_pembayaran']): ?>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-green-100 text-green-700">
                                <i class="fa-solid fa-wallet text-[9px]"></i>
                                <?= htmlspecialchars($pesan['metode_pembayaran']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Harga + waktu -->
                    <div class="text-right flex-shrink-0">
                        <p class="font-black text-gray-900 text-lg">Rp <?= number_format($pesan['total_harga'],0,',','.') ?></p>
                        <p class="text-[11px] text-gray-400 mt-0.5"><?= date('d M, H:i', strtotime($pesan['tanggal'])) ?></p>
                        <p class="text-[11px] text-orange-500 font-semibold mt-1"><?= $total_item ?> <?= t('general.item') ?></p>
                    </div>
                </div>

                <!-- Baris bawah: daftar menu yang dipesan -->
                <?php if (!empty($pesan['items'])): ?>
                <div class="mt-3 pt-3 border-t border-dashed border-gray-200">
                    <div class="flex flex-wrap gap-1.5">
                        <?php foreach ($pesan['items'] as $item): ?>
                        <span class="flex items-center gap-1 bg-orange-50 border border-orange-100
                                     text-orange-800 text-xs font-semibold px-2.5 py-1 rounded-lg">
                            <i class="fa-solid fa-bowl-food text-orange-400 text-[10px]"></i>
                            <?= htmlspecialchars($item['nama_menu']) ?>
                            <span class="bg-orange-500 text-white text-[10px] font-black px-1.5 py-0.5 rounded-md ml-0.5">×<?= $item['qty'] ?></span>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($pesan['catatan']): ?>
                    <p class="mt-2 text-xs text-amber-700 bg-amber-50 rounded-lg px-3 py-1.5 flex items-center gap-1.5">
                        <i class="fa-solid fa-note-sticky text-amber-500 text-[10px]"></i>
                        <?= htmlspecialchars($pesan['catatan']) ?>
                    </p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Hint klik -->
                <p class="text-[10px] text-gray-300 text-right mt-2 flex items-center justify-end gap-1">
                    <i class="fa-solid fa-receipt text-[9px]"></i> <?= t('msg.click_receipt') ?>
                </p>
            </div>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- ===== MENU SAYA ===== -->
        <div class="bg-white rounded-3xl p-6 shadow-lg border border-gray-100">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-orange-100 flex items-center justify-center">
                        <i class="fa-solid fa-fire text-orange-500"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-gray-900"><?= t('seller.my_menu') ?></h2>
                        <p class="text-xs text-gray-400 mt-0.5"><?= $menu_aktif ?> <?= t('seller.available') ?></p>
                    </div>
                </div>
                <a href="kelola_menu_penjual.php"
                   class="text-sm text-orange-500 font-bold hover:text-orange-600 flex items-center gap-1.5">
                    <?= t('action.manage') ?> <i class="fa-solid fa-pen-to-square text-xs"></i>
                </a>
            </div>
            <?php if (empty($menu_list)): ?>
            <div class="text-center py-16 text-gray-400">
                <i class="fa-solid fa-bowl-food text-4xl mb-3 opacity-40"></i>
                <p class="font-semibold">Belum ada menu</p>
            </div>
            <?php else: ?>
            <div class="space-y-3">
            <?php foreach ($menu_list as $m):
                $fp = '../../uploads/' . ($m['foto'] ?? '');
                $ada_foto = !empty($m['foto']) && file_exists($fp);
            ?>
            <div class="flex items-center gap-3 p-3 rounded-2xl hover:bg-orange-50 transition-all group border border-transparent hover:border-orange-100">
                <!-- Foto menu -->
                <div class="w-14 h-14 rounded-xl overflow-hidden flex-shrink-0 bg-gray-100">
                    <?php if ($ada_foto): ?>
                    <img src="<?= htmlspecialchars($fp) ?>" class="w-full h-full object-cover" alt="">
                    <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center text-gray-300">
                        <i class="fa-solid fa-image text-xl"></i>
                    </div>
                    <?php endif; ?>
                </div>
                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <h4 class="font-bold text-gray-900 text-sm truncate"><?= htmlspecialchars($m['nama_menu']) ?></h4>
                    <p class="text-xs text-gray-400"><?= htmlspecialchars($m['kategori'] ?? 'Umum') ?></p>
                    <p class="text-xs text-gray-500 mt-0.5 line-clamp-1"><?= htmlspecialchars(substr($m['deskripsi'] ?? '', 0, 50)) ?></p>
                </div>
                <!-- Harga + status -->
                <div class="text-right flex-shrink-0">
                    <p class="font-black text-gray-900">Rp <?= number_format($m['harga'],0,',','.') ?></p>
                    <p class="text-xs text-gray-400 mt-0.5"><?= t('seller.stok') ?>: <?= $m['stok'] ?? 0 ?></p>
                    <span class="inline-block mt-1 px-2 py-0.5 rounded-lg text-[11px] font-bold
                                 <?= $m['status']==='Tersedia' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                        <?= $m['status'] ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- CHART PENJUALAN -->
    <div class="bg-white rounded-3xl p-6 shadow-lg border border-gray-100">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl bg-orange-100 flex items-center justify-center">
                    <i class="fa-solid fa-chart-line text-orange-500"></i>
                </div>
                <div>
                    <h2 class="text-xl font-black text-gray-900"><?= t('seller.sales_chart') ?></h2>
                    <p class="text-xs text-gray-400 mt-0.5"><?= t('seller.last_7_days') ?></p>
                </div>
            </div>
        </div>
        <div class="relative h-64 lg:h-80">
            <canvas id="salesChart"></canvas>
        </div>
    </div>
</main>

<!-- ============================================================
     MODAL STRUK — tampil saat kartu pesanan diklik
============================================================ -->
<div id="modalStruk">
<div id="strutBox" class="bg-white rounded-3xl shadow-2xl w-full max-w-[340px] mx-auto overflow-hidden">

    <!-- Header struk bergaya kasir -->
    <div class="bg-gradient-to-br from-orange-500 to-orange-700 px-6 py-5 text-white relative">
        <!-- Tombol tutup -->
        <button id="btnTutup" onclick="tutupStruk()"
                class="absolute top-3 right-3 w-8 h-8 rounded-full bg-white/20 hover:bg-white/30
                       flex items-center justify-center transition text-sm">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <!-- Nama toko -->
        <div class="text-center">
            <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-2">
                <i class="fa-solid fa-store text-xl"></i>
            </div>
            <p class="font-black text-lg leading-tight"><?= htmlspecialchars($nama_kantin) ?></p>
            <p class="text-orange-200 text-xs mt-0.5"><?= htmlspecialchars($deskripsi ?: 'Kantin Sekolah') ?></p>
        </div>
    </div>

    <!-- Lubang struk (dekorasi) -->
    <div class="flex">
        <div class="w-5 h-5 rounded-full bg-gray-100 -ml-2.5 flex-shrink-0 shadow-inner"></div>
        <div class="flex-1 border-t-2 border-dashed border-gray-200 self-center"></div>
        <div class="w-5 h-5 rounded-full bg-gray-100 -mr-2.5 flex-shrink-0 shadow-inner"></div>
    </div>

    <!-- Body struk -->
    <div class="px-5 py-4 struk-font">

        <!-- Info pesanan -->
        <div class="text-center mb-3">
            <p class="text-[10px] text-gray-400 uppercase tracking-widest">Nomor Pesanan</p>
            <p class="text-2xl font-black text-gray-900" id="s_id">-</p>
            <p class="text-xs text-gray-500" id="s_kode">-</p>
        </div>

        <table class="w-full text-xs mb-1">
            <tr>
                <td class="text-gray-500 py-0.5 w-28">No. Antrian</td>
                <td class="text-right font-bold text-gray-900" id="s_antrian">-</td>
            </tr>
            <tr>
                <td class="text-gray-500 py-0.5">Pelanggan</td>
                <td class="text-right font-bold text-gray-900" id="s_nama">-</td>
            </tr>
            <tr>
                <td class="text-gray-500 py-0.5">Waktu</td>
                <td class="text-right font-bold text-gray-900" id="s_waktu">-</td>
            </tr>
            <tr>
                <td class="text-gray-500 py-0.5">Status</td>
                <td class="text-right" id="s_status_wrap">-</td>
            </tr>
            <tr>
                <td class="text-gray-500 py-0.5">Pembayaran</td>
                <td class="text-right font-bold text-gray-900" id="s_bayar">-</td>
            </tr>
        </table>

        <hr class="struk-dash">

        <!-- Header kolom item -->
        <div class="flex text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-2 px-0.5">
            <span class="flex-1">Item</span>
            <span class="w-8 text-center">Qty</span>
            <span class="w-20 text-right">Subtotal</span>
        </div>

        <!-- Item detail -->
        <div id="s_items" class="space-y-2 mb-1 min-h-[30px]"></div>

        <hr class="struk-dash">

        <!-- Total -->
        <div class="flex items-center justify-between py-1">
            <span class="font-black text-sm text-gray-900">TOTAL BAYAR</span>
            <span class="font-black text-lg text-orange-600" id="s_total">-</span>
        </div>

        <!-- Catatan -->
        <div id="s_catatan_wrap" class="hidden mt-3 bg-amber-50 border border-amber-200 rounded-xl p-3">
            <p class="text-[10px] font-bold text-amber-600 uppercase tracking-wide mb-1">
                <i class="fa-solid fa-note-sticky mr-1"></i>Catatan dari Pembeli
            </p>
            <p class="text-xs text-amber-800" id="s_catatan">-</p>
        </div>

        <hr class="struk-dash">

        <!-- Footer -->
        <div class="text-center">
            <p class="text-xs text-gray-500 font-semibold"><?= t('seller.thanks') ?> 🙏</p>
            <p class="text-[10px] text-gray-400 mt-0.5"><?= t('seller.receipt_note') ?></p>
        </div>
    </div>

    <!-- Lubang bawah -->
    <div class="flex">
        <div class="w-5 h-5 rounded-full bg-gray-100 -ml-2.5 flex-shrink-0 shadow-inner"></div>
        <div class="flex-1 border-t-2 border-dashed border-gray-200 self-center"></div>
        <div class="w-5 h-5 rounded-full bg-gray-100 -mr-2.5 flex-shrink-0 shadow-inner"></div>
    </div>

    <!-- Tombol aksi -->
    <div class="flex gap-3 px-5 py-4">
        <button id="btnCetak" onclick="cetakStruk()"
                class="flex-1 flex items-center justify-center gap-2
                       bg-orange-500 hover:bg-orange-600 active:scale-95
                       text-white text-sm font-bold py-3 rounded-2xl transition-all shadow-lg shadow-orange-200">
            <i class="fa-solid fa-print"></i> <?= t('action.print') ?>
        </button>
        <button id="btnTutup2" onclick="tutupStruk()"
                class="flex items-center justify-center gap-2 px-5
                       bg-gray-100 hover:bg-gray-200 active:scale-95
                       text-gray-600 text-sm font-bold py-3 rounded-2xl transition-all">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
</div>
</div><!-- /modalStruk -->

<!-- ============================================================
     SCRIPTS
============================================================ -->
<script>
// ===== DATA DARI PHP =====
const dataPesanan = <?= $pesanan_json ?>;

function rp(n) {
    return 'Rp ' + parseInt(n || 0).toLocaleString('id-ID');
}
function fmtTgl(str) {
    if (!str) return '-';
    const d = new Date(str.replace(' ', 'T'));
    return d.toLocaleString('id-ID', {
        weekday:'short', day:'2-digit', month:'short',
        year:'numeric', hour:'2-digit', minute:'2-digit'
    });
}

// ===== BUKA MODAL STRUK =====
function bukaStruk(idx) {
    const p = dataPesanan[idx];
    if (!p) return;

    // Isi info
    document.getElementById('s_id').textContent      = '#' + String(p.id_pesanan).padStart(4,'0');
    document.getElementById('s_kode').textContent    = p.kode_pesanan ? 'Kode: ' + p.kode_pesanan : '';
    document.getElementById('s_antrian').textContent = p.nomor_antrean || '-';
    document.getElementById('s_nama').textContent    = p.username || 'Anonim';
    document.getElementById('s_waktu').textContent   = fmtTgl(p.tanggal);
    document.getElementById('s_bayar').textContent   = p.metode_pembayaran || '-';

    // Badge status
    const scMap = {
        'Pending' : 'bg-amber-100 text-amber-700',
        'Diproses': 'bg-blue-100 text-blue-700',
        'Selesai' : 'bg-green-100 text-green-700',
    };
    document.getElementById('s_status_wrap').innerHTML =
        `<span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold
         ${scMap[p.status] || 'bg-gray-100 text-gray-600'}">${p.status || '-'}</span>`;

    // Catatan
    const cw = document.getElementById('s_catatan_wrap');
    if (p.catatan) {
        document.getElementById('s_catatan').textContent = p.catatan;
        cw.classList.remove('hidden');
    } else { cw.classList.add('hidden'); }

    // Item detail
    const el = document.getElementById('s_items');
    el.innerHTML = '';
    const items = p.items || [];
    if (items.length > 0) {
        items.forEach(d => {
            const sub = parseInt(d.subtotal) || (parseInt(d.harga) * parseInt(d.qty));
            const row = document.createElement('div');
            row.className = 'flex items-center gap-1 text-xs';
            row.innerHTML = `
                <span class="flex-1 text-gray-800 font-semibold leading-tight">${d.nama_menu}</span>
                <span class="w-8 text-center text-gray-500">×${d.qty}</span>
                <span class="w-20 text-right font-bold text-gray-900">${rp(sub)}</span>
            `;
            el.appendChild(row);
        });
    } else {
        el.innerHTML = '<p class="text-center text-gray-300 text-xs py-2">— detail tidak tersedia —</p>';
    }

    // Total
    document.getElementById('s_total').textContent = rp(p.total_harga);

    // Tampilkan modal
    document.getElementById('modalStruk').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function tutupStruk() {
    document.getElementById('modalStruk').classList.remove('active');
    document.body.style.overflow = '';
}
function cetakStruk() { window.print(); }

// Klik backdrop
document.getElementById('modalStruk').addEventListener('click', function(e) {
    if (e.target === this) tutupStruk();
});

// Sidebar mobile
const toggle    = document.getElementById('sidebarToggle');
const sidebar   = document.getElementById('sidebar');
const overlay   = document.getElementById('sidebarOverlay');
toggle?.addEventListener('click', () => {
    sidebar?.classList.toggle('-translate-x-full');
    overlay?.classList.toggle('hidden');
});

// ===== CHART =====
document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('salesChart')?.getContext('2d');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_labels, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                label: 'Pendapatan',
                data: <?= json_encode($chart_data) ?>,
                borderColor: '#f97316',
                backgroundColor: (ctx) => {
                    const g = ctx.chart.ctx.createLinearGradient(0,0,0,300);
                    g.addColorStop(0,'rgba(249,115,22,.25)');
                    g.addColorStop(1,'rgba(249,115,22,.02)');
                    return g;
                },
                borderWidth: 3,
                fill: true,
                tension: 0.45,
                pointBackgroundColor: '#f97316',
                pointBorderColor: '#fff',
                pointBorderWidth: 2.5,
                pointRadius: 6,
                pointHoverRadius: 9,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,.04)' },
                    ticks: { callback: v => 'Rp ' + new Intl.NumberFormat('id-ID').format(v), font: { size: 11 } }
                },
                x: { grid: { display: false }, ticks: { font: { size: 11 } } }
            },
            interaction: { intersect: false, mode: 'index' }
        }
    });
});
</script>
</body>
</html>
