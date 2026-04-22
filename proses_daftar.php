<?php
// 1. Memulai session agar bisa login otomatis setelah data berhasil disimpan
session_start(); 

// 2. Menghubungkan ke database (pastikan file config.php sudah benar)
include 'config.php'; 

if (isset($_POST['daftar_btn'])) {
    // 3. Ambil data dari form dan bersihkan (Mencegah SQL Injection sederhana)
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $email    = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']); // Tanpa Hash (Teks Biasa)
    
    // 4. Set Role otomatis sebagai 'pembeli'
    $role = 'pembeli';

    // 5. Cek apakah username atau email sudah ada di database
    $cek_user = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username' OR email='$email'");
    
    if (mysqli_num_rows($cek_user) > 0) {
        // Jika sudah ada, beri peringatan
        echo "<script>alert('Username atau Email sudah terdaftar! Gunakan yang lain.'); window.location='daftar.php';</script>";
    } else {
        // 6. Masukkan data user baru ke tabel users
        $query = "INSERT INTO users (username, email, password, role) 
                  VALUES ('$username', '$email', '$password', '$role')";
        
        if (mysqli_query($koneksi, $query)) {
            // --- LOGIKA LOGIN OTOMATIS (SESSION) ---
            
            // Mengambil ID user yang baru saja masuk ke database
            $user_id = mysqli_insert_id($koneksi);

            // Simpan data ke dalam $_SESSION agar user dianggap sudah login
            $_SESSION['id_user']  = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['role']     = $role;
            $_SESSION['status']   = "login";

            // 7. ARAHKAN KE loading.php
            header("Location: loading.php");
            exit(); 
            
        } else {
            // Jika ada kesalahan pada database
            echo "Gagal mendaftar: " . mysqli_error($koneksi);
        }
    }
}
?>