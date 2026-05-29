<?php
include __DIR__ . '/config/config.php';

echo "<h2>Database Users Check</h2>";

// Check if users table exists
$result = mysqli_query($koneksi, "SHOW TABLES LIKE 'users'");
if (!$result || mysqli_num_rows($result) == 0) {
    die("Users table does not exist!");
}

// Get all users
echo "<h3>All Users in Database:</h3>";
$query = "SELECT id_user, username, email, role, tipe_pengguna FROM users ORDER BY role, id_user";
$result = mysqli_query($koneksi, $query);

if ($result && mysqli_num_rows($result) > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Tipe</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['id_user'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "<td>" . ($row['tipe_pengguna'] ?? '-') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No users found in database.</p>";
}

// Check kantin table
echo "<h3>Kantin Data:</h3>";
$query = "SELECT id_kantin, id_penjual, nama_kantin FROM kantin";
$result = mysqli_query($koneksi, $query);

if ($result && mysqli_num_rows($result) > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID Kantin</th><th>ID Penjual</th><th>Nama Kantin</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['id_kantin'] . "</td>";
        echo "<td>" . $row['id_penjual'] . "</td>";
        echo "<td>" . $row['nama_kantin'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No kantin data found.</p>";
}

echo "<h3>Database Status:</h3>";
echo "<p>Connection: " . ($koneksi ? "OK" : "FAILED") . "</p>";
?>
