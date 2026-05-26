<?php
include 'config/config.php';

echo "<pre>";
echo "Kantin Table Structure Check...\n\n";

// Check kantin schema
$schema = mysqli_query($koneksi, "DESCRIBE kantin");

if (!$schema) {
    echo "✗ Error: " . mysqli_error($koneksi) . "\n";
    exit;
}

echo "Kantin table columns:\n";
$has_id_penjual = false;
while ($col = mysqli_fetch_assoc($schema)) {
    echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    if ($col['Field'] == 'id_penjual') {
        $has_id_penjual = true;
    }
}

if (!$has_id_penjual) {
    echo "\n✗ Column 'id_penjual' not found. Adding it now...\n";
    $alter = mysqli_query($koneksi, "ALTER TABLE kantin ADD COLUMN id_penjual INT UNSIGNED AFTER id_kantin");
    if ($alter) {
        echo "✓ Column id_penjual added successfully\n";
    } else {
        echo "✗ Error adding column: " . mysqli_error($koneksi) . "\n";
    }
} else {
    echo "\n✓ Column id_penjual exists\n";
}

echo "</pre>";
?>
