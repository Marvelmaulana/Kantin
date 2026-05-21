<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');
kk_ensure_buyer_schema($koneksi);

if (!isset($_POST['cek_lupa'])) {
    header("Location: lupa_password.php");
    exit();
}

$email = mysqli_real_escape_string($koneksi, trim($_POST['email'] ?? ''));
$q = mysqli_query($koneksi, "SELECT id_user, email FROM users WHERE email='$email' LIMIT 1");

if (!$q || mysqli_num_rows($q) === 0) {
    header("Location: lupa_password.php?error=email");
    exit();
}

$user = mysqli_fetch_assoc($q);
$id_user = (int)$user['id_user'];
$token = bin2hex(random_bytes(16));
$expired = date('Y-m-d H:i:s', time() + 3600);

mysqli_query($koneksi, "UPDATE users SET reset_token='$token', reset_expired='$expired' WHERE id_user=$id_user");
mysqli_query($koneksi, "INSERT INTO password_resets (id_user, email, token, expired_at) VALUES ($id_user, '$email', '$token', '$expired')");

header("Location: reset_password.php?token=$token");
exit();
?>
