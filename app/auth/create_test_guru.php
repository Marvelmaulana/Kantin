<?php
/**
 * Create Test Guru User
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include(__DIR__ . '/../../config/config.php');

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$username || !$email || !$password) {
        header('Location: test_guru_login.html?msg=All fields required');
        exit;
    }

    // Check if user already exists
    $check = mysqli_query($koneksi, "SELECT id_user FROM users WHERE username='$username' OR email='$email' LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        header('Location: test_guru_login.html?msg=User already exists');
        exit;
    }

    // Hash password
    $hashed = password_hash($password, PASSWORD_BCRYPT);

    // Insert guru user
    $insert = mysqli_query($koneksi, "INSERT INTO users (username, email, password, role, tipe_pengguna, bahasa) VALUES ('$username', '$email', '$hashed', 'penjual', 'guru', 'id')");

    if ($insert) {
        $msg = "✅ Guru user '$username' created successfully! Password: $password";
        header('Location: test_guru_login.html?msg=' . urlencode($msg));
        exit;
    } else {
        $error = mysqli_error($koneksi);
        header('Location: test_guru_login.html?msg=Error: ' . urlencode($error));
        exit;
    }
} else {
    header('Location: test_guru_login.html');
}
?>
