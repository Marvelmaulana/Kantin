<?php
/**
 * Registration Process Handler
 * Proses pendaftaran untuk siswa dan guru sebagai pembeli
 * Setelah register, user langsung login dan diarahkan ke dashboard pembeli
 */

session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');
include(__DIR__ . '/../../includes/auth_helpers.php');

kk_ensure_buyer_schema($koneksi);

if (isset($_POST['username']) || isset($_POST['daftar_btn'])) {

    // Ambil input dari form
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $user_type = $_POST['user_type'] ?? 'siswa';
    $kelas = ($_POST['kelas'] ?? '') !== '' ? $_POST['kelas'] : '';

    // 1️⃣ VALIDASI USERNAME
    $validate_username = validate_username($username);
    if (!$validate_username['valid']) {
        echo "<script>alert('" . addslashes($validate_username['error']) . "'); window.location='daftar.php';</script>";
        exit();
    }

    // 2️⃣ VALIDASI EMAIL
    $validate_email = validate_email($email);
    if (!$validate_email['valid']) {
        echo "<script>alert('" . addslashes($validate_email['error']) . "'); window.location='daftar.php';</script>";
        exit();
    }

    // 3️⃣ VALIDASI PASSWORD
    $validate_password = validate_password($password);
    if (!$validate_password['valid']) {
        echo "<script>alert('" . addslashes($validate_password['error']) . "'); window.location='daftar.php';</script>";
        exit();
    }

    // 4️⃣ VALIDASI PASSWORD MATCH
    $validate_match = validate_password_match($password, $confirm_password);
    if (!$validate_match['valid']) {
        echo "<script>alert('" . addslashes($validate_match['error']) . "'); window.location='daftar.php';</script>";
        exit();
    }

    // 5️⃣ VALIDASI TIPE PENGGUNA (hanya siswa dan guru yang boleh daftar)
    if (!in_array($user_type, ['siswa', 'guru'], true)) {
        echo "<script>alert('Tipe pengguna tidak valid!'); window.location='daftar.php';</script>";
        exit();
    }

    // 6️⃣ VALIDASI KELAS (hanya untuk siswa)
    if ($user_type === 'siswa') {
        if (empty($kelas) || !in_array($kelas, ['10', '11', '12'], true)) {
            echo "<script>alert('Pilih kelas dengan benar!'); window.location='daftar.php';</script>";
            exit();
        }
    }

    // 7️⃣ CEK USER SUDAH TERDAFTAR
    $check_exists = user_exists($koneksi, $username, $email);
    if ($check_exists['exists']) {
        if ($check_exists['field'] === 'username') {
            echo "<script>alert('Username sudah terdaftar! Gunakan username lain.'); window.location='daftar.php';</script>";
        } else {
            echo "<script>alert('Email sudah terdaftar! Gunakan email lain atau login.'); window.location='daftar.php';</script>";
        }
        exit();
    }

    // 8️⃣ HASH PASSWORD
    $hashed_password = hash_password($password);

    // 9️⃣ TENTUKAN ROLE (siswa = pembeli, guru = pembeli)
    // Catatan: Penjual tidak bisa daftar mandiri, hanya dibuat oleh admin
    $role = 'pembeli'; // Baik siswa maupun guru menjadi pembeli saat register

    // 🔟 INSERT KE DATABASE
    $username_escaped = mysqli_real_escape_string($koneksi, $username);
    $email_escaped = mysqli_real_escape_string($koneksi, strtolower($email));
    
    $kelas_value = ($user_type === 'siswa' && !empty($kelas)) ? "'" . mysqli_real_escape_string($koneksi, $kelas) . "'" : 'NULL';

    $query = "INSERT INTO users (username, email, password, role, tipe_pengguna, bahasa, kelas)
              VALUES ('$username_escaped', '$email_escaped', '$hashed_password', '$role', '$user_type', 'id', $kelas_value)";

    if (mysqli_query($koneksi, $query)) {
        $user_id = mysqli_insert_id($koneksi);

        // ✅ LANGSUNG LOGIN SETELAH REGISTER
        if (create_user_session([
            'id_user' => $user_id,
            'username' => $username,
            'email' => $email,
            'password' => $hashed_password,
            'role' => $role,
            'tipe_pengguna' => $user_type,
            'bahasa' => 'id',
            'kelas' => ($user_type === 'siswa' ? $kelas : null)
        ])) {
            // ✅ REDIRECT KE DASHBOARD PEMBELI (tanpa login ulang)
            header("Location: loading.php");
            exit();
        } else {
            echo "<script>alert('Pendaftaran berhasil tapi gagal membuat session. Silakan login.'); window.location='login.php';</script>";
            exit();
        }

    } else {
        $error_msg = mysqli_error($koneksi);
        echo "<script>alert('Gagal mendaftar: " . addslashes($error_msg) . "'); window.location='daftar.php';</script>";
        exit();
    }

} else {
    echo "<script>window.location='daftar.php';</script>";
    exit();
}
?>
