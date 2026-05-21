<?php
session_start();
include(__DIR__ . '/../../config/config.php');

if (isset($_POST['daftar_btn'])) {
    $username = mysqli_real_escape_string($koneksi, trim($_POST['username']));
    $email    = mysqli_real_escape_string($koneksi, trim($_POST['email']));
    $password = $_POST['password'];

    if (empty($username) || empty($email) || empty($password)) {
        echo "<script>alert('Semua field harus diisi!'); window.location='daftar.php';</script>";
        exit();
    }

    if (strlen($password) < 6) {
        echo "<script>alert('Password minimal 6 karakter!'); window.location='daftar.php';</script>";
        exit();
    }

    $password_aman = password_hash($password, PASSWORD_DEFAULT);
    $role = 'pembeli';

    $cek_user = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username' OR email='$email'");

    if (mysqli_num_rows($cek_user) > 0) {
        echo "<script>alert('Username atau Email sudah terdaftar!'); window.location='daftar.php';</script>";
    } else {
        $query = "INSERT INTO users (username, email, password, role, bahasa)
                  VALUES ('$username', '$email', '$password_aman', '$role', 'id')";

        if (mysqli_query($koneksi, $query)) {
            $user_id = mysqli_insert_id($koneksi);

            $_SESSION['id_user']  = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['role']     = $role;
            $_SESSION['status']   = "login";
            $_SESSION['lang']     = 'id';
            $_SESSION['bahasa']   = 'id';

            header("Location: loading.php");
            exit();
        } else {
            echo "Gagal mendaftar: " . mysqli_error($koneksi);
        }
    }
}
?>
