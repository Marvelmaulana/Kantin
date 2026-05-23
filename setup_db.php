<?php
// Simple DB setup helper — run from browser: /kantin/setup_db.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/config/config.php';

echo "<h2>Setup DB — memastikan skema inti</h2>\n<pre>";

if (!function_exists('kk_ensure_core_schema')) {
    echo "Error: kk_ensure_core_schema() tidak ditemukan. Pastikan file config/config.php ter-include.\n";
    exit;
}

try {
    kk_ensure_core_schema($koneksi);
    echo "kk_ensure_core_schema() selesai.\n\n";

    $tables = [
        'users','kantin','menu','keranjang','favorit','pesanan','detail_pesanan','ulasan','rating_menu','transaksi','detail_transaksi'
    ];

    foreach ($tables as $t) {
        $res = mysqli_query($koneksi, "SHOW TABLES LIKE '$t'");
        if ($res && mysqli_num_rows($res) > 0) {
            $cnt = 0;
            $c = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM `$t`");
            if ($c && ($r = mysqli_fetch_assoc($c))) $cnt = (int)$r['total'];
            echo "Tabel: $t — ADA (rows: $cnt)\n";
        } else {
            echo "Tabel: $t — TIDAK ADA\n";
        }
    }

    echo "\nSelesai. Jika masih error, cek log MySQL atau jalankan phpMyAdmin.\n";
} catch (Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

echo "</pre>";

?>
