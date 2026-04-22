<?php
session_start();
include 'config.php';

// 1. CEK APAKAH KERANJANG KOSONG
$keranjang_kosong = true;
if (isset($_SESSION['keranjang']) && !empty($_SESSION['keranjang'])) {
    $keranjang_kosong = false;
}

$total_bayar = 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Kantin Kita</title>
    <style>
        :root {
            --primary-blue: #50c8ff;
            --dark-blue: #3da8db;
            --accent-orange: #ff9800;
            --bg-light: #f4f7f6;
            --white: #ffffff;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: var(--bg-light); }

        .navbar { background: var(--primary-blue); color: white; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; }
        .navbar a { color: white; text-decoration: none; font-weight: bold; margin-left: 20px; }

        .container { padding: 40px 5%; max-width: 900px; margin: auto; }
        .header-page { margin-bottom: 30px; display: flex; justify-content: space-between; align-items: flex-end; }
        
        /* List Item Keranjang */
        .cart-item { 
            background: white; padding: 20px; border-radius: 15px; 
            display: flex; align-items: center; margin-bottom: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        .item-info { flex-grow: 1; }
        .item-name { font-size: 18px; font-weight: bold; color: #333; }
        .item-price { color: #888; font-size: 14px; }
        .item-subtotal { font-weight: 800; color: var(--dark-blue); font-size: 18px; margin-left: 20px; }

        .btn-hapus { color: #ff5e5e; text-decoration: none; font-size: 24px; margin-left: 20px; line-height: 1; }

        /* Total Section */
        .summary-card { 
            background: white; padding: 25px; border-radius: 15px; 
            margin-top: 30px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .total-row { display: flex; justify-content: space-between; font-size: 20px; font-weight: bold; margin-bottom: 20px; }
        
        .btn-checkout { 
            display: block; width: 100%; background: var(--accent-orange); 
            color: white; text-align: center; padding: 15px; 
            border-radius: 10px; text-decoration: none; font-weight: bold; font-size: 18px;
            transition: 0.3s;
        }
        .btn-checkout:hover { background: #e68a00; transform: translateY(-3px); }

        .aksi-header { display: flex; gap: 15px; align-items: center; }
        .btn-tambah-lagi { color: var(--dark-blue); text-decoration: none; font-size: 14px; font-weight: bold; }
        .btn-kosongkan { color: #ff5e5e; text-decoration: none; font-size: 14px; }
        
        .empty-cart { text-align: center; padding: 100px 0; color: #aaa; }
    </style>
</head>
<body>

<div class="navbar">
    <h2>🛒 Keranjang Kita</h2>
    <div>
        <a href="dashboard.php">Home</a>
    </div>
</div>

<div class="container">
    <div class="header-page">
        <h1>Keranjang Belanja</h1>
        <?php if (!$keranjang_kosong) : ?>
            <div class="aksi-header">
                <a href="menu_kantin.php?id=<?= $_SESSION['id_kantin_aktif'] ?>" class="btn-tambah-lagi">+ Tambah Menu Lain</a>
                <a href="hapus_keranjang.php?semua=1" class="btn-kosongkan" onclick="return confirm('Kosongkan semua isi keranjang?')">Kosongkan</a>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($keranjang_kosong) : ?>
        <div class="empty-cart">
            <p style="font-size: 60px;">🛒</p>
            <h3>Keranjangmu masih kosong</h3>
            <p>Sepertinya perutmu butuh asupan jajanan enak.</p>
            <a href="dashboard.php" style="display:inline-block; margin-top:20px; background: var(--primary-blue); color:white; padding: 10px 25px; border-radius: 8px; text-decoration:none; font-weight:bold;">Cari Makanan Sekarang</a>
        </div>
    <?php else : ?>
        
        <div class="cart-list">
            <?php 
            foreach ($_SESSION['keranjang'] as $id_menu => $jumlah) : 
                $ambil_data = mysqli_query($koneksi, "SELECT * FROM menu WHERE id_menu = '$id_menu'");
                $m = mysqli_fetch_assoc($ambil_data);
                $subtotal = $m['harga'] * $jumlah;
                $total_bayar += $subtotal;
            ?>
            <div class="cart-item">
                <div class="item-info">
                    <p class="item-name"><?= $m['nama_menu'] ?></p>
                    <p class="item-price">Rp <?= number_format($m['harga'], 0, ',', '.') ?> x <?= $jumlah ?></p>
                </div>
                <div class="item-subtotal">
                    Rp <?= number_format($subtotal, 0, ',', '.') ?>
                </div>
                <a href="hapus_keranjang.php?id=<?= $id_menu ?>" class="btn-hapus" title="Hapus Item" onclick="return confirm('Hapus menu ini?')">×</a>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="summary-card">
            <div class="total-row">
                <span>Total Bayar:</span>
                <span style="color: #2e7d32; font-size: 26px;">Rp <?= number_format($total_bayar, 0, ',', '.') ?></span>
            </div>
            <a href="checkout_proses.php" class="btn-checkout" onclick="return confirm('Yakin ingin memesan sekarang?')">KONFIRMASI PESANAN</a>
        </div>

    <?php endif; ?>
</div>

</body>
</html>