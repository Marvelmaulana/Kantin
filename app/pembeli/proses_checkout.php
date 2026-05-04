<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user'])) { exit(); }
$id_user = $_SESSION['id_user'];

// Ambil metode dari URL (ewallet / cash)
$metode = isset($_GET['metode']) ? $_GET['metode'] : 'cash';
$tanggal = date('Y-m-d H:i:s');

// 1. Ambil data keranjang DAN id_kantin
$q_keranjang = mysqli_query($koneksi, "SELECT keranjang.*, menu.harga, menu.id_kantin 
                                       FROM keranjang 
                                       JOIN menu ON keranjang.id_menu = menu.id_menu 
                                       WHERE keranjang.id_user = '$id_user'");

// Kita butuh data keranjang dua kali, simpan ke array
$items = [];
$total_harga = 0;
$id_kantin = null;

while($row = mysqli_fetch_assoc($q_keranjang)) {
    $items[] = $row;
    $total_harga += ($row['harga'] * $row['qty']);
    $id_kantin = $row['id_kantin']; 
}

if ($total_harga > 0 && $id_kantin != null) {
    // 2. Simpan ke tabel pesanan (Utama)
    $status = ($metode == 'ewallet') ? 'Lunas' : 'Proses Masak';
    
    $query_insert = "INSERT INTO pesanan (id_user, id_kantin, total_harga, tanggal, status, metode_pembayaran) 
                     VALUES ('$id_user', '$id_kantin', '$total_harga', '$tanggal', '$status', '$metode')";
    
    if (mysqli_query($koneksi, $query_insert)) {
        // AMBIL ID PESANAN YANG BARU SAJA TERJADI
        $id_pesanan_baru = mysqli_insert_id($koneksi);

        // 3. MASUKKAN SETIAP ITEM KE TABEL detail_pesanan
        foreach ($items as $item) {
            $id_menu = $item['id_menu'];
            $qty = $item['qty'];
            $subtotal = $item['harga'] * $qty;
            $catatan = $item['catatan']; // Pastikan di tabel keranjang ada kolom catatan

            mysqli_query($koneksi, "INSERT INTO detail_pesanan (id_pesanan, id_menu, qty, subtotal, catatan) 
                                    VALUES ('$id_pesanan_baru', '$id_menu', '$qty', '$subtotal', '$catatan')");
        }

        // 4. Kosongkan keranjang pembeli
        mysqli_query($koneksi, "DELETE FROM keranjang WHERE id_user = '$id_user'");
        
        echo "<script>
                alert('Pembayaran Berhasil! Pesanan Anda sedang dikirim ke kantin.');
                window.location='pesanan.php'; 
              </script>";
    } else {
        echo "Gagal memproses pesanan: " . mysqli_error($koneksi);
    }
} else {
    echo "<script>alert('Keranjang kosong!'); window.location='dashboard.php';</script>";
}
?>