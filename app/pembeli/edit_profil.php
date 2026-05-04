<?php
session_start();
// Tambahkan baris ini untuk melihat error jika ada
error_reporting(E_ALL);
ini_set('display_errors', 1);

include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user'])) {
    die("Error: Anda belum login!");
}

$id_user = $_SESSION['id_user'];
$query = mysqli_query($koneksi, "SELECT * FROM users WHERE id_user = '$id_user'");
$user = mysqli_fetch_assoc($query);

// Jika tombol Simpan ditekan
if (isset($_POST['update'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['username']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    
    $update = mysqli_query($koneksi, "UPDATE users SET username='$nama', email='$email' WHERE id_user='$id_user'");
    if ($update) {
        echo "<script>alert('Profil Berhasil Diubah!'); window.location='profil.php';</script>";
    } else {
        echo "Gagal Update: " . mysqli_error($koneksi);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Profil</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-md mx-auto bg-white p-6 rounded-xl shadow-md">
        <h2 class="text-xl font-bold mb-4">Edit Profil</h2>
        <form method="POST">
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Username</label>
                <input type="text" name="username" value="<?= $user['username'] ?>" class="w-full border p-2 rounded">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Email</label>
                <input type="email" name="email" value="<?= $user['email'] ?>" class="w-full border p-2 rounded">
            </div>
            <button type="submit" name="update" class="bg-orange-600 text-white px-4 py-2 rounded">Simpan Perubahan</button>
            <a href="profil.php" class="text-gray-500 ml-4">Batal</a>
        </form>
    </div>
</body>
</html>