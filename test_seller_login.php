<?php
include __DIR__ . '/config/config.php';
include __DIR__ . '/includes/auth_helpers.php';
include __DIR__ . '/includes/pembeli_helpers.php';

echo "<h2>Seller Login Test</h2>";

// First, let's create a test seller if it doesn't exist
$test_seller_username = "test_penjual";
$test_seller_email = "test_penjual@gmail.com";
$test_seller_password = "password123";

// Check if test seller exists
$existing = get_user_by_username_or_email($koneksi, $test_seller_username);

if (!$existing) {
    echo "<h3>Creating Test Seller...</h3>";
    $hashed_password = hash_password($test_seller_password);
    $query = "INSERT INTO users (username, email, password, role, tipe_pengguna) VALUES 
              ('$test_seller_username', '$test_seller_email', '$hashed_password', 'penjual', 'guru')";
    
    if (mysqli_query($koneksi, $query)) {
        $seller_id = mysqli_insert_id($koneksi);
        echo "<p>✓ Test seller created: ID = $seller_id</p>";
        
        // Also create a kantin for this seller
        $kantin_query = "INSERT INTO kantin (id_penjual, nama_kantin) VALUES ('$seller_id', 'Kantin Test Penjual')";
        if (mysqli_query($koneksi, $kantin_query)) {
            echo "<p>✓ Test kantin created</p>";
        } else {
            echo "<p>✗ Failed to create test kantin: " . mysqli_error($koneksi) . "</p>";
        }
    } else {
        echo "<p>✗ Failed to create test seller: " . mysqli_error($koneksi) . "</p>";
    }
} else {
    echo "<p>Test seller already exists</p>";
}

echo "<h3>Test Seller Credentials:</h3>";
echo "<p><strong>Username:</strong> " . $test_seller_username . "</p>";
echo "<p><strong>Email:</strong> " . $test_seller_email . "</p>";
echo "<p><strong>Password:</strong> " . $test_seller_password . "</p>";

// Now let's try to authenticate
echo "<h3>Login Test:</h3>";

// Simulate the login process
$user_data = get_user_by_username_or_email($koneksi, $test_seller_username);

if ($user_data) {
    echo "<p>✓ User found in database</p>";
    echo "<p>Role: " . $user_data['role'] . "</p>";
    echo "<p>Tipe Pengguna: " . ($user_data['tipe_pengguna'] ?? '-') . "</p>";
    
    // Try to verify password
    if (verify_password($test_seller_password, $user_data['password'])) {
        echo "<p>✓ Password verified</p>";
        
        // Check if kantin exists
        $id_user = $user_data['id_user'];
        $query_kantin = mysqli_query($koneksi, "SELECT id_kantin, nama_kantin FROM kantin WHERE id_penjual = '$id_user' LIMIT 1");
        
        if ($query_kantin) {
            $data_kantin = mysqli_fetch_assoc($query_kantin);
            if ($data_kantin) {
                echo "<p>✓ Kantin found: " . $data_kantin['nama_kantin'] . " (ID: " . $data_kantin['id_kantin'] . ")</p>";
                echo "<p style='color: green;'><strong>Login should be successful!</strong></p>";
            } else {
                echo "<p>✗ No kantin data found for this seller</p>";
                echo "<p style='color: red;'><strong>Login would fail - seller has no kantin assigned</strong></p>";
            }
        }
    } else {
        echo "<p>✗ Password verification failed</p>";
    }
} else {
    echo "<p>✗ User not found in database</p>";
}

echo "<h3>Instructions:</h3>";
echo "<ol>";
echo "<li>Go to <a href='app/auth/login.php'>Login Page</a></li>";
echo "<li>Use username: <code>" . $test_seller_username . "</code></li>";
echo "<li>Use password: <code>" . $test_seller_password . "</code></li>";
echo "<li>Check if login succeeds or what error appears</li>";
echo "</ol>";

?>
