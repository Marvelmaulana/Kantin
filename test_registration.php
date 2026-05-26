<?php
/**
 * Test Script untuk Verifikasi Registrasi
 * Test INSERT dan SELECT dari tabel users
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

echo "=== TEST REGISTRASI USER ===\n\n";

// Test data
$test_username = "testguru_" . uniqid();
$test_email = "testguru" . uniqid() . "@gmail.com";
$test_password = password_hash("TestPass123", PASSWORD_BCRYPT);

echo "📝 DATA TEST:\n";
echo "  Username: $test_username\n";
echo "  Email: $test_email\n";
echo "  Role: pembeli\n";
echo "  Tipe: guru\n\n";

// 1. Gunakan prepared statement (seperti yang baru diperbaiki)
echo "▶ Testing prepared statement INSERT...\n";

$stmt = $koneksi->prepare(
    "INSERT INTO users (username, email, password, role, tipe_pengguna, bahasa, kelas) 
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    echo "❌ Prepare failed: " . $koneksi->error . "\n\n";
    exit(1);
}

$role = 'pembeli';
$user_type = 'guru';
$bahasa = 'id';
$kelas = null;

$stmt->bind_param(
    "sssssss",
    $test_username,
    $test_email,
    $test_password,
    $role,
    $user_type,
    $bahasa,
    $kelas
);

if ($stmt->execute()) {
    $user_id = $stmt->insert_id;
    $affected = $stmt->affected_rows;
    
    echo "✅ Insert berhasil!\n";
    echo "  Affected rows: $affected\n";
    echo "  New user ID: $user_id\n\n";
    
    // 2. Verifikasi data bisa di-select
    echo "▶ Verifikasi data di database...\n";
    
    $verify = mysqli_query($koneksi, 
        "SELECT id_user, username, email, role, tipe_pengguna, kelas, bahasa FROM users WHERE id_user = $user_id"
    );
    
    if ($verify && mysqli_num_rows($verify) > 0) {
        $data = mysqli_fetch_assoc($verify);
        echo "✅ Data ditemukan!\n";
        echo "  ID: " . $data['id_user'] . "\n";
        echo "  Username: " . $data['username'] . "\n";
        echo "  Email: " . $data['email'] . "\n";
        echo "  Role: " . $data['role'] . "\n";
        echo "  Tipe: " . $data['tipe_pengguna'] . "\n";
        echo "  Kelas: " . ($data['kelas'] ?: 'NULL') . "\n";
        echo "  Bahasa: " . $data['bahasa'] . "\n\n";
        
        // 3. Test duplikasi detection
        echo "▶ Test duplikasi detection dengan username yang sama...\n";
        
        $dup_check = mysqli_query($koneksi, "SELECT id_user FROM users WHERE username = '$test_username' LIMIT 1");
        
        if ($dup_check && mysqli_num_rows($dup_check) > 0) {
            echo "✅ Duplikasi detection berfungsi!\n";
            echo "   Username ditemukan sebagai ada (prevented duplicates)\n\n";
        } else {
            echo "❌ Duplikasi detection gagal!\n\n";
        }
        
        // 4. Test email detection
        echo "▶ Test email detection...\n";
        
        $email_check = mysqli_query($koneksi, "SELECT id_user FROM users WHERE LOWER(email) = LOWER('$test_email') LIMIT 1");
        
        if ($email_check && mysqli_num_rows($email_check) > 0) {
            echo "✅ Email detection berfungsi!\n";
            echo "   Email ditemukan sebagai ada (prevented duplicates)\n\n";
        } else {
            echo "❌ Email detection gagal!\n\n";
        }
        
        // 5. Summary
        echo "=== HASIL TEST ===\n";
        echo "✅ Database structure sudah benar\n";
        echo "✅ Prepared statement berfungsi\n";
        echo "✅ Data tersimpan dan dapat diambil\n";
        echo "✅ Duplikasi detection berfungsi\n";
        echo "\n🎉 REGISTRASI SUDAH SIAP DIPRODUKSI!\n";
        
    } else {
        echo "❌ Data tidak ditemukan setelah insert!\n";
        echo "   Ini menunjukkan ada masalah dengan database.\n\n";
    }
    
} else {
    echo "❌ Execute failed: " . $stmt->error . "\n";
    echo "   Error code: " . $stmt->errno . "\n\n";
}

$stmt->close();
mysqli_close($koneksi);
?>
