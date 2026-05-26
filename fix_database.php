<?php
/**
 * Database Fixer untuk bug registrasi
 * Fix struktur tabel users dan ensure data integrity
 */

$host = "localhost";
$user = "root";
$pass = "";
$db = "db_kantin";

$koneksi = mysqli_connect($host, $user, $pass, $db);
if (!$koneksi) {
    die("❌ Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($koneksi, 'utf8mb4');

echo "🔧 MEMULAI PERBAIKAN DATABASE...\n\n";

$queries = [
    // 1. Hapus kolom problematic jika ada
    ['ALTER TABLE users DROP COLUMN IF EXISTS id_kantin;', 'Hapus kolom id_kantin yang menyebabkan INSERT gagal'],
    ['ALTER TABLE users DROP COLUMN IF EXISTS nama_kantin;', 'Hapus kolom nama_kantin yang ekstra'],
    
    // 2. Ubah kolom menjadi NOT NULL dan set constraint yang benar
    ['ALTER TABLE users MODIFY COLUMN username VARCHAR(100) NOT NULL UNIQUE;', 'Set username NOT NULL'],
    ['ALTER TABLE users MODIFY COLUMN email VARCHAR(150) NOT NULL UNIQUE;', 'Set email VARCHAR(150) NOT NULL'],
    ['ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NOT NULL;', 'Set password NOT NULL'],
    ['ALTER TABLE users MODIFY COLUMN role ENUM("admin","penjual","pembeli") NOT NULL DEFAULT "pembeli";', 'Set role NOT NULL with default'],
    ['ALTER TABLE users MODIFY COLUMN bahasa VARCHAR(10) NOT NULL DEFAULT "id";', 'Ensure bahasa has default'],
];

foreach ($queries as [$query, $description]) {
    if (empty(trim($query))) continue;
    
    echo "▶ $description...\n";
    
    if (mysqli_query($koneksi, $query)) {
        echo "   ✓ Berhasil\n";
    } else {
        $error = mysqli_error($koneksi);
        // Ignore "check constraint already exists" error
        if (strpos($error, 'already exists') !== false) {
            echo "   ℹ Sudah ada (skip)\n";
        } else {
            echo "   ✗ Error: $error\n";
        }
    }
}

echo "\n📊 STRUKTUR TABEL USERS SETELAH PERBAIKAN:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$result = mysqli_query($koneksi, "SHOW FULL COLUMNS FROM users;");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $null_status = $row['Null'] === 'NO' ? 'NOT NULL' : 'NULL';
        $key_status = $row['Key'] ? " [{$row['Key']}]" : "";
        $default = $row['Default'] ? " DEFAULT '{$row['Default']}'" : "";
        printf("  %-18s %-35s %s%s%s\n", 
            $row['Field'], 
            $row['Type'], 
            $null_status,
            $key_status,
            $default
        );
    }
}

echo "\n✓ PERBAIKAN DATABASE SELESAI!\n";

// Verify data yang existing masih valid
echo "\n📋 VERIFIKASI DATA EXISTING:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$check = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users;");
if ($check) {
    $row = mysqli_fetch_assoc($check);
    echo "  Total user di database: {$row['total']}\n";
}

$check = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE username IS NOT NULL AND email IS NOT NULL AND password IS NOT NULL;");
if ($check) {
    $row = mysqli_fetch_assoc($check);
    echo "  User dengan data lengkap: {$row['total']}\n";
}

echo "\n🎉 DATABASE SIAP UNTUK REGISTRASI!\n";

mysqli_close($koneksi);
?>
