<?php
session_start();
include(__DIR__ . '/../../config/config.php');

// 1. PROTEKSI HALAMAN
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'penjual') {
    header("Location: login.php");
    exit;
}

$id_k = $_SESSION['id_kantin'] ?? 0;

// 2. AMBIL DATA RIWAYAT (Selesai & Dibatalkan)
$query = mysqli_query($koneksi, "SELECT pesanan.*, users.username 
                                 FROM pesanan 
                                 JOIN users ON pesanan.id_user = users.id_user 
                                 WHERE pesanan.id_kantin = '$id_k' 
                                 AND pesanan.status IN ('Selesai', 'Dibatalkan') 
                                 ORDER BY pesanan.tanggal DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Penjualan - Kantin Kita</title>
    <style>
        :root {
            --primary-blue: #50c8ff;
            --dark-blue: #3da8db;
            --bg-light: #f4f7f6;
            --white: #ffffff;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: var(--bg-light); display: flex; }

        .sidebar { 
            width: 260px; height: 100vh; background: var(--primary-blue); 
            color: white; padding: 25px 20px; position: fixed; 
        }
        .sidebar a { 
            display: block; color: white; text-decoration: none; 
            padding: 12px 15px; margin-bottom: 8px; border-radius: 8px; 
        }
        .sidebar a.active { background: var(--dark-blue); font-weight: bold; }

        .main-content { margin-left: 260px; padding: 40px; width: calc(100% - 260px); }
        
        .table-container { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        th { background: var(--primary-blue); color: white; padding: 15px; text-align: left; font-size: 14px; }
        td { padding: 15px; border-bottom: 1px solid #eee; font-size: 14px; color: #444; }

        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; }
        .status-selesai { background: #e8f5e9; color: #2e7d32; }
        .status-batal { background: #ffebee; color: #c62828; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>Kantin Kita</h2>
        <br>
        <a href="dashboard_penjual.php">Dashboard</a>
        <a href="menu_penjual.php">Kelola Menu</a>
        <a href="pesanan_masuk.php">Pesanan Masuk</a>
        <a href="riwayat_penjual.php" class="active">Riwayat Pesanan</a>
        <a href="logout.php" style="margin-top:20px; background:#ff5e5e;">Logout</a>
    </div>

    <div class="main-content">
        <h1>Riwayat Penjualan</h1>
        <p>Daftar pesanan yang telah selesai atau dibatalkan.</p>
        <br>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tanggal</th>
                        <th>Pembeli</th>
                        <th>Total Bayar</th>
                        <th>Status</th>
                        <th>Metode</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($query) > 0): ?>
                        <?php while($p = mysqli_fetch_assoc($query)): 
                            $st_class = ($p['status'] == 'Selesai') ? 'status-selesai' : 'status-batal';
                        ?>
                            <tr>
                                <td><b>#<?= $p['id_pesanan'] ?></b></td>
                                <td><?= date('d M Y, H:i', strtotime($p['tanggal'])) ?></td>
                                <td><?= htmlspecialchars($p['username']) ?></td>
                                <td>Rp <?= number_format($p['total_harga']) ?></td>
                                <td><span class="status-badge <?= $st_class ?>"><?= strtoupper($p['status']) ?></span></td>
                                <td><?= $p['metode_bayar'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 30px; color: #999;">Belum ada riwayat transaksi.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>