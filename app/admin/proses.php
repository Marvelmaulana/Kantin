<?php
// Set session timeout 1 jam
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);

session_start();
include(__DIR__ . '/../../config/config.php');

if (isset($_POST['login_btn'])) {
    $user_input_raw = trim($_POST['user_input']);
    $user_input = mysqli_real_escape_string($koneksi, $user_input_raw);
    $password   = mysqli_real_escape_string($koneksi, trim($_POST['password']));

    $query = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$user_input' OR email='$user_input' LIMIT 1");
    $data  = mysqli_fetch_assoc($query);

    if (!$data) {
        echo "<script>alert('User tidak ditemukan!'); window.location='login.php';</script>";
        exit();
    }

    if ($data['role'] !== 'admin') {
        echo "<script>alert('Hanya akun admin yang boleh login di halaman ini.'); window.location='login.php';</script>";
        exit();
    }

    if (!password_verify($password, $data['password'])) {
        echo "<script>alert('Password salah!'); window.location='login.php';</script>";
        exit();
    }

    $_SESSION['id_user']  = $data['id_user'];
    $_SESSION['username'] = $data['username'];
    $_SESSION['role']     = $data['role'];
    $_SESSION['status']   = 'login';
    $bahasa = $data['bahasa'] ?? 'id';
    $_SESSION['lang'] = $bahasa;
    $_SESSION['bahasa'] = $bahasa;

    header('Location: ../auth/loading.php');
    exit();
}

header('Location: login.php');
exit();
