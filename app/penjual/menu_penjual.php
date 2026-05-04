<?php
session_start();
include(__DIR__ . '/../../config/config.php');

// 1. PROTEKSI HALAMAN
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'penjual') {
    header("Location: login.php");
    exit;
}

// Pastikan id_kantin tersedia
$id_k = $_SESSION['id_kantin'] ?? 0;

// 2. AMBIL DATA MENU
// Query hanya mengambil menu milik kantin yang sedang login
$query = mysqli_query($koneksi, "SELECT * FROM menu WHERE id_kantin = '$id_k' ORDER BY id_menu DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Menu - Kantin Kita</title>
    <style>
        :root {
            --primary-blue: #50c8ff;
            --dark-blue: #3da8db;
            --accent-orange: #ff9800;
            --bg-light: #f8fbff;
            --white: #ffffff;
            --text-dark: #333;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { background-color: var(--bg-light); display: flex; }

        /* --- SIDEBAR --- */
        .sidebar { 
            width: 260px; height: 100vh; background: var(--primary-blue); 
            color: var(--white); padding: 25px 20px; position: fixed; z-index: 1000;
        }
        .sidebar h2 { text-align: center; margin-bottom: 30px; font-size: 22px; font-weight: bold; }
        .user-info { text-align: center; background: rgba(255, 255, 255, 0.2); padding: 15px; border-radius: 12px; margin-bottom: 25px; }

        .sidebar a { 
            display: block; color: white; text-decoration: none; 
            padding: 12px 15px; margin-bottom: 8px; border-radius: 8px; transition: 0.3s; 
        }
        .sidebar a:hover, .sidebar a.active { background-color: var(--dark-blue); }
        .sidebar a.logout { margin-top: 50px; background: #ff5e5e; }

        /* --- MAIN CONTENT --- */
        .main-content { margin-left: 260px; padding: 40px; width: calc(100% - 260px); }
        .main-content h1 { color: var(--text-dark); font-size: 28px; margin-bottom: 20px; border-left: 5px solid var(--accent-orange); padding-left: 15px; }

        .btn-tambah {
            display: inline-block; background: var(--accent-orange); color: white;
            padding: 12px 25px; border-radius: 30px; text-decoration: none;
            font-weight: bold; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3); transition: 0.3s;
        }
        .btn-tambah:hover { transform: translateY(-3px); opacity: 0.9; }

        /* --- TABEL --- */
        .table-container { background: var(--white); border-radius: 15px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; }
        thead { background-color: var(--primary-blue); color: white; }
        th { padding: 18px; text-align: left; font-size: 13px; text-transform: uppercase; }
        td { padding: 15px 18px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; color: #555; }
        
        .img-menu { border-radius: 10px; object-fit: cover; border: 1px solid #ddd; background: #eee; }

        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; }
        .status-tersedia { background: #e8f5e9; color: #2e7d32; }
        .status-habis { background: #ffebee; color: #c62828; }

        .btn-edit { color: #3f51b5; text-decoration: none; font-weight: bold; margin-right: 15px; }
        .btn-hapus { color: #f44336; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>Kantin Kita</h2>
        <div class="user-info">
            <p style="font-size: 11px; opacity: 0.8;">Selamat Datang,</p>
            <b><?= htmlspecialchars($_SESSION['username']) ?></b>
        </div>
        <a href="dashboard_penjual.php">Dashboard</a>
        <a href="menu_penjual.php" class="active">Kelola Menu</a>
        <a href="pesanan_masuk.php">Pesanan Masuk</a>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="main-content">
        <h1>Kelola Menu Kantin</h1>
        
        <a href="tambah_menu.php" class="btn-tambah">+ Tambah Menu Baru</a>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Foto</th>
                        <th>Nama Menu</th>
                        <th>Harga</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1; 
                    if(mysqli_num_rows($query) > 0) :
                        while($m = mysqli_fetch_assoc($query)) : 
                            // Path gambar: naik 2 kali dari app/penjual ke root, lalu masuk uploads
                            $path_gambar = "../../uploads/" . $m['foto'];
                    ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td>
                            <img src="<?= $path_gambar ?>" width="60" height="60" class="img-menu" 
                                 onerror="this.src='https://via.placeholder.com/60?text=No+Img'">
                        </td>
                        <td><b><?= htmlspecialchars($m['nama_menu']); ?></b></td>
                        <td>Rp <?= number_format($m['harga'], 0, ',', '.'); ?></td>
                        <td>
                            <?php 
                                $status_class = (strtolower($m['status']) == 'tersedia') ? 'status-tersedia' : 'status-habis';
                            ?>
                            <span class="status-badge <?= $status_class ?>">
                                <?= strtoupper($m['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="edit_menu.php?id=<?= $m['id_menu']; ?>" class="btn-edit">Edit</a>
                            <a href="hapus_menu.php?id=<?= $m['id_menu']; ?>" class="btn-hapus" 
                               onclick="return confirm('Yakin ingin menghapus menu ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #999;">
                            Belum ada menu. Silakan tambah menu baru.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>