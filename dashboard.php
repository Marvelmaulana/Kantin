<?php
session_start();
include 'config.php';

// CEK LOGIN
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 'pembeli') {
    header("Location: login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

// DATA USER
$user = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM users WHERE id_user='$id_user'"));

// NOTIF (Menghitung pesan/notifikasi yang belum dibaca)
$jml_notif = 0;
$cek = mysqli_query($koneksi, "SHOW TABLES LIKE 'notifikasi'");
if(mysqli_num_rows($cek) > 0){
    $notif = mysqli_query($koneksi, "SELECT * FROM notifikasi WHERE id_user='$id_user' AND status=0");
    $jml_notif = mysqli_num_rows($notif);
}

// SEARCH & KATEGORI
$search   = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$kategori = isset($_GET['kategori']) ? mysqli_real_escape_string($koneksi, $_GET['kategori']) : '';

// QUERY MENU
$sql = "SELECT * FROM menu WHERE 1";
if (!empty($search)) {
    $sql .= " AND nama_menu LIKE '%$search%'";
}
if (!empty($kategori)) {
    $sql .= " AND LOWER(kategori) = LOWER('$kategori')";
}
$sql .= " ORDER BY id_menu DESC";
$query_menu = mysqli_query($koneksi, $sql);

// KANTIN
$query_kantin = mysqli_query($koneksi, "SELECT * FROM kantin");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kantin Online</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    
    <style>
        /* CSS DASAR */
        :root {
            --primary: #ab2d00;      /* Merah Bata */
            --bg-page: #fff4f3;      /* Krem Lembut */
            --white: #ffffff;
            --text: #4e2120;
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: var(--bg-page);
            color: var(--text);
            padding-bottom: 100px; /* Supaya konten tidak tertutup nav bawah */
        }

        /* HEADER */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
        }
        .header h1 { font-size: 20px; color: var(--primary); margin: 0; }
        .cart-icon { position: relative; color: var(--primary); }
        .badge {
            position: absolute; top: -5px; right: -5px;
            background: var(--primary); color: white;
            font-size: 10px; padding: 2px 6px; border-radius: 50%;
        }

        /* SEARCH BAR */
        .search-container { padding: 0 20px; margin-bottom: 20px; }
        .search-container input {
            width: 100%; padding: 15px 20px; border-radius: 30px;
            border: none; box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            box-sizing: border-box; outline: none;
        }

        /* BANNER PROMO */
        .promo-card {
            margin: 0 20px; padding: 25px;
            background: linear-gradient(135deg, #ab2d00, #ff7851);
            border-radius: 20px; color: white;
            position: relative; overflow: hidden;
        }
        .promo-card h2 { margin: 5px 0; font-size: 22px; }
        .promo-card p { font-size: 12px; opacity: 0.8; margin-bottom: 15px; }
        .btn-promo {
            background: white; color: var(--primary);
            padding: 8px 15px; border-radius: 15px;
            text-decoration: none; font-size: 12px; font-weight: bold;
        }

        /* KATEGORI (CHIPS) */
        .category-row {
            display: flex; gap: 15px; padding: 20px;
            overflow-x: auto; /* Bisa digeser ke samping jika penuh */
            scrollbar-width: none; /* Sembunyikan scrollbar */
        }
        .cat-item {
            text-align: center; text-decoration: none; color: inherit; min-width: 70px;
        }
        .cat-box {
            width: 60px; height: 60px; background: white;
            border-radius: 20px; display: flex; align-items: center;
            justify-content: center; margin-bottom: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.04);
            transition: 0.3s;
        }
        .cat-item.active .cat-box { background: var(--primary); color: white; }
        .cat-item span { font-size: 11px; font-weight: bold; }

        /* GRID MENU */
        .menu-grid {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 15px; padding: 0 20px;
        }
        .card {
            background: white; border-radius: 25px;
            overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            position: relative;
        }
        .card img { width: 100%; height: 110px; object-fit: cover; }
        .card-body { padding: 12px; }
        .card-body b { font-size: 14px; display: block; margin-bottom: 4px; }
        .card-body .price { color: var(--primary); font-weight: bold; font-size: 14px; }
        .btn-add {
            position: absolute; bottom: 12px; right: 12px;
            width: 32px; height: 32px; background: #ffd2d0;
            color: var(--primary); border-radius: 50%;
            text-align: center; line-height: 32px;
            text-decoration: none; font-weight: bold;
        }

        /* NAVIGASI BAWAH */
        .nav-bottom {
            position: fixed; bottom: 15px; left: 50%;
            transform: translateX(-50%); width: 90%;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px); height: 70px;
            border-radius: 35px; display: flex;
            justify-content: space-around; align-items: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .nav-link { color: #ccc; text-decoration: none; display: flex; flex-direction: column; align-items: center; }
        .nav-link.active { color: var(--primary); }
    </style>
</head>

<body>

    <div class="header">
        <span class="material-symbols-outlined">menu</span>
        <h1>Kantin Online</h1>
        <div class="cart-icon">
            <span class="material-symbols-outlined">shopping_cart</span>
            <?php if($jml_notif > 0): ?>
                <span class="badge"><?= $jml_notif ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="search-container">
        <form method="GET">
            <input type="text" name="search" placeholder="Cari makan siang apa hari ini?" value="<?= htmlspecialchars($search) ?>">
        </form>
    </div>

    <div class="promo-card">
        <small style="background: #ffd2d0; color: #ab2d00; padding: 2px 8px; border-radius: 10px; font-weight: bold;">PROMO</small>
        <h2>Diskon Hingga 50%</h2>
        <p>Halo, <?= $user['username'] ?>! Nikmati menu spesial hari ini.</p>
        <a href="#" class="btn-promo">PESAN SEKARANG</a>
    </div>

    <div class="category-row">
        <a href="dashboard.php" class="cat-item <?= ($kategori=='')?'active':'' ?>">
            <div class="cat-box"><span class="material-symbols-outlined">grid_view</span></div>
            <span>Semua</span>
        </a>
        <a href="?kategori=Makanan" class="cat-item <?= (strtolower($kategori)=='makanan')?'active':'' ?>">
            <div class="cat-box"><span class="material-symbols-outlined">restaurant</span></div>
            <span>Makanan</span>
        </a>
        <a href="?kategori=Minuman" class="cat-item <?= (strtolower($kategori)=='minuman')?'active':'' ?>">
            <div class="cat-box"><span class="material-symbols-outlined">local_drink</span></div>
            <span>Minuman</span>
        </a>
        <a href="?kategori=Cemilan" class="cat-item <?= (strtolower($kategori)=='cemilan')?'active':'' ?>">
            <div class="cat-box"><span class="material-symbols-outlined">icecream</span></div>
            <span>Cemilan</span>
        </a>
    </div>

    <h4 style="padding-left: 20px; margin-bottom: 10px;">Rekomendasi</h4>
    <div class="menu-grid">
        <?php while($m = mysqli_fetch_assoc($query_menu)) : ?>
        <div class="card">
            <img src="uploads/<?= $m['foto_menu'] ?>" onerror="this.src='https://via.placeholder.com/150'">
            <div class="card-body">
                <b><?= $m['nama_menu'] ?></b>
                <span class="price">Rp <?= number_format($m['harga']) ?></span>
                <a href="tambah_keranjang.php?id=<?= $m['id_menu'] ?>" class="btn-add">+</a>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <h4 style="padding-left: 20px; margin-top: 25px;">Daftar Kantin</h4>
    <?php while($k = mysqli_fetch_assoc($query_kantin)) : ?>
    <a href="menu_kantin.php?id=<?= $k['id_kantin'] ?>" style="display: block; margin: 10px 20px; padding: 15px; background: white; border-radius: 20px; text-decoration: none; color: inherit; font-weight: bold; box-shadow: 0 4px 10px rgba(0,0,0,0.02);">
        🏪 Kantin <?= $k['id_kantin'] ?>
    </a>
    <?php endwhile; ?>

    <div class="nav-bottom">
        <a href="dashboard.php" class="nav-link active">
            <span class="material-symbols-outlined">home</span>
            <small>Home</small>
        </a>
        <a href="keranjang.php" class="nav-link">
            <span class="material-symbols-outlined">shopping_bag</span>
            <small>Pesanan</small>
        </a>
        <a href="riwayat_pembeli.php" class="nav-link">
            <span class="material-symbols-outlined">history</span>
            <small>Riwayat</small>
        </a>
        <a href="profil.php" class="nav-link">
            <span class="material-symbols-outlined">person</span>
            <small>Profil</small>
        </a>
    </div>

</body>
</html>