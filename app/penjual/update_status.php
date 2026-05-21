<?php
session_start();
include(__DIR__ . '/../../config/config.php');

// 1. Proteksi — hanya penjual
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'penjual') {
    header("Location: ../auth/login.php");
    exit;
}

// 2. Hanya terima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: pesanan_masuk.php");
    exit;
}

// 3. Ambil & validasi input
$id_p   = intval($_POST['id_pesanan']  ?? 0);
$status = trim($_POST['status_baru']   ?? '');
$id_k   = intval($_SESSION['id_kantin'] ?? 0);

// Status yang diizinkan
$status_valid = ['Pending', 'Diproses', 'Siap Diambil', 'Selesai'];

if ($id_p <= 0 || !in_array($status, $status_valid)) {
    header("Location: pesanan_masuk.php?error=Data+tidak+valid");
    exit;
}

// 4. Verifikasi pesanan milik kantin yang login (keamanan: cegah update pesanan kantin lain)
$cek = mysqli_query($koneksi,
    "SELECT id_pesanan FROM pesanan WHERE id_pesanan = $id_p AND id_kantin = $id_k LIMIT 1"
);

if (mysqli_num_rows($cek) == 0) {
    header("Location: pesanan_masuk.php?error=Pesanan+tidak+ditemukan");
    exit;
}

// 5. Update dengan prepared statement (lebih aman)
$stmt = mysqli_prepare($koneksi,
    "UPDATE pesanan SET status = ? WHERE id_pesanan = ? AND id_kantin = ?"
);
mysqli_stmt_bind_param($stmt, 'sii', $status, $id_p, $id_k);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// 6. Redirect balik dengan pesan
// Catatan: update ini otomatis terlihat pembeli karena mereka query tabel pesanan yang sama
if ($ok) {
    if ($status === 'Selesai') {
        header("Location: riwayat_penjual.php?success=selesai");
    } else {
        header("Location: pesanan_masuk.php?success=1");
    }
} else {
    $err = urlencode(mysqli_error($koneksi));
    header("Location: pesanan_masuk.php?error=$err");
}
exit;
?>
