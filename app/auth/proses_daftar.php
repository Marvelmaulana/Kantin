<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');
kk_ensure_buyer_schema($koneksi);

if (isset($_POST['username']) || isset($_POST['daftar_btn'])) {
    $username = mysqli_real_escape_string($koneksi, trim($_POST['username'] ?? ''));
    $email    = mysqli_real_escape_string($koneksi, trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $user_type = $_POST['user_type'] ?? 'siswa';
    $kelas    = ($_POST['kelas'] ?? '') !== '' ? $_POST['kelas'] : '';

    if (empty($username) || empty($email) || empty($password)) {
        echo "<script>alert('Semua field harus diisi!'); window.location='daftar.php';</script>";
        exit();
    }

    // ✅ Validasi username: hanya boleh huruf, angka, spasi, titik, dan garis bawah
    if (!preg_match('/^[a-zA-Z0-9._\s]+$/', $username)) {
        echo "<script>alert('Nama pengguna hanya boleh berisi huruf, angka, spasi, titik, dan garis bawah.'); window.location='daftar.php';</script>";
        exit();
    }

    if (strlen($username) < 3) {
        echo "<script>alert('Username minimal 3 karakter!'); window.location='daftar.php';</script>";
        exit();
    }

    if (strlen($password) < 8) {
        echo "<script>alert('Password minimal 8 karakter!'); window.location='daftar.php';</script>";
        exit();
    }

    // Validasi email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Format email tidak valid!'); window.location='daftar.php';</script>";
        exit();
    }

    // Validasi kelas hanya untuk siswa
    if ($user_type === 'siswa') {
        if (empty($kelas) || !in_array($kelas, ['10', '11', '12'], true)) {
            echo "<script>alert('Pilih kelas siswa dengan benar.'); window.location='daftar.php';</script>";
            exit();
        }
    }

    $password_aman = password_hash($password, PASSWORD_DEFAULT);
    
    // Role berdasarkan user_type
    $role = ($user_type === 'siswa') ? 'pembeli' : 'penjual';

    $cek_user = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username' OR email='$email'");

    if (mysqli_num_rows($cek_user) > 0) {
        echo "<script>alert('Username atau Email sudah terdaftar!'); window.location='daftar.php';</script>";
    } else {
        // Insert dengan kelas yang nullable untuk guru
        $kelas_value = $kelas !== '' ? "'$kelas'" : 'NULL';
        $query = "INSERT INTO users (username, email, password, role, bahasa, kelas)
                  VALUES ('$username', '$email', '$password_aman', '$role', 'id', $kelas_value)";

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
            echo "<script>alert('Gagal mendaftar: " . mysqli_error($koneksi) . "'); window.location='daftar.php';</script>";
        }
    }
}
?>
