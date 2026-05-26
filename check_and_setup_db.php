<?php
/**
 * Database Check & Setup Script
 * Untuk memverifikasi dan membuat database serta tables yang diperlukan
 */

// Disable errors for clean output
error_reporting(0);

// 1. Database Connection
$host = "localhost";
$user = "root";
$pass = "";

echo "<h2>🔧 Database Setup & Check</h2>";
echo "<hr>";

// Try to connect to MySQL server
echo "<p><strong>1. Connecting to MySQL Server...</strong></p>";
$server = mysqli_connect($host, $user, $pass);
if (!$server) {
    die("<p style='color:red;'>❌ FAILED: " . mysqli_connect_error() . "</p>");
}
echo "<p style='color:green;'>✅ MySQL Server connected!</p>";

// 2. Create Database if not exists
echo "<p><strong>2. Checking/Creating Database 'db_kantin'...</strong></p>";
$db = "db_kantin";
$createDb = mysqli_query($server, "CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
if ($createDb) {
    echo "<p style='color:green;'>✅ Database 'db_kantin' is ready!</p>";
} else {
    echo "<p style='color:red;'>❌ FAILED to create database: " . mysqli_error($server) . "</p>";
}

// 3. Connect to the database
$koneksi = mysqli_connect($host, $user, $pass, $db);
if (!$koneksi) {
    die("<p style='color:red;'>❌ FAILED to connect to db_kantin: " . mysqli_connect_error() . "</p>");
}
mysqli_set_charset($koneksi, 'utf8mb4');
echo "<p style='color:green;'>✅ Connected to 'db_kantin' database!</p>";

// 4. Check existing tables
echo "<p><strong>3. Checking Existing Tables...</strong></p>";
$result = mysqli_query($koneksi, "SHOW TABLES");
$tables = [];
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
}
if (count($tables) > 0) {
    echo "<p style='color:green;'>✅ Found " . count($tables) . " table(s): " . implode(", ", $tables) . "</p>";
} else {
    echo "<p style='color:orange;'>⚠️ No tables found. Creating database schema...</p>";
}

// 5. Create tables from database_schema.sql
echo "<p><strong>4. Creating Required Tables...</strong></p>";

