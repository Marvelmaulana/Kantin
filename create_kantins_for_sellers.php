<?php
include __DIR__ . '/config/config.php';

echo "<h2>Manual Kantin Assignment</h2>";

// Get all sellers without kantin
$query = "SELECT u.id_user, u.username, u.email 
          FROM users u 
          LEFT JOIN kantin k ON u.id_user = k.id_penjual 
          WHERE u.role = 'penjual' 
          AND (k.id_kantin IS NULL) 
          ORDER BY u.id_user";

$result = mysqli_query($koneksi, $query);

if ($result && mysqli_num_rows($result) > 0) {
    echo "<h3>Sellers Without Kantin:</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Action</th></tr>";
    
    while ($seller = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $seller['id_user'] . "</td>";
        echo "<td>" . $seller['username'] . "</td>";
        echo "<td>" . $seller['email'] . "</td>";
        
        // Create a simple kantin for them
        $kantin_name = "Kantin " . $seller['username'];
        
        // Check if kantin already exists
        $check_query = "SELECT id_kantin FROM kantin WHERE id_penjual = {$seller['id_user']}";
        $check_result = mysqli_query($koneksi, $check_query);
        
        if (mysqli_num_rows($check_result) == 0) {
            // Create kantin
            $insert_query = "INSERT INTO kantin (id_penjual, nama_kantin) VALUES ({$seller['id_user']}, '$kantin_name')";
            if (mysqli_query($koneksi, $insert_query)) {
                $new_id = mysqli_insert_id($koneksi);
                echo "<td><span style='color: green;'>✅ Created Kantin #$new_id</span></td>";
            } else {
                echo "<td><span style='color: red;'>❌ Error: " . mysqli_error($koneksi) . "</span></td>";
            }
        } else {
            echo "<td><span style='color: blue;'>ℹ️ Kantin exists</span></td>";
        }
        
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><strong>Note:</strong> Kantins have been auto-created for sellers. They can now login!</p>";
    echo "<p><a href='verify_seller_setup.php'>Verify Setup</a></p>";
    
} else {
    echo "<p>All sellers already have kantins assigned!</p>";
}

?>
