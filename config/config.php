<?php

date_default_timezone_set("Asia/Jakarta");

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_kantin";

mysqli_report(MYSQLI_REPORT_OFF);

$server = mysqli_connect($host, $user, $pass);
if (!$server) {
    die("Koneksi MySQL gagal: " . mysqli_connect_error());
}

mysqli_query($server, "CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
mysqli_close($server);

$koneksi = mysqli_connect($host, $user, $pass, $db);
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($koneksi, 'utf8mb4');

if (!function_exists('kk_query')) {
    function kk_query($koneksi, $sql) {
        return mysqli_query($koneksi, $sql);
    }
}

if (!function_exists('kk_table_is_readable')) {
    function kk_table_is_readable($koneksi, $table) {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$table);
        if ($table === '') return false;

        $exists = mysqli_query($koneksi, "SHOW TABLES LIKE '$table'");
        if (!$exists || mysqli_num_rows($exists) === 0) {
            return true;
        }

        $check = mysqli_query($koneksi, "SELECT 1 FROM `$table` LIMIT 1");
        return $check instanceof mysqli_result;
    }
}

if (!function_exists('kk_scalar_query')) {
    function kk_scalar_query($koneksi, $sql, $key, $default = 0) {
        $result = mysqli_query($koneksi, $sql);
        if (!$result instanceof mysqli_result) {
            return $default;
        }

        $row = mysqli_fetch_assoc($result);
        return $row[$key] ?? $default;
    }
}

if (!function_exists('kk_fetch_one')) {
    function kk_fetch_one($koneksi, $sql) {
        $result = mysqli_query($koneksi, $sql);
        if (!$result instanceof mysqli_result) {
            return null;
        }

        $row = mysqli_fetch_assoc($result);
        return is_array($row) ? $row : null;
    }
}

