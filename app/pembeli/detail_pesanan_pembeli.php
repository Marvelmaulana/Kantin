<?php
session_start();
include(__DIR__ . '/../../config/config.php');

// 1. PROTEKSI: Cek Login
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

// 2. AMBIL ID TRANSAKSI DARI URL
if (!isset($_GET['id'])) {
    header("Location: riwayat_pembeli.php");
    exit;
}

$id_transaksi = $_GET['id'];
$id_user = $_SESSION['id_user'];

// 3. AMBIL DATA TRANSAKSI UTAMA (Dan cek apakah ini benar milik user yang login)
$query_t = mysqli_query($koneksi, "SELECT transaksi.*, users.username as nama_kantin 
    FROM transaksi 
    JOIN users ON transaksi.id_kantin = users.id_user 
    WHERE transaksi.id_transaksi = '$id_transaksi' AND transaksi.id_user = '$id_user'");

$data_t = mysqli_fetch_assoc($query_t);

// Jika transaksi tidak ditemukan atau bukan milik user ini
if (!$data_t) {
    echo "<script>alert('Data tidak ditemukan!'); window.location='riwayat_pembeli.php';</script>";
    exit;
}

// 4. AMBIL RINCIAN MENU (DARI TABEL DETAIL_TRANSAKSI)
$query_detail = mysqli_query($koneksi, "SELECT detail_transaksi.*, menu.nama_menu 
    FROM detail_transaksi 
    JOIN menu ON detail_transaksi.id_menu = menu.id_menu 
    WHERE detail_transaksi.id_transaksi = '$id_transaksi'");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #<?= $id_transaksi ?></title>
    <style>
        :root {
            --primary-blue: #50c8ff;
            --bg-light: #f4f7f6;
            --white: #ffffff;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: var(--bg-light); padding: 20px; }

        .container { max-width: 700px; margin: 30px auto; background: var(--white); border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        
        .header { background: var(--primary-blue); color: white; padding: 30px; text-align: center; }
        .header h2 { margin-bottom: 5px; }
        
        .content { padding: 30px; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; border-bottom: 1px dashed #ddd; padding-bottom: 20px; }
        .info-label { font-size: 12px; color: #888; text-transform: uppercase; margin-bottom: 3px; }
        .info-value { font-weight: bold; color: #333; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { text-align: left; color: #888; font-size: 13px; padding-bottom: 10px; border-bottom: 2px solid #f4f7f6; }
        td { padding: 15px 0; border-bottom: 1px solid #f4f7f6; }
        
        .total-section { border-top: 2px solid #333; padding-top: 20px; display: flex; justify-content: space-between; align-items: center; }
        .total-label { font-size: 18px; font-weight: bold; }
        .total-price { font-size: 24px; font-weight: 800; color: var(--primary-blue); }

        .btn-kembali { display: block; text-align: center; margin-top: 30px; text-decoration: none; color: #888; font-weight: bold; transition: 0.3s; }
        .btn-kembali:hover { color: var(--primary-blue); }

        /* Status Badge */
        .status { padding: 5px 15px; border-radius: 50px; font-size: 12px; font-weight: bold; }
        .pending { background: #fff3e0; color: #ef6c00; }
        .diproses { background: #e3f2fd; color: #1565c0; }
        .selesai { background: #e8f5e9; color: #2e7d32; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h2>Struk Pesanan</h2>
        <p>ID Transaksi: #<?= $data_t['id_transaksi'] ?></p>
    </div>

    <div class="content">
        <div class="info-grid">
            <div>
                <p class="info-label">Kantin</p>
                <p class="info-value"><?= strtoupper($data_t['nama_kantin']) ?></p>
            </div>
            <div>
                <p class="info-label">Status</p>
                <span class="status <?= $data_t['status'] ?>"><?= strtoupper($data_t['status']) ?></span>
            </div>
            <div>
                <p class="info-label">Tanggal</p>
                <p class="info-value"><?= date('d M Y, H:i', strtotime($data_t['created_at'])) ?></p>
            </div>
            <div>
                <p class="info-label">Metode</p>
                <p class="info-value"><?= ucfirst($data_t['metode_pembayaran']) ?></p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Menu</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php while($item = mysqli_fetch_assoc($query_detail)) : ?>
                <tr>
                    <td>
                        <p style="font-weight: bold; color: #333;"><?= $item['nama_menu'] ?></p>
                    </td>
                    <td style="text-align: center;">x <?= $item['jumlah'] ?></td>
                    <td style="text-align: right; font-weight: bold;">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="total-section">
            <span class="total-label">TOTAL BAYAR</span>
            <span class="total-price">Rp <?= number_format($data_t['total'], 0, ',', '.') ?></span>
        </div>

        <a href="riwayat_pembeli.php" class="btn-kembali">← Kembali ke Riwayat</a>
    </div>
</div>

</body>
</html>