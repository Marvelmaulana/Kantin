<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = $_SESSION['id_user'];
$metode_bayar = mysqli_real_escape_string($koneksi, $_GET['method']);
$source = isset($_GET['source']) ? $_GET['source'] : '';
$tanggal = date('Y-m-d H:i:s');

// 1. HITUNG TOTAL HARGA DULU
$total_bayar = 0;

if ($source == 'cart') {
    // Ambil total dari keranjang
    $q_total = mysqli_query($koneksi, "SELECT SUM(menu.harga * keranjang.qty) as total 
                                      FROM keranjang 
                                      JOIN menu ON keranjang.id_menu = menu.id_menu 
                                      WHERE keranjang.id_user = '$id_user'");
    $res_total = mysqli_fetch_assoc($q_total);
    $total_bayar = $res_total['total'];
} else {
    // Ambil total dari beli langsung
    $id_menu = mysqli_real_escape_string($koneksi, $_GET['id_menu']);
    $qty = (int)$_GET['qty'];
    $q_menu = mysqli_query($koneksi, "SELECT harga FROM menu WHERE id_menu = '$id_menu'");
    $res_menu = mysqli_fetch_assoc($q_menu);
    $total_bayar = $res_menu['harga'] * $qty;
}

// 2. INPUT KE TABEL PESANAN (Induk)
// Status default 'menunggu'
$query_pesanan = "INSERT INTO pesanan (id_user, tanggal, total_harga, metode_pembayaran, status) 
                  VALUES ('$id_user', '$tanggal', '$total_bayar', '$metode_bayar', 'menunggu')";

if (mysqli_query($koneksi, $query_pesanan)) {
    $id_pesanan_baru = mysqli_insert_id($koneksi); // AMBIL ID PESANAN YANG BARU SAJA DIBUAT

    // 3. INPUT KE TABEL DETAIL_PESANAN (Anak)
    if ($source == 'cart') {
        // Pindahkan semua dari keranjang ke detail_pesanan
        $keranjang = mysqli_query($koneksi, "SELECT * FROM keranjang WHERE id_user = '$id_user'");
        while ($item = mysqli_fetch_assoc($keranjang)) {
            $id_m = $item['id_menu'];
            $qty_m = $item['qty'];
            $keranjang = mysqli_query($koneksi, "
    SELECT keranjang.*, menu.harga 
    FROM keranjang 
    JOIN menu ON keranjang.id_menu = menu.id_menu
    WHERE keranjang.id_user = '$id_user'
");
            
            mysqli_query($koneksi, "INSERT INTO detail_pesanan (id_pesanan, id_menu, qty) 
                                    VALUES ('$id_pesanan_baru', '$id_m', '$qty_m')");
        }
        // Hapus keranjang karena sudah jadi pesanan
        mysqli_query($koneksi, "DELETE FROM keranjang WHERE id_user = '$id_user'");
        
    } else {
        // Input satu menu saja (Beli Langsung)
        mysqli_query($koneksi, "INSERT INTO detail_pesanan (id_pesanan, id_menu, qty) 
                                VALUES ('$id_pesanan_baru', '$id_menu', '$qty')");
    }

    // 4. REDIRECT KE HALAMAN BERHASIL (STRUK)
    header("Location: pembayaran_berhasil.php?id_pesanan=" . $id_pesanan_baru);
    exit();

} else {
    echo "Gagal memproses pesanan: " . mysqli_error($koneksi);
}
?>