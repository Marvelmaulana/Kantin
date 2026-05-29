<?php
include __DIR__ . '/config/config.php';

echo "<h2>Seller Login Verification</h2>";

// Get all sellers
$query = "SELECT u.id_user, u.username, u.email, k.id_kantin, k.nama_kantin 
          FROM users u 
          LEFT JOIN kantin k ON u.id_user = k.id_penjual 
          WHERE u.role = 'penjual' 
          ORDER BY u.id_user";

$result = mysqli_query($koneksi, $query);

if ($result && mysqli_num_rows($result) > 0) {
    echo "<h3>All Sellers Status:</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Has Kantin?</th><th>Kantin ID</th><th>Kantin Name</th><th>Status</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        $status = $row['id_kantin'] ? '✅ OK' : '❌ NO KANTIN';
        $status_color = $row['id_kantin'] ? 'green' : 'red';
        
        echo "<tr>";
        echo "<td>" . $row['id_user'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . ($row['id_kantin'] ? 'Yes' : 'No') . "</td>";
        echo "<td>" . ($row['id_kantin'] ?? '-') . "</td>";
        echo "<td>" . ($row['nama_kantin'] ?? '-') . "</td>";
        echo "<td style='color: $status_color;'><strong>$status</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Login Test Instructions:</h3>";
    echo "<ol>";
    echo "<li>Use one of the sellers above with an established kantin (✅ OK)</li>";
    echo "<li>You may need to reset their password first if you don't know it</li>";
    echo "<li>Or use the test seller: <strong>test_penjual / password123</strong></li>";
    echo "</ol>";
    
} else {
    echo "<p>No sellers found in database.</p>";
}

?>
