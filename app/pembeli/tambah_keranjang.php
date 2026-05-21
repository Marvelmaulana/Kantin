<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');
kk_ensure_buyer_schema($koneksi);

if (!isset($_SESSION['id_user'])) {
    echo "<script>alert('Silakan login terlebih dahulu'); window.location='../auth/login.php';</script>";
    exit();
}

$id_user = (int)$_SESSION['id_user'];
$id_menu = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$qty     = isset($_GET['qty']) ? (int)$_GET['qty'] : 1;
$catatan = isset($_GET['catatan']) ? mysqli_real_escape_string($koneksi, $_GET['catatan']) : '';
$opsi    = isset($_GET['opsi']) ? mysqli_real_escape_string($koneksi, $_GET['opsi']) : '';
$action  = isset($_GET['action']) ? $_GET['action'] : 'cart';

if (empty($id_menu)) {
    header("Location: dashboard.php");
    exit();
}

if (!kk_verify_csrf($_GET['csrf_token'] ?? '')) {
    kk_abort_csrf();
}

// Cek stok menu
$cek_menu = mysqli_query($koneksi, "
    SELECT menu.*, kantin.nama_kantin, kantin.jam_buka, kantin.jam_tutup, kantin.status_buka
    FROM menu
    JOIN kantin ON menu.id_kantin = kantin.id_kantin
    WHERE menu.id_menu = $id_menu
");
if (!$cek_menu || mysqli_num_rows($cek_menu) == 0) {
    echo "<script>alert('Menu tidak ditemukan'); window.location='dashboard.php';</script>";
    exit();
}
$data_menu = mysqli_fetch_assoc($cek_menu);
$stok_menu = (int)($data_menu['stok'] ?? 0);
$status_menu = $data_menu['status'] ?? 'Tersedia';

// Cek jika stok habis atau status Habis
if ($status_menu === 'Habis' || $stok_menu <= 0) {
    echo "<script>alert('Maaf, menu ini sedang habis'); window.location='detail_menu.php?id=$id_menu';</script>";
    exit();
}

if (!kk_is_kantin_open($data_menu)) {
    $jam = kk_kantin_hours_label($data_menu);
    echo "<script>alert('Maaf, {$data_menu['nama_kantin']} sedang tutup. Jam buka: $jam'); window.location='detail_menu.php?id=$id_menu';</script>";
    exit();
}

// Cek jumlah di keranjang yang sudah ada
$qty_di_keranjang = 0;
$cek_keranjang = mysqli_query($koneksi, "SELECT qty FROM keranjang WHERE id_user = $id_user AND id_menu = $id_menu AND COALESCE(opsi_pilihan,'') = '$opsi'");
if ($cek_keranjang && mysqli_num_rows($cek_keranjang) > 0) {
    $data_keranjang = mysqli_fetch_assoc($cek_keranjang);
    $qty_di_keranjang = (int)($data_keranjang['qty'] ?? 0);
}

// Total yang akan dipesan
$total_qty = $qty_di_keranjang + $qty;
if ($total_qty > $stok_menu) {
    $sisa_stok = $stok_menu - $qty_di_keranjang;
    if ($sisa_stok <= 0) {
        echo "<script>alert('Maaf, stok tidak mencukupi.'); window.location='detail_menu.php?id=$id_menu';</script>";
    } else {
        echo "<script>alert('Jumlah melebihi stok. Sisa stok: {$sisa_stok}'); window.location='detail_menu.php?id=$id_menu';</script>";
    }
    exit();
}

$cek = mysqli_query($koneksi, "SELECT * FROM keranjang WHERE id_user = '$id_user' AND id_menu = '$id_menu' AND COALESCE(opsi_pilihan,'') = '$opsi'");

if (mysqli_num_rows($cek) > 0) {
    $sql = "UPDATE keranjang SET qty = qty + $qty, catatan = '$catatan', opsi_pilihan = '$opsi'
            WHERE id_user = '$id_user' AND id_menu = '$id_menu' AND COALESCE(opsi_pilihan,'') = '$opsi'";
} else {
    $sql = "INSERT INTO keranjang (id_user, id_menu, qty, catatan, opsi_pilihan)
            VALUES ('$id_user', '$id_menu', '$qty', '$catatan', '$opsi')";
}

if (mysqli_query($koneksi, $sql)) {
    if ($action === 'checkout') {
        header("Location: checkout.php");
        exit();
    } else {
        echo "<script>alert('Berhasil ditambah ke keranjang!'); window.location='detail_menu.php?id=$id_menu';</script>";
        exit();
    }
} else {
    echo "Error: " . mysqli_error($koneksi);
}
?>
