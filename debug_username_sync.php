<?php
// Quick check untuk struktur tabel dan sinkronisasi data
session_start();
include('config/config.php');

echo "<h1>🔍 Cek Struktur & Sinkronisasi Username</h1>";
echo "<hr>";

// 1. Struktur Tabel USERS
echo "<h2>Tabel USERS (untuk Login)</h2>";
$q = mysqli_query($koneksi, "DESCRIBE users");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
while ($row = mysqli_fetch_assoc($q)) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
}
echo "</table>";

echo "<hr>";

// 2. Struktur Tabel KANTIN
echo "<h2>Tabel KANTIN (Profil Kantin)</h2>";
$q = mysqli_query($koneksi, "DESCRIBE kantin");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
while ($row = mysqli_fetch_assoc($q)) {
    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
}
echo "</table>";

echo "<hr>";

// 3. Sample Data
echo "<h2>Sample Data Saat Ini</h2>";
$q_user = mysqli_query($koneksi, "SELECT id_user, username, email FROM users WHERE role='penjual' LIMIT 1");
if ($u = mysqli_fetch_assoc($q_user)) {
    echo "<strong>Users Table:</strong><br>";
    echo "id_user: {$u['id_user']}<br>";
    echo "username: {$u['username']} ← Untuk Login<br>";
    echo "email: {$u['email']}<br>";
    echo "<br>";
    
    $id_penjual = $u['id_user'];
    $q_kantin = mysqli_query($koneksi, "SELECT * FROM kantin WHERE id_penjual = $id_penjual LIMIT 1");
    if ($k = mysqli_fetch_assoc($q_kantin)) {
        echo "<strong>Kantin Table:</strong><br>";
        echo "id_kantin: {$k['id_kantin']}<br>";
        echo "id_penjual: {$k['id_penjual']} (FK ke users.id_user)<br>";
        echo "nama_kantin: {$k['nama_kantin']} ← Nama Toko untuk Pembeli<br>";
        echo "nama_penjual: {$k['nama_penjual']} ← Info Pemilik (Optional)<br>";
        echo "deskripsi: " . substr($k['deskripsi'] ?? '', 0, 50) . "...<br>";
        echo "<br>";
        
        // Cek sinkronisasi
        echo "<h3>❓ Cek Sinkronisasi:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Keterangan</th><th>Nilai</th><th>Status</th></tr>";
        
        $sync_ok = ($u['username'] === $k['nama_penjual']);
        echo "<tr>";
        echo "<td>Username (users) = Nama Penjual (kantin)?</td>";
        echo "<td>{$u['username']} = {$k['nama_penjual']}</td>";
        echo "<td>" . ($sync_ok ? "✅ OK" : "❌ TIDAK SINKRON") . "</td>";
        echo "</tr>";
        
        echo "<tr>";
        echo "<td>Nama Kantin di kantin table</td>";
        echo "<td>{$k['nama_kantin']}</td>";
        echo "<td>✓ (untuk pembeli)</td>";
        echo "</tr>";
        
        echo "</table>";
    }
}

echo "<hr>";
echo "<h2>📋 Penjelasan:</h2>";
echo "<pre>
STRUKTUR YANG BENAR:

Tabel USERS:
  ├─ id_user (PK)
  ├─ username ← Username untuk LOGIN (UNIK)
  ├─ email
  ├─ password
  └─ role (admin, penjual, pembeli)

Tabel KANTIN:
  ├─ id_kantin (PK)
  ├─ id_penjual (FK → users.id_user)
  ├─ nama_kantin ← NAMA TOKO untuk ditampilkan ke PEMBELI
  ├─ nama_penjual ← Nama Pemilik/Penjual (optional, dari username penjual)
  ├─ deskripsi
  ├─ jam_buka, jam_tutup
  └─ ... fields lainnya

UNTUK EDIT PROFIL PENJUAL:
1. Username (dari users) - untuk login, jangan diubah sembarangan
2. Nama Kantin (dari kantin.nama_kantin) - nama yang dilihat pembeli
3. Nama Penjual (dari kantin.nama_penjual) - info siapa pemiliknya
4. Deskripsi, Alamat, Jam Operasional, dll

SINKRONISASI:
- Jika penjual ubah username → update users.username
- Jika penjual ubah nama kantin → update kantin.nama_kantin
- Nama penjual bisa auto-update dari username atau diisi manual
</pre>";

?>
<style>
body { font-family: Arial; margin: 20px; background: #f5f5f5; }
h1 { color: #d32f2f; }
h2 { color: #1976d2; margin-top: 20px; }
h3 { color: #388e3c; }
table { border-collapse: collapse; background: white; margin: 10px 0; }
th { background: #e3f2fd; font-weight: bold; }
td { padding: 8px; border: 1px solid #ddd; }
pre { background: white; padding: 15px; border-radius: 5px; overflow-x: auto; border-left: 4px solid #1976d2; }
</style>
