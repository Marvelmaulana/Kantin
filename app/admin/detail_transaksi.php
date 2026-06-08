<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/language_helper.php');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$id_pesanan = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id_pesanan) {
    header('Location: dashboard_admin.php');
    exit();
}

// Query data transaksi dari pesanan
$query_transaksi = mysqli_query($koneksi, "
    SELECT t.id_pesanan as id_transaksi, t.*, u.username, u.email, k.nama_kantin
    FROM pesanan t
    LEFT JOIN users u ON t.id_user = u.id_user
    LEFT JOIN kantin k ON t.id_kantin = k.id_kantin
    WHERE t.id_pesanan = $id_pesanan
    LIMIT 1
");

if (!$query_transaksi || mysqli_num_rows($query_transaksi) === 0) {
    die("Transaksi tidak ditemukan.");
}

$transaksi = mysqli_fetch_assoc($query_transaksi);
$id_transaksi = $id_pesanan;

// Query detail item pesanan
$items = [];
if ($id_pesanan > 0) {
    $query_items = mysqli_query($koneksi, "
        SELECT * FROM detail_pesanan 
        WHERE id_pesanan = $id_pesanan
    ");
    if ($query_items) {
        while ($row = mysqli_fetch_assoc($query_items)) {
            $items[] = [
                'nama_menu' => $row['nama_menu'] ?? 'Menu Tidak Dikenal',
                'harga' => $row['harga'] ?? 0,
                'qty' => $row['qty'],
                'subtotal' => $row['subtotal'],
                'catatan' => $row['catatan'] ?? '',
                'opsi_pilihan' => $row['opsi_pilihan'] ?? ''
            ];
        }
    }
}

// Fallback ke detail_transaksi jika detail_pesanan kosong
if (empty($items)) {
    $query_items = mysqli_query($koneksi, "
        SELECT dt.*, m.nama_menu, m.harga 
        FROM detail_transaksi dt
        LEFT JOIN menu m ON dt.id_menu = m.id_menu
        WHERE dt.id_transaksi = $id_transaksi
    ");
    if ($query_items) {
        while ($row = mysqli_fetch_assoc($query_items)) {
            $items[] = [
                'nama_menu' => $row['nama_menu'] ?? 'Menu Tidak Dikenal',
                'harga' => $row['harga'] ?? ($row['qty'] > 0 ? $row['subtotal'] / $row['qty'] : 0),
                'qty' => $row['qty'],
                'subtotal' => $row['subtotal'],
                'catatan' => '',
                'opsi_pilihan' => ''
            ];
        }
    }
}

// Kalkulasi subtotal sebelum pajak
$subtotal_items = 0;
foreach ($items as $item) {
    $subtotal_items += $item['subtotal'];
}

$statusClass = match($transaksi['status']) {
    'Selesai', 'Berhasil' => 'bg-green-100 text-green-700 border-green-200',
    'Diproses' => 'bg-blue-100 text-blue-700 border-blue-200',
    'Pending' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
    'Dibatalkan' => 'bg-red-100 text-red-700 border-red-200',
    default => 'bg-slate-100 text-slate-700 border-slate-200'
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Detail Transaksi #<?= $id_transaksi ?> - Kantin Kita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'bg-soft': '#FFF4EB',
                        'primary-orange': '#E25E3E',
                        'primary-orange-dark': '#C2410C',
                        'accent-orange': '#fb8500',
                        'neon-orange': '#ffb703'
                    },
                    borderRadius: {
                        '4xl': '2.5rem'
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: radial-gradient(circle at top left, rgba(251,146,60,.20), transparent 32%), radial-gradient(circle at 80% 20%, rgba(255,183,3,.12), transparent 25%), linear-gradient(180deg,#fff7f1 0%,#fff2e7 38%,#fff9f3 100%); }
        @media print {
            body { background: white; }
            #sidebar, .no-print { display: none !important; }
            main { margin-left: 0 !important; padding: 0 !important; width: 100% !important; }
            .print-card { border: none !important; box-shadow: none !important; }
        }
    </style>
</head>
<body class="text-slate-800 flex min-h-screen overflow-x-hidden">

<?php include '../../includes/sidebar_admin.php'; ?>

<main class="flex-1 w-full lg:ml-72 p-4 md:p-6 lg:p-8 overflow-x-hidden max-w-full">
    <!-- Header -->
    <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 mt-14 lg:mt-0 no-print">
        <div>
            <div class="flex items-center gap-2 text-sm text-orange-600 font-bold mb-2">
                <a href="dashboard_admin.php" class="hover:underline flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">arrow_back</span> Kembali
                </a>
            </div>
            <h2 class="text-2xl md:text-3xl font-extrabold text-[#2a2a2a] tracking-tight">Detail Transaksi</h2
            <p class="text-orange-700 font-semibold mt-1 text-sm md:text-base">Informasi rincian transaksi #<?= $id_transaksi ?></p>
        </div>
        <div class="flex items-center gap-3 w-full sm:w-auto">
            <button onclick="window.print()" class="bg-gradient-to-r from-orange-500 to-orange-600 text-white px-5 py-2.5 rounded-2xl font-bold shadow-lg flex items-center justify-center gap-2 hover:-translate-y-0.5 transition-all text-sm w-full sm:w-auto">
                <span class="material-symbols-outlined text-lg">print</span> Cetak Transaksi
            </button>
        </div>
    </header>

    <div class="max-w-3xl bg-white rounded-3xl border border-orange-100 shadow-xl print-card overflow-hidden">
        <!-- Invoice Header -->
        <div class="bg-gradient-to-r from-orange-500 to-orange-600 text-white p-6 md:p-8">
            <div class="flex flex-col md:flex-row justify-between gap-4">
                <div>
                    <h3 class="text-2xl font-extrabold">Kantin Kita</h3>
                    <p class="text-orange-100 text-sm mt-1">Sistem Pemesanan Kantin Sekolah</p>
                </div>
                <div class="md:text-right">
                    <p class="text-xs uppercase tracking-widest text-orange-200">ID Transaksi</p>
                    <p class="text-xl font-bold">#<?= htmlspecialchars($transaksi['id_transaksi']) ?></p>
                    <?php if ($id_pesanan > 0): ?>
                        <p class="text-xs text-orange-200 mt-1">ID Pesanan: #<?= $id_pesanan ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Meta Grid -->
        <div class="p-6 md:p-8 border-b border-orange-50 bg-orange-50/20">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pembeli</p>
                    <p class="font-bold text-slate-800 text-sm mt-1"><?= htmlspecialchars($transaksi['username'] ?? 'Guest') ?></p>
                    <p class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($transaksi['email'] ?? '-') ?></p>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Kantin</p>
                    <p class="font-bold text-slate-800 text-sm mt-1"><?= htmlspecialchars($transaksi['nama_kantin'] ?? '-') ?></p>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tanggal & Waktu</p>
                    <p class="font-semibold text-slate-800 text-sm mt-1"><?= date('d M Y', strtotime($transaksi['tanggal'])) ?></p>
                    <p class="text-xs text-slate-500 mt-0.5"><?= date('H:i', strtotime($transaksi['tanggal'])) ?> WIB</p>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Status</p>
                    <span class="inline-block px-3 py-1 rounded-xl text-xs font-bold border mt-1 <?= $statusClass ?>">
                        <?= htmlspecialchars($transaksi['status']) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="p-6 md:p-8">
            <h4 class="text-sm font-bold text-[#003049] uppercase tracking-wider mb-4">Daftar Item Belanja</h4>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-slate-100 text-left text-xs font-black text-slate-400 uppercase tracking-wider">
                            <th class="pb-3 text-left">Nama Menu</th>
                            <th class="pb-3 text-center w-20">Jumlah</th>
                            <th class="pb-3 text-right w-32">Harga Satuan</th>
                            <th class="pb-3 text-right w-36">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="4" class="py-6 text-center text-slate-400 text-sm">Tidak ada rincian item belanja.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <tr class="text-slate-800">
                                    <td class="py-4">
                                        <p class="font-bold text-sm text-slate-800"><?= htmlspecialchars($item['nama_menu']) ?></p>
                                        <?php if (!empty($item['opsi_pilihan'])): ?>
                                            <p class="text-[10px] text-slate-400 mt-0.5">Pilihan: <?= htmlspecialchars($item['opsi_pilihan']) ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($item['catatan'])): ?>
                                            <p class="text-[10px] text-orange-600 bg-orange-50 px-2 py-0.5 rounded inline-block mt-1">Catatan: <?= htmlspecialchars($item['catatan']) ?></p>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 text-center font-semibold text-sm"><?= $item['qty'] ?>x</td>
                                    <td class="py-4 text-right text-sm">Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                                    <td class="py-4 text-right font-bold text-sm text-primary-orange">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Price Summary -->
            <div class="mt-8 border-t border-slate-100 pt-6 flex justify-end">
                <div class="w-full md:w-80">
                    <div class="flex justify-between py-1 text-slate-500 text-sm">
                        <span>Total Belanja</span>
                        <span class="font-semibold text-slate-800">Rp <?= number_format($subtotal_items ?: $transaksi['total_harga'], 0, ',', '.') ?></span>
                    </div>
                    <div class="flex justify-between py-1 text-slate-500 text-sm">
                        <span>Biaya Layanan (Pendapatan Admin)</span>
                        <span class="font-semibold text-slate-800">Rp <?= number_format($transaksi['pajak'] ?? 1000, 0, ',', '.') ?></span>
                    </div>
                    <div class="flex justify-between py-1 text-slate-500 text-sm">
                        <span>Metode Pembayaran</span>
                        <span class="font-semibold text-slate-800"><?= htmlspecialchars($transaksi['metode_pembayaran'] ?? 'Cash') ?></span>
                    </div>
                    <div class="flex justify-between py-3 border-t border-slate-100 mt-3 text-slate-800 font-extrabold text-base">
                        <span>Total Pembayaran</span>
                        <span class="text-primary-orange text-lg">Rp <?= number_format(($subtotal_items ? $subtotal_items + ($transaksi['pajak'] ?? 1000) : $transaksi['total_harga']), 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice Footer -->
        <div class="bg-slate-50 p-6 text-center border-t border-slate-100">
            <p class="text-xs text-slate-400">Terima kasih atas transaksi Anda.</p>
            <p class="text-[10px] text-slate-300 mt-1">Dicetak otomatis melalui Sistem Admin Kantin Kita</p>
        </div>
    </div>
</main>

</body>
</html>
