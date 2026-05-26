<?php
include 'config/config.php';

echo "<pre>";
echo "Creating Test Guru Penjual Account...\n\n";

// Check if already exists
$check = mysqli_query($koneksi, "SELECT * FROM users WHERE username='guru_test' LIMIT 1");
if (mysqli_num_rows($check) > 0) {
    echo "✗ Account 'guru_test' already exists\n";
    $existing = mysqli_fetch_assoc($check);
    echo "   - ID: " . $existing['id_user'] . "\n";
    echo "   - Email: " . $existing['email'] . "\n";
    echo "   - Role: " . $existing['role'] . "\n";
    echo "   - Tipe: " . $existing['tipe_pengguna'] . "\n";
} else {
    // Create new guru penjual account
    $username = 'guru_test';
    $email = 'guru_test@school.local';
    $password = 'guru123456'; // Plain password for demo
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 'penjual';
    $tipe_pengguna = 'guru';
    $nip = 'NIP123456789';
    
    $insert = mysqli_query($koneksi, "
        INSERT INTO users (username, email, password, role, tipe_pengguna, nip)
        VALUES ('$username', '$email', '$hashed_password', '$role', '$tipe_pengguna', '$nip')
    ");
    
    if ($insert) {
        $new_id = mysqli_insert_id($koneksi);
        echo "✓ Guru Penjual account created successfully!\n\n";
        echo "Account Details:\n";
        echo "- ID: " . $new_id . "\n";
        echo "- Username: " . $username . "\n";
        echo "- Email: " . $email . "\n";
        echo "- Password: " . $password . "\n";
        echo "- Role: " . $role . "\n";
        echo "- Type (Tipe): " . $tipe_pengguna . "\n";
        echo "- NIP: " . $nip . "\n\n";
        echo "You can now test login with:\n";
        echo "- Username/Email: " . $username . "\n";
        echo "- Password: " . $password . "\n";
    } else {
        echo "✗ Error creating account: " . mysqli_error($koneksi) . "\n";
    }
}

echo "</pre>";
?>
