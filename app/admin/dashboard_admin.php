<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/language_helper.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');
kk_ensure_buyer_schema($koneksi);

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../auth/login.php"); exit();
}

// ============================
// PROSES TAMBAH KANTIN BARU
// ============================
$message = '';
$message_type = 'success';

if (isset($_POST['tambah_kantin'])) {
    $nama_kantin = trim($_POST['nama_kantin'] ?? '');
    $deskripsi   = trim($_POST['deskripsi']   ?? '');

    if ($nama_kantin === '') {
        $message = t('admin.name_required');
        $message_type = 'error';
    } else {
        $safe_nama = mysqli_real_escape_string($koneksi, $nama_kantin);
        $safe_desk = mysqli_real_escape_string($koneksi, $deskripsi);
        $cek = mysqli_query($koneksi, "SELECT id_kantin FROM kantin WHERE nama_kantin='$safe_nama' LIMIT 1");
        if (mysqli_num_rows($cek) > 0) {
            $message = t('admin.name_exists');
            $message_type = 'error';
        } else {
            $ins = mysqli_query($koneksi, "INSERT INTO kantin (nama_kantin, id_user, deskripsi) VALUES ('$safe_nama', 0, '$safe_desk')");
            if ($ins) {
                $message = "Kantin \"" . htmlspecialchars($nama_kantin) . "\" " . t('admin.kantin_added');
                $message_type = 'success';
                $_POST = [];
            } else {
                $message = t('admin.add_failed') . mysqli_error($koneksi);
                $message_type = 'error';
            }
        }
    }
}

// Statistik
$total_user       = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role='pembeli'"))['total'] ?? 0;
$total_penjual    = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE role='penjual'"))['total'] ?? 0;
$pesanan_hari_ini = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pesanan WHERE DATE(tanggal) = CURDATE()"))['total'] ?? 0;
$total_pendapatan = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(total_harga) as total FROM pesanan WHERE status='Selesai'"))['total'] ?? 0;
$total_biaya_admin = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COALESCE(SUM(pajak),0) as total FROM pesanan WHERE status='Selesai'"))['total'] ?? 0;

$target_bulanan = 10000000;
$persen_target  = ($total_pendapatan > 0) ? min(($total_pendapatan / $target_bulanan) * 100, 100) : 0;

// Grafik 7 hari
$grafik_data = [];
$days_label  = ['Mon'=>'SEN','Tue'=>'SEL','Wed'=>'RAB','Thu'=>'KAM','Fri'=>'JUM','Sat'=>'SAB','Sun'=>'MIN'];
for ($i = 6; $i >= 0; $i--) {
    $date   = date('Y-m-d', strtotime("-$i days"));
    $day_en = date('D', strtotime($date));
    $res_g  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(total_harga) as total FROM pesanan WHERE DATE(tanggal)='$date' AND status='Selesai'"));
    $grafik_data[] = ['label'=>$days_label[$day_en], 'nilai'=>$res_g['total']??0, 'is_today'=>($i==0)];
}
$max_val = max(array_column($grafik_data, 'nilai')) ?: 1;

