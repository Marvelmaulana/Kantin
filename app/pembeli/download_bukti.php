<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');
include(__DIR__ . '/../../includes/language_helper.php');
kk_ensure_buyer_schema($koneksi);

if (!isset($_SESSION['id_user'])) {
    header('Location: ../auth/login.php');
    exit;
}

$id_user = (int)$_SESSION['id_user'];
$id = (int)($_GET['id'] ?? 0);

$query = mysqli_query($koneksi, "
    SELECT p.*, k.nama_kantin
    FROM pesanan p
    JOIN kantin k ON p.id_kantin = k.id_kantin
    WHERE p.id_pesanan = $id
      AND p.id_user = $id_user
    LIMIT 1
");

$data = mysqli_fetch_assoc($query);
if (!$data) {
    header('HTTP/1.0 404 Not Found');
    echo t('tracking.order_not_found');
    exit;
}

$detail = mysqli_query($koneksi, "
    SELECT dp.qty, dp.catatan, m.nama_menu, m.harga
    FROM detail_pesanan dp
    JOIN menu m ON dp.id_menu = m.id_menu
    WHERE dp.id_pesanan = $id
");

$items = [];
while ($row = mysqli_fetch_assoc($detail)) {
    $items[] = $row;
}

$filename = 'bukti_pesanan_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $data['kode_pesanan']) . '.txt';

$lines = [];
$lines[] = 'BUKTI PEMESANAN';
$lines[] = str_repeat('=', 32);
$lines[] = 'Kode Pesanan : ' . $data['kode_pesanan'];
$lines[] = 'Kantin       : ' . $data['nama_kantin'];
$lines[] = 'Tanggal      : ' . date('d M Y H:i', strtotime($data['tanggal']));
$lines[] = 'Status       : ' . $data['status'];
$lines[] = 'Metode Bayar : ' . $data['metode_pembayaran'];
$lines[] = '';
$lines[] = 'Daftar Item';
$lines[] = str_repeat('-', 32);

foreach ($items as $item) {
    $lines[] = $item['nama_menu'];
    $lines[] = '  Qty : ' . $item['qty'];
    $lines[] = '  Harga satuan : Rp ' . number_format($item['harga'], 0, ',', '.');
    $lines[] = '  Subtotal     : Rp ' . number_format($item['qty'] * $item['harga'], 0, ',', '.');
    if (!empty($item['catatan'])) {
        $lines[] = '  Catatan      : ' . $item['catatan'];
    }
    $lines[] = '';
}
$lines[] = str_repeat('-', 32);
$lines[] = 'Total Bayar  : Rp ' . number_format($data['total_harga'], 0, ',', '.');
$lines[] = str_repeat('=', 32);
$lines[] = 'Terima kasih sudah memesan!';

$content = implode("\n", $lines) . "\n";

header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . strlen($content));

echo $content;
exit;