if (!function_exists('kk_ensure_core_schema')) {
    function kk_ensure_core_schema($koneksi) {
        static $done = false;
        if ($done) return;
        $done = true;

        $tables = [
            "CREATE TABLE IF NOT EXISTS users (
                id_user INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) NOT NULL UNIQUE,
                email VARCHAR(150) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role ENUM('admin','penjual','pembeli') NOT NULL DEFAULT 'pembeli',
                id_kantin INT NULL,
                nama_kantin VARCHAR(150) NULL,
                foto_profil VARCHAR(255) NULL,
                bahasa VARCHAR(10) NOT NULL DEFAULT 'id',
                reset_token VARCHAR(100) NULL,
                reset_expired DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS kantin (
                id_kantin INT AUTO_INCREMENT PRIMARY KEY,
                id_user INT NULL,
                nama_kantin VARCHAR(150) NOT NULL,
                nama_penjual VARCHAR(150) NULL,
                pasword_kantin VARCHAR(100) NULL,
                deskripsi TEXT NULL,
                logo VARCHAR(255) NULL,
                banner VARCHAR(255) NULL,
                jam_buka TIME NULL DEFAULT '07:00:00',
                jam_tutup TIME NULL DEFAULT '15:00:00',
                status_buka ENUM('Buka','Tutup') NULL DEFAULT 'Buka',
                tipe_operasi ENUM('manual','otomatis') NOT NULL DEFAULT 'manual',
                rating DECIMAL(3,2) NOT NULL DEFAULT 0,
                total_rating INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX (id_user)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS menu (
                id_menu INT AUTO_INCREMENT PRIMARY KEY,
                id_kantin INT NOT NULL,
                nama_menu VARCHAR(150) NOT NULL,
                harga DECIMAL(12,2) NOT NULL DEFAULT 0,
                foto VARCHAR(255) NULL,
                foto_menu VARCHAR(255) NULL,
                kategori VARCHAR(50) NOT NULL DEFAULT 'Makanan',
                deskripsi TEXT NULL,
                opsi_pilihan TEXT NULL,
                stok INT NOT NULL DEFAULT 0,
                status ENUM('Tersedia','Habis') NOT NULL DEFAULT 'Tersedia',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX (id_kantin)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS keranjang (
                id_keranjang INT AUTO_INCREMENT PRIMARY KEY,
                id_user INT NOT NULL,
                id_menu INT NOT NULL,
                qty INT NOT NULL DEFAULT 1,
                catatan TEXT NULL,
                opsi_pilihan VARCHAR(120) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX (id_user),
                INDEX (id_menu)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS favorit (
                id_favorit INT AUTO_INCREMENT PRIMARY KEY,
                id_user INT NOT NULL,
                id_menu INT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_favorit (id_user, id_menu)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS pesanan (
                id_pesanan INT AUTO_INCREMENT PRIMARY KEY,
                kode_pesanan VARCHAR(50) NULL,
                id_user INT NOT NULL,
                id_kantin INT NOT NULL,
                tanggal DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                total_harga DECIMAL(12,2) NOT NULL DEFAULT 0,
                pajak INT NOT NULL DEFAULT 0,
                metode_pembayaran VARCHAR(30) NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'Pending',
                catatan TEXT NULL,
                bukti_pembayaran VARCHAR(255) NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX (id_user),
                INDEX (id_kantin),
                INDEX (tanggal)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS detail_pesanan (
                id_detail INT AUTO_INCREMENT PRIMARY KEY,
                id_pesanan INT NOT NULL,
                id_menu INT NOT NULL,
                qty INT NOT NULL DEFAULT 1,
                harga DECIMAL(12,2) NOT NULL DEFAULT 0,
                subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
                nama_menu VARCHAR(150) NULL,
                nama_kantin VARCHAR(150) NULL,
                catatan TEXT NULL,
                opsi_pilihan VARCHAR(120) NULL,
                INDEX (id_pesanan),
                INDEX (id_menu)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS ulasan (
                id_ulasan INT AUTO_INCREMENT PRIMARY KEY,
                id_user INT NOT NULL,
                id_menu INT NOT NULL,
                rating TINYINT NOT NULL DEFAULT 5,
                komentar TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_ulasan (id_user, id_menu),
                INDEX (id_menu)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS rating_menu (
                id_rating INT AUTO_INCREMENT PRIMARY KEY,
                id_user INT NOT NULL,
                id_menu INT NOT NULL,
                nilai_rating TINYINT NOT NULL DEFAULT 5,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_rating (id_user, id_menu),
                INDEX (id_menu)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS transaksi (
                id_transaksi INT AUTO_INCREMENT PRIMARY KEY,
                id_user INT NOT NULL,
                id_kantin INT NULL,
                tanggal DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                total_harga DECIMAL(12,2) NOT NULL DEFAULT 0,
                status VARCHAR(30) NOT NULL DEFAULT 'Pending'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS detail_transaksi (
                id_detail INT AUTO_INCREMENT PRIMARY KEY,
                id_transaksi INT NOT NULL,
                id_menu INT NOT NULL,
                qty INT NOT NULL DEFAULT 1,
                subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
                INDEX (id_transaksi),
                INDEX (id_menu)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ];

        $coreTables = [
            'users',
            'kantin',
            'menu',
            'keranjang',
            'favorit',
            'pesanan',
            'detail_pesanan',
            'ulasan',
            'rating_menu',
            'transaksi',
            'detail_transaksi',
        ];

        foreach ($coreTables as $table) {
            if (!kk_table_is_readable($koneksi, $table)) {
                mysqli_query($koneksi, "DROP TABLE IF EXISTS `$table`");
            }
        }

        foreach ($tables as $sql) {
            kk_query($koneksi, $sql);
        }

        $count = kk_scalar_query($koneksi, "SELECT COUNT(*) AS total FROM users", 'total', 0);
        if ((int)$count === 0) {
            $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
            $buyerPass = password_hash('buyer123', PASSWORD_DEFAULT);
            $sellerPass = password_hash('kantin123', PASSWORD_DEFAULT);

            mysqli_query($koneksi, "INSERT INTO users (username,email,password,role) VALUES
                ('admin','admin@kantin.local','$adminPass','admin'),
                ('buyer','buyer@kantin.local','$buyerPass','pembeli'),
                ('kantin_demo','penjual@kantin.local','$sellerPass','penjual')
            ");

            $seller = kk_fetch_one($koneksi, "SELECT id_user FROM users WHERE username='kantin_demo' LIMIT 1");
            $sellerId = (int)($seller['id_user'] ?? 0);
            mysqli_query($koneksi, "INSERT INTO kantin (id_user,nama_kantin,nama_penjual,deskripsi,jam_buka,jam_tutup,status_buka,tipe_operasi)
                VALUES ($sellerId,'Kantin Demo',(SELECT username FROM users WHERE id_user=$sellerId),'Kantin contoh untuk mencoba aplikasi.','07:00:00','23:59:00','Buka','manual')");
            $kantinId = (int)mysqli_insert_id($koneksi);
            mysqli_query($koneksi, "UPDATE users SET id_kantin=$kantinId, nama_kantin='Kantin Demo' WHERE id_user=$sellerId");
            mysqli_query($koneksi, "INSERT INTO menu (id_kantin,nama_menu,harga,kategori,deskripsi,stok,status) VALUES
                ($kantinId,'Nasi Goreng Demo',15000,'Makanan','Menu contoh siap pesan.',20,'Tersedia'),
                ($kantinId,'Es Teh Demo',5000,'Minuman','Minuman contoh.',30,'Tersedia')
            ");
        }

        $demoUsers = [
            ['admin', 'admin@kantin.local', 'admin123', 'admin'],
            ['buyer', 'buyer@kantin.local', 'buyer123', 'pembeli'],
            ['kantin_demo', 'penjual@kantin.local', 'kantin123', 'penjual'],
        ];

        foreach ($demoUsers as $demoUser) {
            [$demoUsername, $demoEmail, $demoPassword, $demoRole] = $demoUser;
            $existingDemo = kk_fetch_one(
                $koneksi,
                "SELECT id_user FROM users WHERE username='$demoUsername' OR email='$demoEmail' LIMIT 1"
            );

            if (!$existingDemo) {
                $demoHash = password_hash($demoPassword, PASSWORD_DEFAULT);
                mysqli_query($koneksi, "INSERT INTO users (username,email,password,role) VALUES
                    ('$demoUsername','$demoEmail','$demoHash','$demoRole')
                ");
            }
        }

        $seller = kk_fetch_one($koneksi, "SELECT id_user, id_kantin FROM users WHERE username='kantin_demo' AND role='penjual' LIMIT 1");
        if ($seller) {
            mysqli_query($koneksi, "UPDATE users SET id_kantin=NULL WHERE role <> 'penjual'");

            $sellerId = (int)$seller['id_user'];
            $kantin = kk_fetch_one($koneksi, "SELECT id_kantin FROM kantin WHERE id_user=$sellerId LIMIT 1");

            if (!$kantin) {
                mysqli_query($koneksi, "UPDATE kantin SET id_user=$sellerId WHERE nama_kantin='Kantin Demo' LIMIT 1");
                $kantin = kk_fetch_one($koneksi, "SELECT id_kantin FROM kantin WHERE id_user=$sellerId LIMIT 1");
            }

            if (!$kantin) {
                mysqli_query($koneksi, "INSERT INTO kantin (id_user,nama_kantin,nama_penjual,deskripsi,jam_buka,jam_tutup,status_buka,tipe_operasi)
                    VALUES ($sellerId,'Kantin Demo',(SELECT username FROM users WHERE id_user=$sellerId),'Kantin contoh untuk mencoba aplikasi.','07:00:00','23:59:00','Buka','manual')");
                $kantin = ['id_kantin' => mysqli_insert_id($koneksi)];
            }

            $kantinId = (int)$kantin['id_kantin'];
            mysqli_query($koneksi, "UPDATE users SET id_kantin=$kantinId, nama_kantin=(SELECT nama_kantin FROM kantin WHERE id_kantin=$kantinId) WHERE id_user=$sellerId");

            $menuCount = kk_scalar_query($koneksi, "SELECT COUNT(*) AS total FROM menu WHERE id_kantin=$kantinId", 'total', 0);
            if ((int)$menuCount === 0) {
                mysqli_query($koneksi, "INSERT INTO menu (id_kantin,nama_menu,harga,kategori,deskripsi,stok,status) VALUES
                    ($kantinId,'Nasi Goreng Demo',15000,'Makanan','Menu contoh siap pesan.',20,'Tersedia'),
                    ($kantinId,'Es Teh Demo',5000,'Minuman','Minuman contoh.',30,'Tersedia')
                ");
            }
        }
    }
}

kk_ensure_core_schema($koneksi);

// --- MIGRASI: pastikan kolom nama_kantin (users) dan nama_penjual (kantin) ada ---
function kk_column_exists($koneksi, $table, $column) {
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$table);
    $column = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$column);
    if ($table === '' || $column === '') return false;
    $res = mysqli_query($koneksi, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $res && mysqli_num_rows($res) > 0;
}

if (!kk_column_exists($koneksi, 'users', 'nama_kantin')) {
    mysqli_query($koneksi, "ALTER TABLE users ADD COLUMN nama_kantin VARCHAR(150) NULL");
}

if (!kk_column_exists($koneksi, 'kantin', 'nama_penjual')) {
    mysqli_query($koneksi, "ALTER TABLE kantin ADD COLUMN nama_penjual VARCHAR(150) NULL");
}

if (kk_column_exists($koneksi, 'kantin', 'lokasi')) {
    mysqli_query($koneksi, "ALTER TABLE kantin DROP COLUMN lokasi");
}

// Tambahkan kolom untuk pendapatan admin (pajak dan metode pembayaran)
if (!kk_column_exists($koneksi, 'transaksi', 'jumlah_pajak')) {
    mysqli_query($koneksi, "ALTER TABLE transaksi ADD COLUMN jumlah_pajak DECIMAL(12,2) NOT NULL DEFAULT 1000");
}

if (!kk_column_exists($koneksi, 'transaksi', 'metode_pembayaran')) {
    mysqli_query($koneksi, "ALTER TABLE transaksi ADD COLUMN metode_pembayaran VARCHAR(50) NULL DEFAULT 'Cash'");
}

if (!kk_column_exists($koneksi, 'transaksi', 'id_pesanan')) {
    mysqli_query($koneksi, "ALTER TABLE transaksi ADD COLUMN id_pesanan INT NULL");
}

// Isi data awal berdasarkan relasi yang ada
mysqli_query($koneksi, "UPDATE users u JOIN kantin k ON u.id_kantin = k.id_kantin SET u.nama_kantin = k.nama_kantin WHERE u.id_kantin IS NOT NULL");
mysqli_query($koneksi, "UPDATE kantin k JOIN users u ON k.id_user = u.id_user SET k.nama_penjual = u.username WHERE k.id_user IS NOT NULL");

include_once(__DIR__ . '/../includes/pembeli_helpers.php');
if (function_exists('kk_ensure_buyer_schema')) {
    kk_ensure_buyer_schema($koneksi);
}
if (function_exists('kk_refresh_kantin_status')) {
    kk_refresh_kantin_status($koneksi);
}

include_once(__DIR__ . '/../includes/language_helper.php');

?>
