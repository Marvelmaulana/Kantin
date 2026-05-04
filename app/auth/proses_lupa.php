<?php
include 'config.php';

if (isset($_POST['cek_lupa'])) {
    $user = $_POST['username'];
    $jawaban = $_POST['jawaban'];

    // Cek di database
    $query = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$user' AND jawaban_keamanan='$jawaban'");
    
    if (mysqli_num_rows($query) > 0) {
        // Jika cocok, arahkan ke halaman ganti password baru
        // Kita simpan username di session sementara
        session_start();
        $_SESSION['reset_user'] = $user;
        header("Location: ganti_password_baru.php");
    } else {
        echo "<script>alert('Data tidak cocok! Silakan hubungi admin sekolah.'); window.location='lupa_password.php';</script>";
    }
}
?>