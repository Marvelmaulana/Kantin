<?php
include 'config/config.php';

echo "<pre>";
echo "Starting Database Migration...\n\n";

// 1. Add tipe_pengguna column if not exists
echo "1. Checking tipe_pengguna column...\n";
$result = mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'tipe_pengguna'");
if (mysqli_num_rows($result) == 0) {
    echo "   - Adding tipe_pengguna column...\n";
    $alter1 = mysqli_query($koneksi, "ALTER TABLE users ADD COLUMN tipe_pengguna ENUM('siswa','guru') NULL AFTER role");
    if ($alter1) {
        echo "   ✓ tipe_pengguna column added\n";
    } else {
        echo "   ✗ Error: " . mysqli_error($koneksi) . "\n";
    }
} else {
    echo "   ✓ tipe_pengguna column already exists\n";
}

// 2. Add nip column if not exists
echo "\n2. Checking nip column...\n";
$result = mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'nip'");
if (mysqli_num_rows($result) == 0) {
    echo "   - Adding nip column...\n";
    $alter2 = mysqli_query($koneksi, "ALTER TABLE users ADD COLUMN nip VARCHAR(20) UNIQUE NULL AFTER tipe_pengguna");
    if ($alter2) {
        echo "   ✓ nip column added\n";
    } else {
        echo "   ✗ Error: " . mysqli_error($koneksi) . "\n";
    }
} else {
    echo "   ✓ nip column already exists\n";
}

// 3. Verify updated schema
echo "\n3. Updated Users table schema:\n";
$schema = mysqli_query($koneksi, "DESCRIBE users");
while ($col = mysqli_fetch_assoc($schema)) {
    echo "   - " . $col['Field'] . " (" . $col['Type'] . ")\n";
}

echo "\n✓ Database migration completed!\n";
echo "</pre>";
?>
