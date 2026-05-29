<?php
/**
 * Test Integration: Edit Profil Kantin
 * Verifikasi bahwa semua field edit profil tersinkronisasi dengan database dan tampilan pembeli
 */

session_start();
include(__DIR__ . '/config/config.php');
include(__DIR__ . '/includes/pembeli_helpers.php');

date_default_timezone_set('Asia/Jakarta');

echo "<h1>🔍 Test Integration: Edit Profil Kantin</h1>";
echo "<hr>";

// Test 1: Cek database fields
echo "<h2>Test 1: Cek Database Fields</h2>";
$q = mysqli_query($koneksi, "SHOW COLUMNS FROM kantin");
$fields_needed = ['id_kantin', 'id_penjual', 'nama_kantin', 'deskripsi', 'alamat', 'jam_buka', 'jam_tutup', 'tipe_operasi', 'status_buka', 'logo', 'banner', 'rating', 'total_ulasan'];
$db_fields = [];
while ($row = mysqli_fetch_assoc($q)) {
    $db_fields[] = $row['Field'];
}

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Field</th><th>Status</th><th>Type</th></tr>";
foreach ($fields_needed as $field) {
    $exists = in_array($field, $db_fields);
    $status = $exists ? "✅ OK" : "❌ MISSING";
    
    // Get type
    $type_q = mysqli_query($koneksi, "SHOW COLUMNS FROM kantin WHERE Field='$field'");
    $type_row = mysqli_fetch_assoc($type_q);
    $type = $type_row['Type'] ?? 'N/A';
    
    echo "<tr><td>$field</td><td>$status</td><td>$type</td></tr>";
}
echo "</table>";

echo "<hr>";

// Test 2: Data Sample Kantin
echo "<h2>Test 2: Data Sample Kantin</h2>";
$q_sample = mysqli_query($koneksi, "SELECT * FROM kantin LIMIT 1");
if ($q_sample && mysqli_num_rows($q_sample) > 0) {
    $kantin = mysqli_fetch_assoc($q_sample);
    echo "<pre>";
    echo "ID Kantin: " . $kantin['id_kantin'] . "\n";
    echo "Nama: " . $kantin['nama_kantin'] . "\n";
    echo "Deskripsi: " . substr($kantin['deskripsi'] ?? '', 0, 50) . "...\n";
    echo "Alamat: " . ($kantin['alamat'] ?? 'KOSONG') . "\n";
    echo "Jam Buka: " . ($kantin['jam_buka'] ?? 'N/A') . "\n";
    echo "Jam Tutup: " . ($kantin['jam_tutup'] ?? 'N/A') . "\n";
    echo "Tipe Operasi: " . ($kantin['tipe_operasi'] ?? 'N/A') . "\n";
    echo "Status Buka: " . ($kantin['status_buka'] ?? 'N/A') . "\n";
    echo "Logo: " . ($kantin['logo'] ?? 'N/A') . "\n";
    echo "Banner: " . ($kantin['banner'] ?? 'N/A') . "\n";
    echo "</pre>";
    
    // Test 3: Verifikasi display di pembeli
    echo "<h2>Test 3: Verifikasi Function Helper</h2>";
    echo "<pre>";
    echo "Jam Label: " . kk_kantin_hours_label($kantin) . "\n";
    
    $status_badge = kk_kantin_status_badge($kantin);
    echo "Status Badge: " . $status_badge['status'] . " (is_open: " . ($status_badge['is_open'] ? 'true' : 'false') . ")\n";
    
    $isOpen = kk_is_kantin_open($kantin);
    echo "Is Kantin Open (saat ini): " . ($isOpen ? 'YES' : 'NO') . "\n";
    echo "Current Time: " . date('H:i:s') . "\n";
    echo "</pre>";
    
} else {
    echo "<p>❌ Tidak ada data kantin di database</p>";
}

echo "<hr>";

// Test 4: Cek menu yang terkait
echo "<h2>Test 4: Menu Terkait Kantin</h2>";
if (isset($kantin) && $kantin['id_kantin']) {
    $id_kantin = (int)$kantin['id_kantin'];
    $q_menu = mysqli_query($koneksi, "
        SELECT id_menu, nama_menu, harga, stok, status, kategori
        FROM menu
        WHERE id_kantin = $id_kantin
        LIMIT 5
    ");
    
    if (mysqli_num_rows($q_menu) > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>Menu</th><th>Harga</th><th>Stok</th><th>Status</th><th>Kategori</th></tr>";
        while ($m = mysqli_fetch_assoc($q_menu)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($m['nama_menu']) . "</td>";
            echo "<td>Rp " . number_format($m['harga'], 0, ',', '.') . "</td>";
            echo "<td>" . $m['stok'] . "</td>";
            echo "<td>" . $m['status'] . "</td>";
            echo "<td>" . $m['kategori'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Kantin ini belum punya menu</p>";
    }
}

echo "<hr>";

// Test 5: Summary
echo "<h2>✅ Integration Summary</h2>";
echo "<ul>";
echo "<li><strong>Edit Profil Fields:</strong> username, nama_kantin, alamat, deskripsi, jam_buka, jam_tutup, logo, banner</li>";
echo "<li><strong>Database Storage:</strong> Semua field tersimpan di tabel kantin</li>";
echo "<li><strong>Pembeli Display:</strong> Menggunakan helper functions (kk_kantin_hours_label, kk_kantin_status_badge, kk_is_kantin_open)</li>";
echo "<li><strong>Status Otomatis:</strong> Sistem akan otomatis update status berdasarkan jam operasional</li>";
echo "<li><strong>Menu Integration:</strong> Stok menu akan mempengaruhi status tersedia/habis di pembeli</li>";
echo "</ul>";

// Test 6: Cek perubahan jam buka-tutup
echo "<h2>Test 6: Simulasi Perubahan Jam Operasional</h2>";
if (isset($kantin) && $kantin['id_kantin']) {
    echo "<pre>";
    echo "Contoh jika penjual mengubah jam:\n";
    echo "Jam Buka: 08:00\n";
    echo "Jam Tutup: 14:00\n\n";
    
    $test_kantin = array_merge($kantin, ['jam_buka' => '08:00:00', 'jam_tutup' => '14:00:00']);
    echo "Jam Label yang ditampilkan pembeli: " . kk_kantin_hours_label($test_kantin) . "\n";
    
    $test_badge = kk_kantin_status_badge($test_kantin);
    echo "Status Pembeli saat " . date('H:i') . ": " . $test_badge['status'] . "\n";
    echo "</pre>";
}

?>
<style>
body { font-family: Arial; margin: 20px; }
h1 { color: #d32f2f; }
h2 { color: #1976d2; margin-top: 20px; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { text-align: left; border: 1px solid #ddd; }
th { background: #f5f5f5; font-weight: bold; }
pre { background: #f0f0f0; padding: 10px; border-radius: 5px; overflow-x: auto; }
ul { line-height: 1.8; }
</style>
