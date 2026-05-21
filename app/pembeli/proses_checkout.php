<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');
kk_ensure_buyer_schema($koneksi);

if (!isset($_SESSION['id_user'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !kk_verify_csrf($_POST['csrf_token'] ?? '')) {
    kk_abort_csrf();
}

$id_user = (int)$_SESSION['id_user'];
$metode_bayar = trim($_POST['method'] ?? '');
$source = $_POST['source'] ?? '';
$catatan = trim($_POST['catatan'] ?? '');
$tanggal = date('Y-m-d H:i:s');
$allowedMethods = ['DANA', 'OVO', 'GOPAY'];

if (!in_array($metode_bayar, $allowedMethods, true)) {
    exit('Metode pembayaran tidak valid.');
}

$metode_bayar_sql = mysqli_real_escape_string($koneksi, $metode_bayar);
$catatan_sql = mysqli_real_escape_string($koneksi, $catatan);

function kk_checkout_fail($koneksi, $message, $redirect = 'keranjang.php') {
    @mysqli_rollback($koneksi);
    $_SESSION['checkout_error'] = $message;
    header("Location: $redirect");
    exit();
}

function kk_checkout_create_orders($koneksi, $id_user, $groups, $tanggal, $metode_bayar_sql, $catatan_sql) {
    $created = [];

    foreach ($groups as $id_kantin => $items) {
        $subtotal_order = array_sum(array_column($items, 'subtotal'));
        $pajak = kk_checkout_tax();
        $total_bayar = $subtotal_order + $pajak;
        $kode_pesanan = "PSN" . date('YmdHis') . $id_kantin . mt_rand(10, 99);

        $ok = mysqli_query($koneksi, "
            INSERT INTO pesanan
            (kode_pesanan, id_user, id_kantin, tanggal, total_harga, pajak, metode_pembayaran, status, catatan)
            VALUES
            ('$kode_pesanan', '$id_user', '$id_kantin', '$tanggal', '$total_bayar', '$pajak', '$metode_bayar_sql', 'Pending', '$catatan_sql')
        ");
        if (!$ok) {
            throw new RuntimeException('Gagal membuat pesanan: ' . mysqli_error($koneksi));
        }

        $id_pesanan_baru = mysqli_insert_id($koneksi);
        $created[] = $id_pesanan_baru;

        foreach ($items as $item) {
            $id_m = (int)$item['id_menu'];
            $qty_m = (int)$item['qty'];
            $harga_m = (float)$item['harga'];
            $subtotal_m = (float)$item['subtotal'];
            $nama_m = mysqli_real_escape_string($koneksi, $item['nama_menu']);
            $nama_k = mysqli_real_escape_string($koneksi, $item['nama_kantin'] ?? '');
            $opsi_m = mysqli_real_escape_string($koneksi, $item['opsi_pilihan'] ?? '');
            $catatan_item = trim(($item['opsi_pilihan'] ? $item['opsi_pilihan'] . ' - ' : '') . ($item['catatan'] ?? ''));
            $catatan_m = mysqli_real_escape_string($koneksi, $catatan_item);

            $ok = mysqli_query($koneksi, "
                INSERT INTO detail_pesanan
                (id_pesanan, id_menu, qty, harga, subtotal, nama_menu, nama_kantin, catatan, opsi_pilihan)
                VALUES
                ('$id_pesanan_baru', '$id_m', '$qty_m', '$harga_m', '$subtotal_m', '$nama_m', '$nama_k', '$catatan_m', '$opsi_m')
            ");
            if (!$ok) {
                throw new RuntimeException('Gagal menyimpan detail pesanan: ' . mysqli_error($koneksi));
            }

            $ok = mysqli_query($koneksi, "
                UPDATE menu
                SET stok = stok - $qty_m,
                    status = CASE WHEN stok - $qty_m <= 0 THEN 'Habis' ELSE 'Tersedia' END
                WHERE id_menu = $id_m AND stok >= $qty_m AND status = 'Tersedia'
            ");
            if (!$ok || mysqli_affected_rows($koneksi) !== 1) {
                throw new RuntimeException('Stok berubah saat checkout. Silakan cek keranjang lagi.');
            }
        }
    }

    return $created;
}

mysqli_begin_transaction($koneksi);

try {
    $groups = [];
    $delete_cart_ids = [];

    if ($source === 'cart') {
        $selected_ids = array_filter(array_map('intval', explode(',', $_POST['selected'] ?? '')));
        if (empty($selected_ids)) {
            kk_checkout_fail($koneksi, 'Tidak ada item yang dipilih.');
        }

        $ids_str = implode(',', $selected_ids);
        $q_items = mysqli_query($koneksi, "
            SELECT
                k.id_keranjang, k.id_menu, k.qty, k.catatan, k.opsi_pilihan,
                m.nama_menu, m.harga, m.id_kantin, m.stok, m.status,
                kt.nama_kantin, kt.jam_buka, kt.jam_tutup, kt.status_buka
            FROM keranjang k
            JOIN menu m ON k.id_menu = m.id_menu
            JOIN kantin kt ON m.id_kantin = kt.id_kantin
            WHERE k.id_user = $id_user
            AND k.id_keranjang IN ($ids_str)
            ORDER BY m.id_kantin
            FOR UPDATE
        ");

        if (!$q_items) {
            throw new RuntimeException(mysqli_error($koneksi));
        }

        while ($item = mysqli_fetch_assoc($q_items)) {
            $qty = max(1, (int)$item['qty']);
            if (!kk_is_menu_available($item)) {
                kk_checkout_fail($koneksi, $item['nama_menu'] . ' sedang habis.');
            }
            if ($qty > (int)$item['stok']) {
                kk_checkout_fail($koneksi, 'Stok ' . $item['nama_menu'] . ' tinggal ' . (int)$item['stok'] . '.');
            }
            if (!kk_is_kantin_open($item)) {
                kk_checkout_fail($koneksi, $item['nama_kantin'] . ' sedang tutup. Jam buka: ' . kk_kantin_hours_label($item) . '.');
            }

            $item['qty'] = $qty;
            $item['harga'] = (float)$item['harga'];
            $item['subtotal'] = $item['harga'] * $qty;
            $groups[(int)$item['id_kantin']][] = $item;
            $delete_cart_ids[] = (int)$item['id_keranjang'];
        }

        if (empty($groups)) {
            kk_checkout_fail($koneksi, 'Item keranjang tidak ditemukan.');
        }
    } else {
        $id_menu = (int)($_POST['id_menu'] ?? 0);
        $qty = max(1, (int)($_POST['qty'] ?? 1));
        $opsi = trim($_POST['opsi'] ?? '');

        if ($id_menu <= 0) {
            kk_checkout_fail($koneksi, 'Menu tidak ditemukan.', 'dashboard.php');
        }

        $q_menu = mysqli_query($koneksi, "
            SELECT
                m.id_menu, m.nama_menu, m.harga, m.id_kantin, m.stok, m.status,
                kt.nama_kantin, kt.jam_buka, kt.jam_tutup, kt.status_buka
            FROM menu m
            JOIN kantin kt ON m.id_kantin = kt.id_kantin
            WHERE m.id_menu = $id_menu
            FOR UPDATE
        ");

        if (!$q_menu) {
            throw new RuntimeException(mysqli_error($koneksi));
        }

        $item = mysqli_fetch_assoc($q_menu);
        if (!$item) {
            kk_checkout_fail($koneksi, 'Menu tidak ditemukan.', 'dashboard.php');
        }
        if (!kk_is_menu_available($item)) {
            kk_checkout_fail($koneksi, $item['nama_menu'] . ' sedang habis.', 'detail_menu.php?id=' . $id_menu);
        }
        if ($qty > (int)$item['stok']) {
            kk_checkout_fail($koneksi, 'Stok ' . $item['nama_menu'] . ' tinggal ' . (int)$item['stok'] . '.', 'detail_menu.php?id=' . $id_menu);
        }
        if (!kk_is_kantin_open($item)) {
            kk_checkout_fail($koneksi, $item['nama_kantin'] . ' sedang tutup. Jam buka: ' . kk_kantin_hours_label($item) . '.', 'detail_menu.php?id=' . $id_menu);
        }

        $item['qty'] = $qty;
        $item['catatan'] = '';
        $item['opsi_pilihan'] = $opsi;
        $item['harga'] = (float)$item['harga'];
        $item['subtotal'] = $item['harga'] * $qty;
        $groups[(int)$item['id_kantin']][] = $item;
    }

    $created = kk_checkout_create_orders($koneksi, $id_user, $groups, $tanggal, $metode_bayar_sql, $catatan_sql);

    if (!empty($delete_cart_ids)) {
        $delete_ids_str = implode(',', $delete_cart_ids);
        mysqli_query($koneksi, "DELETE FROM keranjang WHERE id_user = $id_user AND id_keranjang IN ($delete_ids_str)");
    }

    mysqli_commit($koneksi);

    $redirectId = $created[0] ?? 0;
    if (count($created) === 1 && $redirectId > 0) {
        header("Location: pembayaran_berhasil.php?id_pesanan=$redirectId");
    } else {
        header("Location: pesanan.php?success=checkout_multi");
    }
    exit();
} catch (Throwable $e) {
    kk_checkout_fail($koneksi, $e->getMessage());
}
?>
