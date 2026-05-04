<?php
session_start();
include(__DIR__ . '/../../config/config.php');

$id_kantin = $_GET['id'];
$ambil_kantin = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id_kantin'");
$kantin = mysqli_fetch_assoc($ambil_kantin);

// Ambil Menu
$ambil_menu = mysqli_query($koneksi, "SELECT * FROM menu WHERE id_kantin = '$id_kantin'");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kantin - <?= $kantin['username'] ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --blue: #50c8ff; --orange: #ffb74d; --bg: #f8f9fa; }
        body { font-family: 'Poppins', sans-serif; background: var(--bg); margin: 0; padding-bottom: 80px; }
        
        /* Header Kantin */
        .header-kantin { background: white; padding: 20px; border-bottom-left-radius: 30px; border-bottom-right-radius: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .back-btn { font-size: 20px; color: var(--blue); text-decoration: none; }
        .info-kantin { display: flex; align-items: center; margin-top: 15px; gap: 15px; }
        .img-kantin { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid var(--blue); }

        /* Menu Grid */
        .container { padding: 20px; }
        .section-title { font-weight: bold; font-size: 18px; margin: 20px 0; display: flex; justify-content: space-between; }
        .grid-menu { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .card-menu { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 5px 10px rgba(0,0,0,0.03); position: relative; }
        .card-menu img { width: 100%; height: 120px; object-fit: cover; }
        .card-info { padding: 12px; }
        .card-info h4 { margin: 0; font-size: 14px; color: #333; }
        .card-info p { margin: 5px 0; color: var(--blue); font-weight: bold; font-size: 13px; }
        .btn-add { position: absolute; bottom: 10px; right: 10px; background: var(--blue); color: white; border: none; width: 30px; height: 30px; border-radius: 10px; cursor: pointer; }

        /* POPUP STYLES */
        .overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1001; align-items: flex-end; }
        .popup { background: white; width: 100%; border-top-left-radius: 30px; border-top-right-radius: 30px; padding: 30px 20px; transform: translateY(100%); transition: 0.3s; }
        .popup.active { transform: translateY(0); }
        .qty-control { display: flex; align-items: center; justify-content: center; gap: 20px; margin: 20px 0; }
        .btn-qty { width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--blue); background: none; color: var(--blue); font-size: 20px; font-weight: bold; cursor: pointer; }
        .btn-submit { background: var(--orange); color: white; width: 100%; border: none; padding: 15px; border-radius: 15px; font-weight: bold; font-size: 16px; cursor: pointer; }

        /* Bottom Nav */
        .bottom-nav { position: fixed; bottom: 0; width: 100%; background: white; display: flex; justify-content: space-around; padding: 15px 0; box-shadow: 0 -5px 20px rgba(0,0,0,0.05); }
        .nav-item { color: #ccc; font-size: 20px; text-decoration: none; }
        .nav-item.active { color: var(--blue); }
    </style>
</head>
<body>

<div class="header-kantin">
    <a href="dashboard_pembeli.php" class="back-btn"><i class="fas fa-arrow-left"></i> Menu</a>
    <div class="info-kantin">
        <img src="uploads/<?= $kantin['foto'] ?>" class="img-kantin">
        <div>
            <h2 style="margin:0; font-size:18px;"><?= $kantin['username'] ?></h2>
            <span style="color:#888; font-size:12px;">Buka Jam 08:00 - 16:00</span>
        </div>
    </div>
</div>

<div class="container">
    <div class="section-title">Makanan <i class="fas fa-chevron-right" style="font-size:12px; color:#ccc;"></i></div>
    <div class="grid-menu">
        <?php while($m = mysqli_fetch_assoc($ambil_menu)) : ?>
        <div class="card-menu">
            <img src="uploads/<?= $m['foto_menu'] ?>">
            <div class="card-info">
                <h4><?= $m['nama_menu'] ?></h4>
                <p>Rp <?= number_format($m['harga'], 0, ',', '.') ?></p>
                <button class="btn-add" onclick="openPopup('<?= $m['id_menu'] ?>', '<?= $m['nama_menu'] ?>')">+</button>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<div class="overlay" id="overlay" onclick="closePopup()">
    <div class="popup" id="popup" onclick="event.stopPropagation()">
        <center>
            <h2 id="p_nama">Ayam Geprek</h2>
            <p style="color:#888;">Tentukan jumlah pesanan</p>
        </center>

        <form action="tambah_keranjang.php" method="POST">
            <input type="hidden" name="id_menu" id="p_id">
            <input type="hidden" name="id_kantin" value="<?= $id_kantin ?>">
            <input type="hidden" name="aksi" value="tambah_keranjang">
            
            <div class="qty-control">
                <button type="button" class="btn-qty" onclick="changeQty(-1)">-</button>
                <b id="display_qty" style="font-size: 24px;">1</b>
                <input type="hidden" name="jumlah" id="input_qty" value="1">
                <button type="button" class="btn-qty" onclick="changeQty(1)">+</button>
            </div>

            <button type="submit" class="btn-submit">Tambah ke keranjang</button>
        </center>
    </div>
</div>

<div class="bottom-nav">
    <a href="dashboard_pembeli.php" class="nav-item"><i class="fas fa-home"></i></a>
    <a href="keranjang.php" class="nav-item active"><i class="fas fa-shopping-bag"></i></a>
    <a href="#" class="nav-item"><i class="fas fa-comment-dots"></i></a>
    <a href="#" class="nav-item"><i class="fas fa-user"></i></a>
</div>

<script>
    function openPopup(id, nama) {
        document.getElementById('p_id').value = id;
        document.getElementById('p_nama').innerText = nama;
        document.getElementById('overlay').style.display = 'flex';
        setTimeout(() => { document.getElementById('popup').classList.add('active'); }, 10);
    }

    function closePopup() {
        document.getElementById('popup').classList.remove('active');
        setTimeout(() => { document.getElementById('overlay').style.display = 'none'; }, 300);
    }

    function changeQty(val) {
        let display = document.getElementById('display_qty');
        let input = document.getElementById('input_qty');
        let current = parseInt(display.innerText);
        let result = current + val;
        if(result < 1) result = 1;
        display.innerText = result;
        input.value = result;
    }
</script>

</body>
</html>