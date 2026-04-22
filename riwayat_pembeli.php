<?php
session_start();
include 'config.php';

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit();
}

$id_user = $_SESSION['id_user'];

$query = mysqli_query($koneksi, "
    SELECT transaksi.*, kantin.nama_kantin 
    FROM transaksi 
    JOIN kantin ON transaksi.id_kantin = kantin.id_kantin 
    WHERE transaksi.id_user = '$id_user'
    ORDER BY transaksi.created_at DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Riwayat</title>

<style>
body { font-family:sans-serif; margin:0; background:#f5f5f5; }

.header {
    background:#50c8ff;
    padding:15px;
    color:white;
    text-align:center;
}

/* BACK BUTTON */
.back {
    display:inline-block;
    margin:10px;
    padding:6px 12px;
    background:#50c8ff;
    color:white;
    text-decoration:none;
    border-radius:8px;
    font-size:12px;
}

/* CARD */
.card {
    background:white;
    margin:10px;
    padding:15px;
    border-radius:10px;
    box-shadow:0 2px 5px rgba(0,0,0,0.1);
}

/* STATUS */
.status {
    font-size:11px;
    padding:4px 10px;
    border-radius:10px;
    color:white;
}
.pending { background:orange; }
.diproses { background:blue; }
.selesai { background:green; }
.dibatalkan { background:red; }

/* BUTTON */
.btn {
    display:inline-block;
    margin-top:8px;
    padding:5px 10px;
    background:#50c8ff;
    color:white;
    text-decoration:none;
    border-radius:5px;
    font-size:12px;
}
</style>
</head>

<body>

<div class="header">
    <h3>Riwayat Pesanan</h3>
</div>

<a href="dashboard.php" class="back">← Kembali</a>

<?php if(mysqli_num_rows($query) > 0): ?>
    <?php while($r = mysqli_fetch_assoc($query)) : ?>
        <div class="card">
            <b><?= $r['nama_kantin'] ?></b><br>
            Rp <?= number_format($r['total_harga'],0,',','.') ?><br>
            <small><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></small><br><br>

            <span class="status <?= $r['status'] ?>">
                <?= $r['status'] ?>
            </span><br>

            <a href="detail_pesanan_pembeli.php?id=<?= $r['id_transaksi'] ?>" class="btn">
                Detail
            </a>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <p style="text-align:center; margin-top:30px;">
        Belum ada pesanan
    </p>
<?php endif; ?>

</body>
</html>