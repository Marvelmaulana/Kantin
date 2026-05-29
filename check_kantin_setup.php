<?php
include __DIR__ . '/config/config.php';

echo "<h2>Kantin Table Status</h2>";

// Get all kantin records
$query = "SELECT k.id_kantin, k.id_penjual, k.nama_kantin, u.username 
          FROM kantin k 
          LEFT JOIN users u ON k.id_penjual = u.id_user 
          ORDER BY k.id_kantin";

$result = mysqli_query($koneksi, $query);

if ($result && mysqli_num_rows($result) > 0) {
    echo "<h3>All Kantin Records:</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Kantin ID</th><th>Penjual ID</th><th>Kantin Name</th><th>Penjual Username</th><th>Status</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        $status = $row['id_penjual'] ? '✅ Has Penjual' : '❌ NO PENJUAL';
        $status_color = $row['id_penjual'] ? 'green' : 'red';
        
        echo "<tr>";
        echo "<td>" . $row['id_kantin'] . "</td>";
        echo "<td>" . ($row['id_penjual'] ?? '-') . "</td>";
        echo "<td>" . $row['nama_kantin'] . "</td>";
        echo "<td>" . ($row['username'] ?? '-') . "</td>";
        echo "<td style='color: $status_color;'><strong>$status</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No kantin records found.</p>";
}

?>
