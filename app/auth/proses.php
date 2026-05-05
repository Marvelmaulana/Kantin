<?php
// Set session timeout 1 jam
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);

session_start();
include(__DIR__ . '/../../config/config.php');

if (isset($_POST['login_btn'])) {

    $user_input = mysqli_real_escape_string($koneksi, trim($_POST['user_input']));
    $password   = mysqli_real_escape_string($koneksi, trim($_POST['password'])); 

    // Mencari user berdasarkan username atau email
    $query = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$user_input' OR email='$user_input' LIMIT 1");
    $data  = mysqli_fetch_assoc($query);

    if ($data) {
        // Verifikasi Password
        if (password_verify($password, $data['password'])) {

            // 1. Set Session Dasar (Role diambil otomatis dari Database)
            $_SESSION['id_user']  = $data['id_user'];
            $_SESSION['username'] = $data['username'];
            $_SESSION['role']     = $data['role']; // Role: pembeli/penjual/admin
            $_SESSION['status']   = "login";

            // 2. LOGIKA KHUSUS PENJUAL: Ambil id_kantin
            if ($data['role'] === 'penjual') {
                $id_user = $data['id_user'];
                $query_kantin = mysqli_query($koneksi, "SELECT id_kantin FROM kantin WHERE id_user = '$id_user'");
                $data_kantin = mysqli_fetch_assoc($query_kantin);
                
                if ($data_kantin) {
                    $_SESSION['id_kantin'] = $data_kantin['id_kantin'];
                } else {
                    // Jika rolenya penjual tapi belum ada data di tabel kantin
                    echo "<script>alert('Akun penjual belum memiliki data kantin!'); window.location='login.php';</script>";
                    exit();
                }
            }

            // Redirect ke loading.php (nanti di loading.php baru di arahkan sesuai role)
            header("Location: loading.php");
            exit();

        } else {
            echo "<script>alert('Password salah!'); window.location='login.php';</script>";
        }
    } else {
        echo "<script>alert('User tidak ditemukan!'); window.location='login.php';</script>";
    }
}
?>