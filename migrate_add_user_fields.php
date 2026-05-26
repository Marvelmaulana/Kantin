<?php
/**
 * Migration Script: Tambah fields tipe_pengguna, nip, dan kelas ke tabel users
 * Jalankan dari browser: /kantin/migrate_add_user_fields.php
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/config/config.php';

echo "<h2>🔄 Migrasi Database — Tambah Field Pengguna</h2><pre>";

try {
    // Check apakah column sudah ada
    $result = mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'tipe_pengguna'");
    
    if (!$result || mysqli_num_rows($result) === 0) {
        echo "📝 Menambahkan field tipe_pengguna...\n";
        $sql1 = "ALTER TABLE users ADD COLUMN tipe_pengguna ENUM('siswa','guru') NULL COMMENT 'Untuk pembeli & penjual: siswa atau guru' AFTER role";
        if (mysqli_query($koneksi, $sql1)) {
            echo "✅ Field tipe_pengguna ditambahkan\n";
        } else {
            echo "❌ Error menambah tipe_pengguna: " . mysqli_error($koneksi) . "\n";
        }
    } else {
        echo "✅ Field tipe_pengguna sudah ada\n";
    }

    // Check apakah column nip sudah ada
    $result = mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'nip'");
    
    if (!$result || mysqli_num_rows($result) === 0) {
        echo "📝 Menambahkan field nip...\n";
        $sql2 = "ALTER TABLE users ADD COLUMN nip VARCHAR(20) UNIQUE NULL COMMENT 'Nomor Identitas Guru untuk verifikasi penjual' AFTER tipe_pengguna";
        if (mysqli_query($koneksi, $sql2)) {
            echo "✅ Field nip ditambahkan\n";
        } else {
            echo "❌ Error menambah nip: " . mysqli_error($koneksi) . "\n";
        }
    } else {
        echo "✅ Field nip sudah ada\n";
    }

    // Check apakah column kelas sudah ada
    $result = mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'kelas'");
    
    if (!$result || mysqli_num_rows($result) === 0) {
        echo "📝 Menambahkan field kelas...\n";
        $sql3 = "ALTER TABLE users ADD COLUMN kelas ENUM('10','11','12') NULL COMMENT 'Kelas hanya untuk siswa pembeli' AFTER nip";
        if (mysqli_query($koneksi, $sql3)) {
            echo "✅ Field kelas ditambahkan\n";
        } else {
            echo "❌ Error menambah kelas: " . mysqli_error($koneksi) . "\n";
        }
    } else {
        echo "✅ Field kelas sudah ada\n";
    }

    echo "\n📊 Update Data Users:\n";
    
    // Update pembeli users yang tidak punya tipe_pengguna
    echo "📝 Mengupdate pembeli users ke tipe_pengguna = 'siswa'...\n";
    $upd1 = "UPDATE users SET tipe_pengguna = 'siswa' WHERE role = 'pembeli' AND tipe_pengguna IS NULL";
    if (mysqli_query($koneksi, $upd1)) {
        $affected = mysqli_affected_rows($koneksi);
        echo "✅ Diupdate $affected pembeli users\n";
    }

    // Update penjual users yang tidak punya tipe_pengguna
    echo "📝 Mengupdate penjual users ke tipe_pengguna = 'guru'...\n";
    $upd2 = "UPDATE users SET tipe_pengguna = 'guru' WHERE role = 'penjual' AND tipe_pengguna IS NULL";
    if (mysqli_query($koneksi, $upd2)) {
        $affected = mysqli_affected_rows($koneksi);
        echo "✅ Diupdate $affected penjual users\n";
    }

    echo "\n📋 Verifikasi Struktur Tabel:\n";
    $columns = mysqli_query($koneksi, "SHOW COLUMNS FROM users");
    if ($columns) {
        while ($col = mysqli_fetch_assoc($columns)) {
            if (in_array($col['Field'], ['id_user', 'username', 'role', 'tipe_pengguna', 'nip', 'kelas', 'email', 'password'])) {
                echo "  • " . str_pad($col['Field'], 20) . " — " . $col['Type'] . " (" . ($col['Null'] === 'YES' ? 'nullable' : 'required') . ")\n";
            }
        }
    }

    echo "\n✨ Migrasi selesai!\n";
    echo "Jika ada error di atas, cek log MySQL atau jalankan manual queries di phpMyAdmin.\n\n";
    
    echo "<strong>Catatan Penting:</strong>\n";
    echo "• Pembeli SISWA: harus punya kelas (10, 11, atau 12) dan tipe_pengguna = 'siswa'\n";
    echo "• Pembeli GURU: tipe_pengguna = 'guru', kelas harus NULL\n";
    echo "• Penjual: tipe_pengguna = 'guru', nip harus terisi, kelas harus NULL\n";

} catch (Throwable $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "</pre>";

// Tambahan: Tampilkan sample users untuk testing
echo "<hr><h3>🧪 Sample Users untuk Testing</h3><pre>";
echo "Siswa Pembeli (username: siswa1, password: password123):\n";
echo "INSERT INTO users (username, email, password, role, tipe_pengguna, kelas) \n";
echo "VALUES ('siswa1', 'siswa1@school.id', PASSWORD('password123'), 'pembeli', 'siswa', '10');\n\n";

echo "Guru Pembeli (username: guru1, password: password123):\n";
echo "INSERT INTO users (username, email, password, role, tipe_pengguna) \n";
echo "VALUES ('guru1', 'guru1@school.id', PASSWORD('password123'), 'pembeli', 'guru');\n\n";

echo "Penjual Guru (username: penjual1, nip: 123456789):\n";
echo "INSERT INTO users (username, email, password, role, tipe_pengguna, nip) \n";
echo "VALUES ('penjual1', 'penjual1@school.id', PASSWORD('temppassword'), 'penjual', 'guru', '123456789');\n";

echo "</pre>";
?>
