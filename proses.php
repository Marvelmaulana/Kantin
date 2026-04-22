<?php
session_start();
include 'config.php';

if (isset($_POST['login_btn'])) {
    $user_input = mysqli_real_escape_string($koneksi, $_POST['user_input']);
    $password   = mysqli_real_escape_string($koneksi, $_POST['password']);
    $role_pilih = $_POST['role']; 

    // 1. CEK USERNAME / EMAIL DULU
    // Kita cari apakah user dengan identitas tersebut ada di database
    $cek_user = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$user_input' OR email='$user_input'");
    $data = mysqli_fetch_assoc($cek_user);

    if (mysqli_num_rows($cek_user) > 0) {
        
        // 2. JIKA USER ADA, CEK PASSWORDNYA
        if ($data['password'] == $password) {
            
            // 3. JIKA PASSWORD BENAR, CEK ROLENYA
            if ($data['role'] == $role_pilih) {
                
                // LOGIN BERHASIL!
                $_SESSION['id_user']   = $data['id_user'];
                $_SESSION['username']  = $data['username'];
                $_SESSION['role']      = $data['role'];
                $_SESSION['status']    = "login";

                header("Location: loading.php");
                exit();

            } else {
                // Notif jika role tidak sesuai (misal pembeli mencoba login sebagai admin)
                echo "<script>alert('Gagal! Anda terdaftar sebagai " . $data['role'] . ", bukan $role_pilih.'); window.location='login.php';</script>";
            }

        } else {
            // Notif jika password salah
            echo "<script>alert('Gagal! Password yang Anda masukkan salah.'); window.location='login.php';</script>";
        }

    } else {
        // Notif jika username/email tidak ditemukan
        echo "<script>alert('Gagal! Username atau Email tidak terdaftar.'); window.location='login.php';</script>";
    }
}
?>