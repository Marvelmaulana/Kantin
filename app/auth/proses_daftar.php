<?php
session_start(); 

// Pastikan path ini sesuai dengan letak file config.php kamu di folder XAMPP
include(__DIR__ . '/../../config/config.php');

if (isset($_POST['daftar_btn'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $email    = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = $_POST['password']; // Ambil password asli dulu
    
    // Keamanan: Hash Password
    $password_aman = password_hash($password, PASSWORD_DEFAULT);
    
    $role = 'pembeli';

    // Cek duplikasi (Username ATAU Email)
    $cek_user = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username' OR email='$email'");
    
    if (mysqli_num_rows($cek_user) > 0) {
        echo "<script>alert('Username atau Email sudah terdaftar!'); window.location='daftar.php';</script>";
    } else {
        // Simpan ke database
        $query = "INSERT INTO users (username, email, password, role) 
                  VALUES ('$username', '$email', '$password_aman', '$role')";
        
        if (mysqli_query($koneksi, $query)) {
            // Ambil ID yang baru saja dibuat
            $user_id = mysqli_insert_id($koneksi);

            // Set Session untuk Login Otomatis
            $_SESSION['id_user']  = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['role']     = $role;
            $_SESSION['status']   = "login";

            // Langsung ke loading.php
            header("Location: loading.php");
            exit(); 
            
        } else {
            echo "Gagal mendaftar: " . mysqli_error($koneksi);
        }
    }
    // Tutup koneksi (Opsional tapi baik dilakukan)
    mysqli_close($koneksi);
}
?>