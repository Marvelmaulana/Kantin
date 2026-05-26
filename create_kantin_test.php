<?php
include 'config/config.php';

echo "<pre>";
echo "Creating Kantin for Guru Test Account...\n\n";

// Get guru_test user ID
$result = mysqli_query($koneksi, "SELECT id_user FROM users WHERE username='guru_test' LIMIT 1");

if (!$result) {
    echo "✗ Query failed: " . mysqli_error($koneksi) . "\n";
    exit;
}

if (mysqli_num_rows($result) == 0) {
    echo "✗ guru_test account not found\n";
    exit;
}

$guru = mysqli_fetch_assoc($result);
$id_penjual = $guru['id_user'];

// Check if kantin already exists (try both id_penjual and id_user)
$check = mysqli_query($koneksi, "SELECT * FROM kantin WHERE id_penjual=$id_penjual OR id_user=$id_penjual");

if (!$check) {
    echo "✗ Query failed: " . mysqli_error($koneksi) . "\n";
    exit;
}

if (mysqli_num_rows($check) > 0) {
    $existing = mysqli_fetch_assoc($check);
    echo "✗ Kantin already exists for guru_test\n";
    echo "   - ID: " . $existing['id_kantin'] . "\n";
    echo "   - Name: " . $existing['nama_kantin'] . "\n";
} else {
    // Create kantin
    $nama_kantin = 'Kantin Guru Test';
    $deskripsi = 'Kantin untuk testing guru penjual';
    
    $insert = mysqli_query($koneksi, "
        INSERT INTO kantin (id_penjual, id_user, nama_kantin, deskripsi)
        VALUES ($id_penjual, $id_penjual, '$nama_kantin', '$deskripsi')
    ");
    
    if ($insert) {
        $kantin_id = mysqli_insert_id($koneksi);
        echo "✓ Kantin created successfully!\n\n";
        echo "Kantin Details:\n";
        echo "- Kantin ID: " . $kantin_id . "\n";
        echo "- Penjual ID: " . $id_penjual . "\n";
        echo "- Nama: " . $nama_kantin . "\n";
    } else {
        echo "✗ Error creating kantin: " . mysqli_error($koneksi) . "\n";
    }
}

echo "</pre>";
?>
