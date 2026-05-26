<?php
include 'config/config.php';

echo "<pre>";

// Check connection
if (!$koneksi) {
    echo "✗ Database connection failed\n";
    exit;
}

// Check guru penjual account
$result = mysqli_query($koneksi, "SELECT id_user, username, email, role, tipe_pengguna FROM users WHERE role='penjual' AND tipe_pengguna='guru' LIMIT 1");

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    echo "✓ Guru Penjual Found: " . json_encode($row) . "\n";
} else {
    echo "✗ Guru Penjual Not Found\n";
    if (!$result) {
        echo "   Query Error: " . mysqli_error($koneksi) . "\n";
    }
}

// Check all users
$result_all = mysqli_query($koneksi, "SELECT id_user, username, email, role, tipe_pengguna FROM users");
echo "\nUsers in database:\n";
if ($result_all) {
    $count = 0;
    while ($row = mysqli_fetch_assoc($result_all)) {
        echo "- " . json_encode($row) . "\n";
        $count++;
    }
    echo "Total: " . $count . " users\n";
}

// Check schema
$schema = mysqli_query($koneksi, "DESCRIBE users");
echo "\nUsers table schema:\n";
if ($schema) {
    while ($col = mysqli_fetch_assoc($schema)) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
}

echo "</pre>";
?>