// Users table
$users_table = "
CREATE TABLE IF NOT EXISTS users (
    id_user INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','penjual','pembeli') NOT NULL DEFAULT 'pembeli',
    tipe_pengguna ENUM('siswa','guru') NULL,
    nip VARCHAR(20) UNIQUE NULL,
    kelas ENUM('10','11','12') NULL,
    bahasa VARCHAR(10) NOT NULL DEFAULT 'id',
    foto_profil VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_user_kelas CHECK (
        (role = 'pembeli' AND tipe_pengguna = 'siswa' AND kelas IN ('10','11','12')) OR
        (role = 'pembeli' AND tipe_pengguna = 'guru' AND kelas IS NULL) OR
        (role = 'penjual' AND tipe_pengguna = 'guru' AND kelas IS NULL) OR
        (role = 'admin' AND tipe_pengguna IS NULL AND kelas IS NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if (mysqli_query($koneksi, $users_table)) {
    echo "<p style='color:green;'>✅ 'users' table created/exists</p>";
} else {
    echo "<p style='color:red;'>❌ Failed to create 'users' table: " . mysqli_error($koneksi) . "</p>";
}

// Kantin table
$kantin_table = "
CREATE TABLE IF NOT EXISTS kantin (
    id_kantin INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_penjual INT UNSIGNED NOT NULL,
    nama_kantin VARCHAR(150) NOT NULL,
    deskripsi TEXT,
    alamat VARCHAR(255),
    jam_buka TIME NOT NULL DEFAULT '07:00:00',
    jam_tutup TIME NOT NULL DEFAULT '15:00:00',
    tipe_operasi ENUM('manual','otomatis') NOT NULL DEFAULT 'manual',
    status_buka ENUM('Buka','Tutup') NOT NULL DEFAULT 'Tutup',
    rating DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    total_ulasan INT UNSIGNED NOT NULL DEFAULT 0,
    logo VARCHAR(255) NULL,
    banner VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_penjual) REFERENCES users(id_user) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if (mysqli_query($koneksi, $kantin_table)) {
    echo "<p style='color:green;'>✅ 'kantin' table created/exists</p>";
} else {
    echo "<p style='color:red;'>❌ Failed to create 'kantin' table: " . mysqli_error($koneksi) . "</p>";
}

// Menu table
$menu_table = "
CREATE TABLE IF NOT EXISTS menu (
    id_menu INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_kantin INT UNSIGNED NOT NULL,
    nama_menu VARCHAR(150) NOT NULL,
    deskripsi TEXT,
    harga INT UNSIGNED NOT NULL,
    kategori VARCHAR(50),
    gambar VARCHAR(255) NULL,
    stok INT UNSIGNED DEFAULT 0,
    is_available TINYINT(1) DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kantin) REFERENCES kantin(id_kantin) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if (mysqli_query($koneksi, $menu_table)) {
    echo "<p style='color:green;'>✅ 'menu' table created/exists</p>";
} else {
    echo "<p style='color:red;'>❌ Failed to create 'menu' table: " . mysqli_error($koneksi) . "</p>";
}

// Pesanan table
$pesanan_table = "
CREATE TABLE IF NOT EXISTS pesanan (
    id_pesanan INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pembeli INT UNSIGNED NOT NULL,
    id_kantin INT UNSIGNED NOT NULL,
    status ENUM('pending','dikonfirmasi','siap','diambil','dibatalkan') DEFAULT 'pending',
    total_harga INT UNSIGNED NOT NULL,
    catatan TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pembeli) REFERENCES users(id_user) ON DELETE CASCADE,
    FOREIGN KEY (id_kantin) REFERENCES kantin(id_kantin) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if (mysqli_query($koneksi, $pesanan_table)) {
    echo "<p style='color:green;'>✅ 'pesanan' table created/exists</p>";
} else {
    echo "<p style='color:red;'>❌ Failed to create 'pesanan' table: " . mysqli_error($koneksi) . "</p>";
}

// Detail Pesanan table
$detail_pesanan_table = "
CREATE TABLE IF NOT EXISTS detail_pesanan (
    id_detail INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_pesanan INT UNSIGNED NOT NULL,
    id_menu INT UNSIGNED NOT NULL,
    jumlah INT UNSIGNED NOT NULL,
    harga_satuan INT UNSIGNED NOT NULL,
    FOREIGN KEY (id_pesanan) REFERENCES pesanan(id_pesanan) ON DELETE CASCADE,
    FOREIGN KEY (id_menu) REFERENCES menu(id_menu) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

if (mysqli_query($koneksi, $detail_pesanan_table)) {
    echo "<p style='color:green;'>✅ 'detail_pesanan' table created/exists</p>";
} else {
    echo "<p style='color:red;'>❌ Failed to create 'detail_pesanan' table: " . mysqli_error($koneksi) . "</p>";
}

// 6. Summary
echo "<hr>";
echo "<p><strong>5. Final Summary</strong></p>";
$result = mysqli_query($koneksi, "SHOW TABLES");
$final_tables = [];
while ($row = mysqli_fetch_row($result)) {
    $final_tables[] = $row[0];
}
echo "<p style='color:green;'>✅ Database 'db_kantin' is properly configured with " . count($final_tables) . " table(s)</p>";
echo "<p>Tables: <strong>" . implode(", ", $final_tables) . "</strong></p>";

// 7. Create test user
echo "<hr>";
echo "<p><strong>6. Creating Test User...</strong></p>";

// Check if test user exists
$check = mysqli_query($koneksi, "SELECT id_user FROM users WHERE username='testuser' LIMIT 1");
if (mysqli_num_rows($check) > 0) {
    echo "<p style='color:blue;'>ℹ️ Test user already exists</p>";
} else {
    // Hash password
    $hashed_password = password_hash('password123', PASSWORD_BCRYPT);
    
    // Insert test user
    $insert = mysqli_query($koneksi, "INSERT INTO users (username, email, password, role, tipe_pengguna, kelas, bahasa) VALUES ('testuser', 'testuser@school.com', '$hashed_password', 'pembeli', 'siswa', '10', 'id')");
    
    if ($insert) {
        echo "<p style='color:green;'>✅ Test user created!</p>";
        echo "<p>Username: <strong>testuser</strong></p>";
        echo "<p>Password: <strong>password123</strong></p>";
        echo "<p>Kelas: <strong>10</strong></p>";
    } else {
        echo "<p style='color:red;'>❌ Failed to create test user: " . mysqli_error($koneksi) . "</p>";
    }
}

// Close connection
mysqli_close($koneksi);
mysqli_close($server);

echo "<hr>";
echo "<p><a href='app/auth/login.php'>👉 Go to Login Page</a></p>";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h2 { color: #333; }
        p { line-height: 1.6; }
        hr { margin: 20px 0; }
    </style>
</head>
<body>
</body>
</html>
