<?php
session_start();
include(__DIR__ . '/../../config/config.php');

// 1. Cek Login
if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_user = (int)$_SESSION['id_user'];
    $id_menu = (int)($_POST['id_menu'] ?? 0);
    $rating  = max(1, min(5, (int)($_POST['rating'] ?? 5)));
    $komentar = trim($_POST['komentar'] ?? '');
    $komentar_sql = mysqli_real_escape_string($koneksi, $komentar);

    if ($id_menu <= 0) {
    echo json_encode(['success' => false, 'message' => 'Menu tidak valid.']);
    exit();
}

    // Pastikan menu ini memang pernah dibeli dan pesanan sudah selesai.
    $cek_beli = mysqli_query($koneksi, "
        SELECT dp.id_detail
        FROM detail_pesanan dp
        JOIN pesanan p ON dp.id_pesanan = p.id_pesanan
        WHERE dp.id_menu = $id_menu
          AND p.id_user = $id_user
          AND p.status = 'Selesai'
        LIMIT 1
    ");

    if (!$cek_beli || mysqli_num_rows($cek_beli) === 0) {
    echo json_encode(['success' => false, 'message' => 'Kamu belum pernah membeli menu ini.']);
    exit();
}

    // Ulasan teks: struktur tabel ulasan hanya per user + menu.
    $cek_ulasan = mysqli_query($koneksi, "SELECT id_ulasan FROM ulasan WHERE id_user=$id_user AND id_menu=$id_menu LIMIT 1");
    if ($cek_ulasan && mysqli_num_rows($cek_ulasan) > 0) {
        $u = mysqli_fetch_assoc($cek_ulasan);
        mysqli_query($koneksi, "
            UPDATE ulasan
            SET rating=$rating, komentar='$komentar_sql'
            WHERE id_ulasan=".(int)$u['id_ulasan']."
        ");
    } else {
        mysqli_query($koneksi, "
            INSERT INTO ulasan (id_user, id_menu, rating, komentar) 
VALUES ($id_user, $id_menu, $rating, '$komentar_sql')
        ");
    }

    // Rating numerik untuk dashboard pembeli/penjual.
    $cek_rating = mysqli_query($koneksi, "SELECT id_rating FROM rating_menu WHERE id_user=$id_user AND id_menu=$id_menu LIMIT 1");
    if ($cek_rating && mysqli_num_rows($cek_rating) > 0) {
        $r = mysqli_fetch_assoc($cek_rating);
        mysqli_query($koneksi, "
            UPDATE rating_menu
            SET nilai_rating=$rating
            WHERE id_rating=".(int)$r['id_rating']."
        ");
    } else {
        mysqli_query($koneksi, "
            INSERT INTO rating_menu (id_user, id_menu, nilai_rating)
            VALUES ($id_user, $id_menu, $rating)
        ");
    }

    // Sinkronkan rating kantin sebagai rata-rata semua rating menu milik kantin.
    $q_kantin = mysqli_query($koneksi, "SELECT id_kantin FROM menu WHERE id_menu=$id_menu LIMIT 1");
    if ($q_kantin && ($kantin = mysqli_fetch_assoc($q_kantin))) {
        $id_kantin = (int)$kantin['id_kantin'];
        $q_avg = mysqli_query($koneksi, "
            SELECT COALESCE(AVG(rm.nilai_rating),0) AS avg_rating, COUNT(rm.id_rating) AS total_rating
            FROM rating_menu rm
            JOIN menu m ON rm.id_menu = m.id_menu
            WHERE m.id_kantin = $id_kantin
        ");
        if ($q_avg && ($avg = mysqli_fetch_assoc($q_avg))) {
            $avg_rating = (float)$avg['avg_rating'];
            $total_rating = (int)$avg['total_rating'];
            mysqli_query($koneksi, "
                UPDATE kantin
                SET rating=$avg_rating, total_ulasan=$total_rating
                WHERE id_kantin=$id_kantin
            ");
        }
    }

   echo json_encode(['success' => true]);
exit();
} else {
    header("Location: dashboard.php");
    exit();
}
?>
