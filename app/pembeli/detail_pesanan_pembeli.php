<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');

if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit;
}

$id_pesanan = (int)($_GET['id'] ?? 0);
$id_user = (int)$_SESSION['id_user'];

if ($id_pesanan <= 0) {
    header("Location: riwayat_pembeli.php");
    exit;
}

$query_t = mysqli_query($koneksi, "
    SELECT p.*, k.nama_kantin
    FROM pesanan p
    JOIN kantin k ON p.id_kantin = k.id_kantin
    WHERE p.id_pesanan = $id_pesanan
      AND p.id_user = $id_user
    LIMIT 1
");
$data_t = mysqli_fetch_assoc($query_t);

if (!$data_t) {
    echo "<script>alert('Data tidak ditemukan!'); window.location='riwayat_pembeli.php';</script>";
    exit;
}

$query_detail = mysqli_query($koneksi, "
    SELECT dp.*, COALESCE(dp.nama_menu, m.nama_menu) AS nama_menu
    FROM detail_pesanan dp
    JOIN menu m ON dp.id_menu = m.id_menu
    WHERE dp.id_pesanan = $id_pesanan
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #<?= $id_pesanan ?></title>
    <style>
        :root { --primary: #b22204; --bg-light: #fff8f6; --white: #ffffff; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: var(--bg-light); padding: 20px; color: #2f241f; }
        .container { max-width: 760px; margin: 30px auto; background: var(--white); border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        .header { background: var(--primary); color: white; padding: 30px; text-align: center; }
        .content { padding: 30px; }
        .info-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; margin-bottom: 30px; border-bottom: 1px dashed #ddd; padding-bottom: 20px; }
        .info-label { font-size: 12px; color: #888; text-transform: uppercase; margin-bottom: 3px; }
        .info-value { font-weight: bold; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { text-align: left; color: #888; font-size: 13px; padding-bottom: 10px; border-bottom: 2px solid #f4f1ef; }
        td { padding: 15px 0; border-bottom: 1px solid #f4f1ef; }
        .total-section { border-top: 2px solid #333; padding-top: 20px; display: flex; justify-content: space-between; align-items: center; }
        .total-label { font-size: 18px; font-weight: bold; }
        .total-price { font-size: 24px; font-weight: 800; color: var(--primary); }
        .btn-kembali { display: block; text-align: center; margin-top: 30px; text-decoration: none; color: #8a6b61; font-weight: bold; }
        .status { padding: 5px 15px; border-radius: 50px; font-size: 12px; font-weight: bold; background: #fff3e0; color: #a34800; }
        @media (max-width: 640px) { .info-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>Detail Pesanan</h2>
        <p>Kode: <?= htmlspecialchars($data_t['kode_pesanan'] ?? ('#' . $id_pesanan)) ?></p>
    </div>

    <div class="content">
        <div class="info-grid">
            <div>
                <p class="info-label">Kantin</p>
                <p class="info-value"><?= htmlspecialchars(strtoupper($data_t['nama_kantin'])) ?></p>
            </div>
            <div>
                <p class="info-label">Status</p>
                <span class="status"><?= htmlspecialchars(strtoupper($data_t['status'])) ?></span>
            </div>
            <div>
                <p class="info-label">Tanggal</p>
                <p class="info-value"><?= date('d M Y, H:i', strtotime($data_t['tanggal'])) ?></p>
            </div>
            <div>
                <p class="info-label">Metode</p>
                <p class="info-value"><?= htmlspecialchars(ucfirst($data_t['metode_pembayaran'] ?? '-')) ?></p>
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
                <?php while ($item = mysqli_fetch_assoc($query_detail)) : ?>
                <tr>
                    <td><strong><?= htmlspecialchars($item['nama_menu']) ?></strong></td>
                    <td style="text-align: center;">x <?= (int)$item['qty'] ?></td>
                    <td style="text-align: right; font-weight: bold;">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="total-section">
            <span class="total-label">TOTAL BAYAR</span>
            <span class="total-price">Rp <?= number_format($data_t['total_harga'], 0, ',', '.') ?></span>
        </div>

        <a href="riwayat_pembeli.php" class="btn-kembali">Kembali ke Riwayat</a>
    </div>
</div>
</body>
</html>
