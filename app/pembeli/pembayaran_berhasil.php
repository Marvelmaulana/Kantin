<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['id_pesanan'])) {
    header("Location: dashboard.php");
    exit();
}

$id_pesanan = mysqli_real_escape_string($koneksi, $_GET['id_pesanan']);
$id_user    = $_SESSION['id_user'];

// Ambil data pesanan induk
$q_pesanan = mysqli_query($koneksi, "
    SELECT pesanan.*, users.username
    FROM pesanan 
    JOIN users ON pesanan.id_user = users.id_user
    WHERE pesanan.id_pesanan = '$id_pesanan' AND pesanan.id_user = '$id_user'
");

if (mysqli_num_rows($q_pesanan) == 0) {
    header("Location: dashboard.php");
    exit();
}

$pesanan = mysqli_fetch_assoc($q_pesanan);
$metode  = $pesanan['metode_pembayaran'];
$isEwallet = in_array($metode, ['GOPAY', 'OVO', 'DANA']);

// Ambil detail item pesanan
$q_detail = mysqli_query($koneksi, "
    SELECT detail_pesanan.*, menu.nama_menu, menu.harga, menu.foto, kantin.nama_kantin
    FROM detail_pesanan
    JOIN menu ON detail_pesanan.id_menu = menu.id_menu
    JOIN kantin ON menu.id_kantin = kantin.id_kantin
    WHERE detail_pesanan.id_pesanan = '$id_pesanan'
");

// Konfigurasi per metode
$methodConfig = [
    'GOPAY' => ['color' => '#00AED6', 'bg' => '#e6f7fb', 'icon' => 'payments',       'label' => 'GoPay'],
    'OVO'   => ['color' => '#4C3494', 'bg' => '#ede8f5', 'icon' => 'wallet',          'label' => 'OVO'],
    'DANA'  => ['color' => '#108BE3', 'bg' => '#e3f1fc', 'icon' => 'account_balance_wallet', 'label' => 'DANA'],
    'CASH'  => ['color' => '#2e7d32', 'bg' => '#e8f5e9', 'icon' => 'storefront',      'label' => 'Tunai / Kasir'],
];
$cfg = $methodConfig[$metode] ?? $methodConfig['CASH'];

// Nomor referensi acak (simulasi)
$ref_number = strtoupper(substr($metode, 0, 3)) . '-' . date('Ymd', strtotime($pesanan['tanggal'])) . '-' . str_pad($id_pesanan, 5, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Pembayaran Berhasil - Kantin Kita</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@400,1&display=swap" rel="stylesheet"/>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Be Vietnam Pro', sans-serif; background: #fff8f6; min-height: 100vh; }
        .headline { font-family: 'Plus Jakarta Sans', sans-serif; }

        /* ===== SUKSES ICON ANIMASI ===== */
        @keyframes pop-in {
            0%   { transform: scale(0.3); opacity: 0; }
            70%  { transform: scale(1.15); }
            100% { transform: scale(1); opacity: 1; }
        }
        @keyframes check-draw {
            from { stroke-dashoffset: 60; }
            to   { stroke-dashoffset: 0; }
        }
        @keyframes fade-up {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes confetti-fall {
            0%   { transform: translateY(-20px) rotate(0deg); opacity: 1; }
            100% { transform: translateY(120px) rotate(720deg); opacity: 0; }
        }

        .icon-wrap { animation: pop-in 0.5s cubic-bezier(.36,.07,.19,.97) 0.1s both; }
        .check-path {
            stroke-dasharray: 60;
            stroke-dashoffset: 60;
            animation: check-draw 0.4s ease 0.55s forwards;
        }
        .fade-up-1 { animation: fade-up 0.4s ease 0.4s both; }
        .fade-up-2 { animation: fade-up 0.4s ease 0.55s both; }
        .fade-up-3 { animation: fade-up 0.4s ease 0.7s both; }
        .fade-up-4 { animation: fade-up 0.4s ease 0.85s both; }
        .fade-up-5 { animation: fade-up 0.4s ease 1.0s both; }

        /* ===== STRUK ===== */
        .struk {
            background: white;
            border-radius: 24px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.07);
            overflow: hidden;
            position: relative;
        }
        /* Efek garis putus-putus di bawah header struk */
        .struk-divider {
            display: flex;
            align-items: center;
            gap: 0;
        }
        .struk-divider::before,
        .struk-divider::after {
            content: '';
            width: 20px;
            height: 20px;
            background: #fff8f6;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .struk-divider-line {
            flex: 1;
            border-top: 2px dashed #e4e4e7;
        }

        /* ===== BADGE STATUS ===== */
        .badge-berhasil {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.05em;
        }

        /* ===== CONFETTI ===== */
        .confetti-piece {
            position: fixed;
            width: 8px;
            height: 8px;
            border-radius: 2px;
            animation: confetti-fall 1.2s ease-out forwards;
            pointer-events: none;
            z-index: 9999;
        }

        /* ===== TOMBOL ===== */
        .btn-primary {
            background: linear-gradient(135deg, #b22204, #d63c1e);
            color: white;
            border-radius: 99px;
            height: 52px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: 15px;
            letter-spacing: 0.03em;
            box-shadow: 0 8px 20px rgba(178,34,4,0.3);
            transition: transform 0.15s, box-shadow 0.15s;
            width: 100%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-primary:active { transform: scale(0.97); box-shadow: 0 4px 10px rgba(178,34,4,0.2); }
        .btn-secondary {
            background: white;
            color: #3f3f46;
            border: 1.5px solid #e4e4e7;
            border-radius: 99px;
            height: 48px;
            font-weight: 600;
            font-size: 14px;
            width: 100%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.15s;
        }
        .btn-secondary:hover { background: #f4f4f5; }
    </style>
</head>
<body class="pb-12">

<!-- CONFETTI (hanya muncul saat load) -->
<div id="confetti-container"></div>

<div class="max-w-md mx-auto px-4 pt-10 space-y-6">

    <!-- ===== SUKSES HEADER ===== -->
    <div class="text-center space-y-3">
        <!-- Icon lingkaran centang animasi -->
        <div class="icon-wrap inline-flex items-center justify-center w-24 h-24 rounded-full mx-auto"
             style="background: <?= $isEwallet ? '#dcfce7' : $cfg['bg'] ?>;">
            <?php if ($isEwallet): ?>
            <svg width="52" height="52" viewBox="0 0 52 52" fill="none">
                <circle cx="26" cy="26" r="24" fill="#22c55e" opacity="0.15"/>
                <circle cx="26" cy="26" r="24" stroke="#22c55e" stroke-width="2.5"/>
                <polyline class="check-path" points="14,27 22,35 38,18"
                    stroke="#16a34a" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            </svg>
            <?php else: ?>
            <span class="material-symbols-outlined text-5xl" style="color:<?= $cfg['color'] ?>; font-variation-settings:'FILL' 1;">storefront</span>
            <?php endif; ?>
        </div>

        <div class="fade-up-1">
            <h1 class="headline font-black text-2xl text-zinc-800">
                <?= $isEwallet ? 'Pembayaran Berhasil!' : 'Pesanan Diterima!' ?>
            </h1>
            <p class="text-zinc-500 text-sm mt-1">
                <?= $isEwallet
                    ? "Transaksi via <strong>{$cfg['label']}</strong> telah terkonfirmasi"
                    : "Silakan bayar ke kasir saat mengambil pesanan" ?>
            </p>
        </div>
    </div>

    <!-- ===== STRUK ===== -->
    <div class="struk fade-up-2">
        <!-- Header Struk -->
        <div class="px-5 pt-5 pb-4" style="background: <?= $cfg['bg'] ?>;">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg" style="color:<?= $cfg['color'] ?>; font-variation-settings:'FILL' 1;"><?= $cfg['icon'] ?></span>
                    <span class="font-bold text-sm" style="color:<?= $cfg['color'] ?>"><?= $cfg['label'] ?></span>
                </div>
                <span class="badge-berhasil" style="background:<?= $isEwallet ? '#dcfce7' : '#f0fdf4' ?>; color:<?= $isEwallet ? '#16a34a' : $cfg['color'] ?>;">
                    <span class="material-symbols-outlined text-xs" style="font-variation-settings:'FILL' 1;"><?= $isEwallet ? 'check_circle' : 'pending' ?></span>
                    <?= $isEwallet ? 'BERHASIL' : 'MENUNGGU BAYAR' ?>
                </span>
            </div>
            <p class="text-[11px] text-zinc-400 mt-2 font-mono"><?= $ref_number ?></p>
        </div>

        <!-- Garis putus-putus separator -->
        <div class="struk-divider px-3 py-0" style="margin: 0 -1px;">
            <div class="struk-divider-line"></div>
        </div>

        <!-- Detail Item -->
        <div class="px-5 py-4 space-y-3">
            <p class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Detail Pesanan</p>
            <?php while($item = mysqli_fetch_assoc($q_detail)): ?>
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <img src="../../uploads/<?= $item['foto'] ?>"
                         class="w-10 h-10 rounded-xl object-cover bg-zinc-100"
                         onerror="this.src='../../assets/img/default-food.jpg'">
                    <div>
                        <p class="font-semibold text-sm leading-tight"><?= $item['nama_menu'] ?></p>
                        <p class="text-[11px] text-zinc-400"><?= $item['nama_kantin'] ?> · x<?= $item['qty'] ?></p>
                    </div>
                </div>
                <span class="text-sm font-bold text-zinc-700 whitespace-nowrap">
                    Rp <?= number_format($item['harga'] * $item['qty'], 0, ',', '.') ?>
                </span>
            </div>
            <?php endwhile; ?>
        </div>

        <!-- Garis putus-putus separator -->
        <div class="struk-divider px-3" style="margin: 0 -1px;">
            <div class="struk-divider-line"></div>
        </div>

        <!-- Info Pembayaran -->
        <div class="px-5 py-4 space-y-2 text-sm">
            <div class="flex justify-between text-zinc-500">
                <span>Tanggal</span>
                <span class="font-medium"><?= date('d M Y, H:i', strtotime($pesanan['tanggal'])) ?></span>
            </div>
            <div class="flex justify-between text-zinc-500">
                <span>Nama</span>
                <span class="font-medium"><?= htmlspecialchars($pesanan['username']) ?></span>
            </div>
            <div class="flex justify-between text-zinc-500">
                <span>Metode</span>
                <span class="font-bold" style="color:<?= $cfg['color'] ?>"><?= $cfg['label'] ?></span>
            </div>
            <?php if ($isEwallet): ?>
            <div class="flex justify-between text-zinc-500">
                <span>No. Referensi</span>
                <span class="font-mono text-xs font-bold"><?= $ref_number ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Total -->
        <div class="mx-4 mb-5 p-4 rounded-2xl" style="background: <?= $cfg['bg'] ?>;">
            <div class="flex justify-between items-center">
                <span class="headline font-extrabold text-base text-zinc-700">Total Bayar</span>
                <span class="headline font-black text-xl" style="color:<?= $cfg['color'] ?>">
                    Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?>
                </span>
            </div>
            <?php if (!$isEwallet): ?>
            <p class="text-[11px] text-zinc-400 mt-1">⚠️ Tunjukkan halaman ini ke kasir saat mengambil pesanan</p>
            <?php else: ?>
            <p class="text-[11px] text-zinc-400 mt-1">✅ Dana telah berhasil didebit dari akun <?= $cfg['label'] ?> kamu</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== TOMBOL AKSI ===== -->
    <div class="space-y-3 fade-up-3">
        <?php if ($isEwallet): ?>
        <button onclick="downloadStruk()" class="btn-secondary">
            <span class="material-symbols-outlined text-base">download</span>
            Simpan Struk
        </button>
        <?php endif; ?>
        <a href="dashboard.php">
            <button class="btn-primary">
                <span class="material-symbols-outlined text-base">home</span>
                Kembali ke Beranda
            </button>
        </a>
    </div>

    <!-- ===== INFO TAMBAHAN (CASH) ===== -->
    <?php if (!$isEwallet): ?>
    <div class="fade-up-4 p-4 rounded-2xl border-2 border-dashed" style="border-color:<?= $cfg['color'] ?>30; background:<?= $cfg['bg'] ?>;">
        <div class="flex gap-3">
            <span class="material-symbols-outlined text-2xl mt-0.5" style="color:<?= $cfg['color'] ?>; font-variation-settings:'FILL' 1;">info</span>
            <div>
                <p class="font-bold text-sm" style="color:<?= $cfg['color'] ?>">Cara Pengambilan</p>
                <ol class="text-xs text-zinc-500 mt-1 space-y-1 list-decimal list-inside">
                    <li>Tunjukkan halaman ini ke kasir kantin</li>
                    <li>Kasir akan menyiapkan pesananmu</li>
                    <li>Lakukan pembayaran tunai di kasir</li>
                    <li>Selamat menikmati! 😊</li>
                </ol>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ID Pesanan kecil di bawah -->
    <p class="fade-up-5 text-center text-[11px] text-zinc-300 pb-4">
        ID Pesanan: #<?= str_pad($id_pesanan, 5, '0', STR_PAD_LEFT) ?>
    </p>

</div>

<script>
// ===== CONFETTI =====
(function spawnConfetti() {
    const colors = ['#b22204','#22c55e','#f59e0b','#3b82f6','#a855f7','#ec4899'];
    const container = document.getElementById('confetti-container');
    for (let i = 0; i < 30; i++) {
        const el = document.createElement('div');
        el.className = 'confetti-piece';
        el.style.cssText = `
            left: ${Math.random() * 100}vw;
            top: ${Math.random() * -10}vh;
            background: ${colors[Math.floor(Math.random() * colors.length)]};
            width: ${6 + Math.random() * 6}px;
            height: ${6 + Math.random() * 6}px;
            border-radius: ${Math.random() > 0.5 ? '50%' : '2px'};
            animation-delay: ${Math.random() * 0.8}s;
            animation-duration: ${1 + Math.random() * 0.8}s;
        `;
        container.appendChild(el);
    }
    // Bersihkan setelah animasi selesai
    setTimeout(() => container.innerHTML = '', 3000);
})();

// ===== DOWNLOAD STRUK (Print to PDF) =====
function downloadStruk() {
    window.print();
}
</script>

<!-- Print style: sembunyikan tombol saat print -->
<style>
@media print {
    .btn-primary, .btn-secondary, #confetti-container { display: none !important; }
    body { background: white; }
    .struk { box-shadow: none; border: 1px solid #eee; }
}
</style>

</body>
</html>