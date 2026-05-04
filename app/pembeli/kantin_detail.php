<?php
session_start();
include(__DIR__ . '/../../config/config.php');

// 1. Tangkap ID Kantin dari URL
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$id_kantin = mysqli_real_escape_string($koneksi, $_GET['id']);

// 2. Ambil Info Kantin (untuk Nama Kantin di Header)
// Perbaikan: Ambil dari tabel users jika info kantin ada di sana, 
// atau pastikan tabel 'kantin' memiliki nama_kantin
$q_kantin = mysqli_query($koneksi, "SELECT * FROM users WHERE id_kantin = '$id_kantin' AND role='penjual'");
$kantin = mysqli_fetch_assoc($q_kantin);

// Jika tabel kantin terpisah, gunakan query asalmu:
// $q_kantin = mysqli_query($koneksi, "SELECT * FROM kantin WHERE id_kantin = '$id_kantin'");

// 3. Ambil Menu KHUSUS dari Kantin ini saja
$query_menu = mysqli_query($koneksi, "SELECT * FROM menu WHERE id_kantin = '$id_kantin' ORDER BY id_menu DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu <?= htmlspecialchars($kantin['username'] ?? 'Kantin') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <style>
        :root { --primary: #ab2d00; --bg-page: #fff4f3; }
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: var(--bg-page); padding: 20px; padding-bottom: 30px; }
        
        .header-nav { display: flex; align-items: center; margin-bottom: 25px; }
        .btn-back { background: white; color: var(--primary); width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; text-decoration: none; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        
        .kantin-info-header { background: linear-gradient(135deg, #ab2d00, #ff7851); padding: 25px; border-radius: 25px; color: white; margin-bottom: 25px; }
        
        .menu-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .card { background: white; border-radius: 25px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03); position: relative; }
        /* PERBAIKAN: Tinggi gambar disesuaikan agar proporsional */
        .card img { width: 100%; height: 140px; object-fit: cover; background: #f0f0f0; display: block; }
        .card-body { padding: 12px; }
        .card-body b { font-size: 14px; display: block; margin-bottom: 4px; color: #333; }
        .price { color: var(--primary); font-weight: bold; font-size: 14px; }
        .btn-add { position: absolute; bottom: 12px; right: 12px; width: 32px; height: 32px; background: #ffd2d0; color: var(--primary); border-radius: 50%; text-align: center; line-height: 32px; text-decoration: none; font-weight: bold; transition: 0.3s; }
        .btn-add:hover { background: var(--primary); color: white; }
    </style>
</head>
<body>

    <div class="header-nav">
        <a href="dashboard.php" class="btn-back">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <h2 style="margin: 0 0 0 15px; font-size: 18px; color: var(--primary);">Daftar Menu</h2>
    </div>

    <div class="kantin-info-header">
        <span style="font-size: 12px; opacity: 0.8; text-transform: uppercase; font-weight: bold;">Selamat Datang di</span>
        <h2 style="margin: 5px 0 0 0;"><?= htmlspecialchars($kantin['username'] ?? 'Kantin') ?></h2>
        <p style="margin: 5px 0 0 0; font-size: 13px; opacity: 0.9;">Silakan pilih menu favoritmu dari kantin ini.</p>
    </div>

    <div class="menu-grid">
        <?php if(mysqli_num_rows($query_menu) > 0) : ?>
            <?php while($m = mysqli_fetch_assoc($query_menu)) : ?>
            <div class="card">
                <img src="../../uploads/<?= $m['foto'] ?>" onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                <div class="card-body">
                    <b><?= htmlspecialchars($m['nama_menu']) ?></b>
                    <span class="price">Rp <?= number_format($m['harga'], 0, ',', '.') ?></span>
                    <a href="tambah_keranjang.php?id_menu=<?= $m['id_menu'] ?>" class="btn-add">+</a>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else : ?>
            <div style="grid-column: span 2; text-align: center; padding: 50px 0; color: #999;">
                <span class="material-symbols-outlined" style="font-size: 48px;">sentiment_dissatisfied</span>
                <p>Kantin ini belum memiliki menu.</p>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>