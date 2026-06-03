<?php
// Update admin and seller passwords
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_kantin";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Update admin password to "admin123"
$admin_pwd = password_hash("admin123", PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
$stmt->bind_param("s", $admin_pwd);
$stmt->execute();
echo "Admin password updated: " . $stmt->affected_rows . " rows\n";
$stmt->close();

// Update kantin_demo password to "kantin123"
$seller_pwd = password_hash("kantin123", PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = 'kantin_demo'");
$stmt->bind_param("s", $seller_pwd);
$stmt->execute();
echo "Kantin Demo password updated: " . $stmt->affected_rows . " rows\n";
$stmt->close();

// Update buyer password to "buyer123"
$buyer_pwd = password_hash("buyer123", PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = 'buyer'");
$stmt->bind_param("s", $buyer_pwd);
$stmt->execute();
echo "Buyer password updated: " . $stmt->affected_rows . " rows\n";
$stmt->close();

$conn->close();
echo "\nAll passwords updated successfully!";
?>