// Kategori
$res_kat = mysqli_query($koneksi, "
    SELECT m.kategori, SUM(dp.qty) as jumlah
    FROM detail_pesanan dp JOIN menu m ON dp.id_menu=m.id_menu JOIN pesanan p ON dp.id_pesanan=p.id_pesanan
    WHERE p.status='Selesai' GROUP BY m.kategori
");
$stats = ['Makanan'=>0,'Minuman'=>0,'Camilan'=>0];
$total_stats = 0;
while ($row = mysqli_fetch_assoc($res_kat)) {
    $nk = ucfirst(strtolower($row['kategori']));
    if (isset($stats[$nk])) { $stats[$nk] = $row['jumlah']; $total_stats += $row['jumlah']; }
}

// Data kantin + jumlah penjual
$res_kantin = mysqli_query($koneksi, "
    SELECT k.id_kantin, k.nama_kantin, k.deskripsi,
           COUNT(u.id_user) AS jumlah_penjual
    FROM kantin k
    LEFT JOIN users u ON u.id_kantin=k.id_kantin AND u.role='penjual'
    GROUP BY k.id_kantin ORDER BY k.id_kantin ASC
");
$daftar_kantin = [];
while ($row = mysqli_fetch_assoc($res_kantin)) { $daftar_kantin[] = $row; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/><meta content="width=device-width,initial-scale=1.0" name="viewport"/>
    <title><?= t('admin.dashboard_title') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    <script>
        tailwind.config = { theme: { extend: { colors: { 'bg-soft':'#FFF4EB','primary-orange':'#E25E3E','primary-orange-dark':'#C2410C','accent-orange':'#fb8500','neon-orange':'#ffb703' }, borderRadius:{'4xl':'2.5rem'} } } }
    </script>
    <style>
        body { font-family:'Plus Jakarta Sans',sans-serif; background: radial-gradient(circle at top left, rgba(251,146,60,.20), transparent 32%), radial-gradient(circle at 80% 20%, rgba(255,183,3,.12), transparent 25%), linear-gradient(180deg,#fff7f1 0%,#fff2e7 38%,#fff9f3 100%); }
        ::-webkit-scrollbar{width:6px;height:6px} ::-webkit-scrollbar-thumb{background:#FF8C20;border-radius:10px}
        .glow-card{box-shadow:0 25px 80px rgba(251,146,60,0.16);}
        .badge-genz{background:linear-gradient(135deg,#ffb703,#fb8500);color:#fff;}
        .box-fade{animation:fadein .25s ease-out forwards}
        @keyframes fadein{from{opacity:0;transform:scale(.97) translateY(10px)}to{opacity:1;transform:scale(1) translateY(0)}}
        .modal-anim{animation:fadein .2s ease-out forwards}
    </style>
</head>
<body class="bg-bg-soft text-slate-800 flex">

<?php include '../../includes/sidebar_admin.php'; ?>

<main class="flex-1 w-full lg:ml-72 p-6 md:p-10">
    <header class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-10 mt-14 lg:mt-0">
        <div>
            <h2 class="text-3xl md:text-4xl font-extrabold text-[#2a2a2a] tracking-tight"><?= t('admin.dashboard_overview') ?></h2>
            <p class="text-orange-700 font-semibold mt-2"><?= t('admin.dashboard_subtitle') ?></p>
            <div class="mt-4 inline-flex items-center gap-2 rounded-full border border-orange-200 px-4 py-2 bg-white/80 shadow-sm text-sm font-semibold text-orange-700">
                <span class="h-2.5 w-2.5 rounded-full bg-neon-orange shadow-[0_0_12px_rgba(255,183,3,0.45)]"></span>
                Gen-Z Mode Aktif
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-4 w-full md:w-auto">
            <div class="bg-white px-5 py-3 rounded-2xl border border-orange-100 flex items-center gap-3 shadow-glow-card text-sm font-bold text-orange-700">
                <span class="material-symbols-outlined text-orange-500 text-lg">calendar_today</span>
                <?= date('M d, Y') ?>
            </div>
            <button onclick="window.print()" class="bg-gradient-to-r from-orange-500 to-orange-600 text-white px-6 py-3 rounded-3xl font-black shadow-glow-card flex items-center justify-center gap-2 hover:-translate-y-0.5 transition-all w-full md:w-auto">
                <span class="material-symbols-outlined text-xl">print</span> <?= t('admin.print_report') ?>
            </button>
        </div>
    </header>

    <?php if ($message !== ''): ?>
    <div class="mb-6 px-5 py-4 rounded-2xl border <?= $message_type==='success' ? 'bg-orange-50 border-orange-100 text-primary-orange' : 'bg-red-50 border-red-100 text-red-500' ?> font-bold text-sm">
        <?= $message ?>
    </div>
    <?php endif; ?>

    <!-- Statistik Cards -->
    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-6 mb-10">
        <div class="bg-white/90 backdrop-blur-xl p-8 rounded-[2rem] border border-orange-100 flex items-center gap-5 shadow-glow-card">
            <div class="w-16 h-16 rounded-[24px] bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white shrink-0 shadow-xl shadow-orange-300/40"><span class="material-symbols-outlined text-3xl">group</span></div>
            <div><p class="text-[10px] font-black text-orange-500 uppercase tracking-widest">Total User</p><h3 class="text-2xl font-extrabold text-[#2a2a2a]"><?= number_format($total_user) ?></h3></div>
        </div>
        <div class="bg-white/90 backdrop-blur-xl p-8 rounded-[2rem] border border-orange-100 flex items-center gap-5 shadow-glow-card">
            <div class="w-16 h-16 rounded-[24px] bg-gradient-to-br from-orange-300 to-orange-500 flex items-center justify-center text-white shrink-0 shadow-xl shadow-orange-300/40"><span class="material-symbols-outlined text-3xl">store</span></div>
            <div><p class="text-[10px] font-black text-orange-500 uppercase tracking-widest">Total Penjual</p><h3 class="text-2xl font-extrabold text-[#2a2a2a]"><?= number_format($total_penjual) ?></h3></div>
        </div>
        <div class="bg-white/90 backdrop-blur-xl p-8 rounded-[2rem] border border-orange-100 flex items-center gap-5 shadow-glow-card">
            <div class="w-16 h-16 rounded-[24px] bg-gradient-to-br from-orange-200 to-orange-500 flex items-center justify-center text-orange-900 shrink-0 shadow-xl shadow-orange-300/40"><span class="material-symbols-outlined text-3xl">shopping_cart</span></div>
            <div><p class="text-[10px] font-black text-orange-500 uppercase tracking-widest">Pesanan Baru</p><h3 class="text-2xl font-extrabold text-[#2a2a2a]"><?= number_format($pesanan_hari_ini) ?></h3></div>
        </div>
        <div class="bg-white/90 backdrop-blur-xl p-8 rounded-[2rem] border border-orange-100 shadow-glow-card relative overflow-hidden">
            <div class="absolute -top-10 -right-6 w-24 h-24 rounded-full bg-orange-100/70 blur-2xl"></div>
            <p class="text-[10px] font-black text-orange-500 uppercase tracking-widest mb-1">Total Pendapatan</p>
            <h3 class="text-xl font-extrabold text-[#2a2a2a]">Rp <?= number_format($total_pendapatan,0,',','.') ?></h3>
            <div class="w-full h-1.5 bg-orange-100 rounded-full mt-4 overflow-hidden"><div class="h-full bg-orange-500 shadow-[0_0_20px_rgba(251,146,60,0.25)]" style="width:<?= $persen_target ?>%"></div></div>
            <p class="text-[8px] font-black text-orange-600 mt-2 tracking-tighter uppercase"><?= round($persen_target) ?>% CAPAIAN TARGET</p>
        </div>
        <div class="bg-white/90 backdrop-blur-xl p-8 rounded-[2rem] border border-orange-100 flex items-center gap-5 shadow-glow-card">
            <div class="w-16 h-16 rounded-[24px] bg-gradient-to-br from-orange-200 to-orange-400 flex items-center justify-center text-orange-900 shrink-0 shadow-xl shadow-orange-300/40"><span class="material-symbols-outlined text-3xl">admin_panel_settings</span></div>
            <div><p class="text-[10px] font-black text-orange-500 uppercase tracking-widest">Pendapatan Pajak</p><h3 class="text-xl font-extrabold text-[#2a2a2a]">Rp <?= number_format($total_biaya_admin,0,',','.') ?></h3></div>
        </div>
    </section>

    <!-- Grafik + Kategori -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8 mb-10">
        <div class="xl:col-span-2 bg-white p-8 md:p-10 rounded-4xl shadow-sm border border-slate-50">
            <h4 class="text-xl font-extrabold text-[#003049] mb-8"><?= t('admin.sales_statistics') ?></h4>
            <div class="flex items-end justify-between h-64 gap-2">
                <?php foreach($grafik_data as $g): $height = ($g['nilai'] / $max_val) * 100; ?>
                <div class="flex-1 flex flex-col items-center gap-4 group relative">
                    <div class="absolute -top-10 bg-[#003049] text-white text-[9px] px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">Rp<?= number_format($g['nilai']) ?></div>
                    <div class="w-full max-w-[12px] bg-slate-50 rounded-full relative h-48 overflow-hidden">
                        <div class="absolute bottom-0 left-0 w-full bg-primary-orange rounded-full transition-all duration-1000" style="height:<?= $height ?>%"></div>
                    </div>
                    <span class="text-[10px] font-black <?= $g['is_today'] ? 'text-primary-orange' : 'text-slate-300' ?>"><?= $g['label'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="bg-white p-8 md:p-10 rounded-4xl shadow-sm border border-slate-50 flex flex-col">
            <h4 class="text-xl font-extrabold text-[#003049] mb-2"><?= t('admin.category') ?></h4>
            <p class="text-xs text-slate-400 font-medium mb-10"><?= t('admin.category_subtitle') ?></p>
            <div class="space-y-8 flex-1">
                <?php $colors=['Makanan'=>'bg-primary-orange','Minuman'=>'bg-orange-400','Camilan'=>'bg-orange-500'];
                foreach($stats as $kat => $jml): $persen=($total_stats>0)?($jml/$total_stats)*100:0; ?>
                <div class="space-y-3">
                    <div class="flex justify-between text-[10px] font-black uppercase tracking-widest">
                        <span class="text-slate-400"><?= $kat ?></span><span class="text-[#003049]"><?= round($persen) ?>%</span>
                    </div>
                    <div class="h-2.5 w-full bg-slate-50 rounded-full overflow-hidden">
                        <div class="h-full <?= $colors[$kat]??'bg-slate-300' ?> rounded-full" style="width:<?= $persen ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Menu Terlaris Global -->
    <?php
    $q_terlaris = mysqli_query($koneksi, "
        SELECT m.nama_menu, m.harga, m.foto, k.nama_kantin,
               COALESCE(SUM(dp.qty),0) AS total_terjual,
               COUNT(DISTINCT rm.id_rating) AS jml_rating,
               COALESCE(AVG(rm.nilai_rating),0) AS avg_rating
        FROM menu m
        JOIN kantin k ON m.id_kantin = k.id_kantin
        LEFT JOIN detail_pesanan dp ON m.id_menu = dp.id_menu
        LEFT JOIN pesanan p ON dp.id_pesanan = p.id_pesanan AND p.status IN ('Selesai','Siap Diambil')
        LEFT JOIN rating_menu rm ON m.id_menu = rm.id_menu
        GROUP BY m.id_menu
        ORDER BY total_terjual DESC, avg_rating DESC
        LIMIT 6
    ");
    ?>
    <?php if ($q_terlaris && mysqli_num_rows($q_terlaris) > 0): ?>
    <div class="bg-gradient-to-br from-[#fff9f7] to-orange-50 rounded-4xl p-8 md:p-10 border border-orange-100 mb-10">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white shadow-lg shadow-orange-200">
                    <span class="material-symbols-outlined text-2xl">local_fire_department</span>
                </div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-orange-400">Hot Items</p>
                    <h3 class="text-xl font-extrabold text-[#003049]"><?= t('admin.popular_menu') ?></h3>
                </div>
            </div>
            <span class="bg-orange-100 text-orange-600 px-4 py-2 rounded-2xl text-xs font-bold">Top Sellers</span>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <?php
            $rank = 1;
            while ($tl = mysqli_fetch_assoc($q_terlaris)):
                $foto = !empty($tl['foto']) && file_exists("../../uploads/{$tl['foto']}") ? "../../uploads/{$tl['foto']}" : "../../public/assets/img/default-food.svg";
                $terjual = (int)($tl['total_terjual'] ?? 0);
                $rating = round((float)($tl['avg_rating'] ?? 0), 1);
            ?>
            <div class="bg-white rounded-2xl p-4 border border-orange-100 hover:shadow-lg hover:shadow-orange-200/30 transition-all relative">
                <?php if ($rank <= 3): ?>
                <div class="absolute -top-2 -right-2 w-7 h-7 rounded-full bg-gradient-to-br <?= $rank == 1 ? 'from-orange-400 to-orange-600' : ($rank == 2 ? 'from-orange-300 to-orange-500' : 'from-orange-200 to-orange-400') ?> flex items-center justify-center text-white font-black text-xs shadow-lg z-10">
                    <?= $rank ?>
                </div>
                <?php endif; ?>
                <img src="<?= $foto ?>" class="w-full aspect-square rounded-xl object-cover bg-orange-50 mb-3" alt="<?= htmlspecialchars($tl['nama_menu']) ?>">
                <p class="text-xs font-bold text-slate-400 truncate"><?= htmlspecialchars($tl['nama_kantin']) ?></p>
                <p class="font-black text-sm text-slate-800 truncate mt-1"><?= htmlspecialchars($tl['nama_menu']) ?></p>
                <p class="text-[#b22204] font-black text-sm mt-1">Rp <?= number_format($tl['harga'],0,',','.') ?></p>
                <div class="flex items-center justify-between mt-2 text-[10px]">
                    <span class="text-orange-500 font-bold"><i class="fa-solid fa-fire mr-1"></i><?= $terjual ?></span>
                    <span class="text-yellow-500 font-bold"><i class="fa-solid fa-star mr-1"></i><?= $rating > 0 ? $rating : '-' ?></span>
                </div>
            </div>
            <?php $rank++; endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Daftar Kantin -->
    <div class="bg-white rounded-4xl shadow-sm border border-slate-50 overflow-hidden">
        <div class="p-8 border-b border-slate-50 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div>
                <h3 class="font-extrabold text-[#003049] text-lg flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary-orange">storefront</span>
                    <?= t('admin.kantin_list') ?>
                </h3>
                <p class="text-xs text-slate-400 font-medium mt-1"><?= count($daftar_kantin) ?> <?= t('admin.kantin_registered') ?></p>
            </div>
            <button onclick="openModalKantin()" class="bg-primary-orange text-white px-5 py-3 rounded-2xl font-bold shadow-lg shadow-orange-200 flex items-center gap-2 hover:scale-105 transition-all text-sm whitespace-nowrap">
                <span class="material-symbols-outlined text-lg">add_home_work</span> <?= t('admin.add_kantin') ?>
            </button>
        </div>

        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
            <?php if (count($daftar_kantin) === 0): ?>
            <div class="col-span-3 py-20 flex flex-col items-center text-slate-300">
                <span class="material-symbols-outlined text-5xl">domain_disabled</span>
                <p class="mt-3 font-bold text-slate-400 text-sm"><?= t('admin.no_kantin') ?></p>
            </div>
            <?php endif; ?>

            <?php foreach($daftar_kantin as $k):
                $jml  = (int)$k['jumlah_penjual'];
                $penuh = ($jml >= 5);
                $pct   = ($jml / 5) * 100;
                $bar   = $penuh ? 'bg-orange-400' : ($jml >= 3 ? 'bg-orange-300' : 'bg-primary-orange');
            ?>
<div class="p-5 rounded-3xl border <?= $penuh ? 'border-orange-100 bg-orange-50/20' : 'border-orange-50 bg-orange-50/30' ?> flex flex-col gap-4 hover:shadow-md transition-all">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-2xl <?= $penuh ? 'bg-orange-100 text-orange-500' : 'bg-orange-50 text-primary-orange' ?> flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-2xl">store</span>
                        </div>
                        <div>
                            <p class="font-extrabold text-[#003049] text-sm leading-tight"><?= htmlspecialchars($k['nama_kantin']) ?></p>
                            <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">ID: #<?= $k['id_kantin'] ?></p>
                        </div>
                    </div>
                    <span class="text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-lg border <?= $penuh ? 'text-orange-600 bg-orange-50 border-orange-100' : 'text-orange-700 bg-orange-100 border-orange-200' ?>">
                        <?= $penuh ? t('admin.full') : t('admin.available') ?>
                    </span>
                </div>

                <?php if (!empty($k['deskripsi'])): ?>
                <p class="text-xs text-slate-400 font-medium leading-relaxed -mt-1"><?= htmlspecialchars($k['deskripsi']) ?></p>
                <?php endif; ?>

                <div>
                    <div class="flex justify-between items-center mb-1.5">
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Slot Penjual</p>
                        <p class="text-[10px] font-black <?= $penuh ? 'text-orange-600' : 'text-primary-orange' ?>"><?= $jml ?> / 5</p>
                    </div>
                    <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full <?= $bar ?> rounded-full" style="width:<?= $pct ?>%"></div>
                    </div>
                    <div class="flex gap-1 mt-2">
                        <?php for ($s=1;$s<=5;$s++): ?>
                        <div class="flex-1 h-1 rounded-full <?= $s<=$jml ? ($penuh ? 'bg-orange-300' : 'bg-primary-orange') : 'bg-slate-100' ?>"></div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<!-- Modal Tambah Kantin -->
<div id="modal-tambah-kantin" class="fixed inset-0 z-[100] hidden items-center justify-center">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeModalKantin()"></div>
    <div class="relative bg-white rounded-4xl shadow-2xl w-full max-w-md mx-4 overflow-hidden modal-anim">
        <div class="bg-gradient-to-br from-[#E25E3E] to-[#c04828] p-7 text-white relative">
            <button onclick="closeModalKantin()" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-all">
                <span class="material-symbols-outlined text-white text-lg">close</span>
            </button>
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-white/15 flex items-center justify-center">
                    <span class="material-symbols-outlined text-3xl">add_home_work</span>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-white/70">Admin Panel</p>
                    <h3 class="text-xl font-extrabold"><?= t('admin.add_new_kantin') ?></h3>
                </div>
            </div>
        </div>
        <form action="" method="POST" class="p-7 space-y-5">
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Nama Kantin <span class="text-red-400">*</span></label>
                <input type="text" name="nama_kantin" required
                       class="w-full px-4 py-3 rounded-2xl border border-slate-100 bg-slate-50 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary-orange/20 focus:border-primary-orange"
                       placeholder="Contoh: Kantin Pojok Sehat">
            </div>
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Deskripsi <span class="text-slate-300">(opsional)</span></label>
                <textarea name="deskripsi" rows="3"
                          class="w-full px-4 py-3 rounded-2xl border border-slate-100 bg-slate-50 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-primary-orange/20 focus:border-primary-orange resize-none"
                          placeholder="Deskripsi singkat kantin..."></textarea>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="closeModalKantin()"
                        class="flex-1 py-3 rounded-2xl border border-slate-200 text-slate-500 font-bold text-sm hover:bg-slate-50 transition-all"><?= t('action.cancel') ?></button>
                <button type="submit" name="tambah_kantin"
                        class="flex-1 py-3 rounded-2xl bg-primary-orange text-white font-bold text-sm shadow-lg shadow-orange-100 hover:scale-[1.02] transition-all flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-sm">save</span> <?= t('action.save') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModalKantin(){ const m=document.getElementById('modal-tambah-kantin'); m.classList.remove('hidden'); m.classList.add('flex'); }
function closeModalKantin(){ const m=document.getElementById('modal-tambah-kantin'); m.classList.add('hidden'); m.classList.remove('flex'); }
document.addEventListener('keydown', e=>{ if(e.key==='Escape') closeModalKantin(); });
<?php if($message!=='' && $message_type==='error'): ?>
document.addEventListener('DOMContentLoaded', ()=>openModalKantin());
<?php endif; ?>
</script>
</body>
</html>
