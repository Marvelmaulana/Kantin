<?php
/**
 * Registration Process Handler (IMPROVED)
 * Proses pendaftaran untuk siswa dan guru sebagai pembeli
 * Fitur: Error handling, prepared statements, detailed logging
 */

session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');
include(__DIR__ . '/../../includes/auth_helpers.php');

kk_ensure_buyer_schema($koneksi);

// Fungsi untuk logging dan error handling
function log_registration_error($username, $message) {
    $log_file = __DIR__ . '/../../logs/registration_error.log';
    if (!is_dir(dirname($log_file))) {
        mkdir(dirname($log_file), 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] Username: $username | Error: $message\n";
    error_log($log_entry, 3, $log_file);
}

// Cek database connection
if (!$koneksi || mysqli_connect_errno()) {
    log_registration_error('unknown', 'Database connection failed: ' . mysqli_connect_error());
    echo "<script>alert('⚠️ Koneksi database gagal. Silakan coba lagi nanti.'); window.location='daftar.php';</script>";
    exit();
}

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
        log_registration_error($username, 'Invalid user type: ' . $user_type);
        echo "<script>alert('Tipe pengguna tidak valid!'); window.location='daftar.php';</script>";
        exit();
    }

    // 6️⃣ VALIDASI KELAS (hanya untuk siswa)
    if ($user_type === 'siswa') {
        if (empty($kelas) || !in_array($kelas, ['10', '11', '12'], true)) {
            log_registration_error($username, 'Invalid kelas for siswa: ' . $kelas);
            echo "<script>alert('Pilih kelas dengan benar!'); window.location='daftar.php';</script>";
            exit();
        }
    }

    // 7️⃣ CEK USER SUDAH TERDAFTAR (menggunakan prepared statement untuk safety)
    $check_exists = user_exists($koneksi, $username, $email);
    if ($check_exists['exists']) {
        if ($check_exists['field'] === 'username') {
            log_registration_error($username, 'Username already exists in database');
            echo "<script>alert('Username sudah terdaftar! Gunakan username lain.'); window.location='daftar.php';</script>";
        } else {
            log_registration_error($username, 'Email already exists in database: ' . $email);
            echo "<script>alert('Email sudah terdaftar! Gunakan email lain atau login.'); window.location='daftar.php';</script>";
        }
        exit();
    }

    // 8️⃣ HASH PASSWORD
    $hashed_password = hash_password($password);

    // 9️⃣ TENTUKAN ROLE (siswa = pembeli, guru = pembeli)
    $role = 'pembeli';

    // 🔟 INSERT KE DATABASE dengan PREPARED STATEMENT (lebih aman)
    $email_lower = strtolower($email);
    $kelas_for_db = ($user_type === 'siswa' && !empty($kelas)) ? $kelas : null;

    // Gunakan prepared statement untuk keamanan
    $stmt = $koneksi->prepare(
        "INSERT INTO users (username, email, password, role, tipe_pengguna, bahasa, kelas) 
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        log_registration_error($username, 'Prepare statement failed: ' . $koneksi->error);
        echo "<script>alert('⚠️ Terjadi kesalahan sistem. Silakan coba lagi.'); window.location='daftar.php';</script>";
        exit();
    }

    // Bind parameters
    $stmt->bind_param(
        "sssssss",
        $username,
        $email_lower,
        $hashed_password,
        $role,
        $user_type,
        $bahasa_value,
        $kelas_for_db
    );

    $bahasa_value = 'id';

    // Execute statement
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        $affected_rows = $stmt->affected_rows;

        // Verifikasi data tersimpan
        if ($affected_rows > 0 && $user_id > 0) {
            
            // ✅ LANGSUNG LOGIN SETELAH REGISTER
            if (create_user_session([
                'id_user' => $user_id,
                'username' => $username,
                'email' => $email_lower,
                'password' => $hashed_password,
                'role' => $role,
                'tipe_pengguna' => $user_type,
                'bahasa' => 'id',
                'kelas' => ($user_type === 'siswa' ? $kelas : null)
            ])) {
                // Log successful registration
                error_log("[" . date('Y-m-d H:i:s') . "] SUCCESS: User registered - ID: $user_id, Username: $username, Email: $email_lower, Type: $user_type\n", 3, __DIR__ . '/../../logs/registration_success.log');
                
                // ✅ REDIRECT KE DASHBOARD PEMBELI
                header("Location: loading.php");
                exit();
            } else {
                log_registration_error($username, 'Failed to create user session after insert');
                echo "<script>alert('Pendaftaran berhasil tapi gagal membuat session. Silakan login.'); window.location='login.php';</script>";
                exit();
            }
        } else {
            log_registration_error($username, "Insert executed but affected_rows=$affected_rows, insert_id=$user_id");
            echo "<script>alert('⚠️ Data gagal tersimpan. Silakan coba lagi.'); window.location='daftar.php';</script>";
            exit();
        }

    } else {
        $error_msg = $stmt->error;
        log_registration_error($username, "Database error: $error_msg");
        
        // Tampilkan error yang lebih detail untuk debugging
        echo "<script>alert('Gagal mendaftar: " . addslashes($error_msg) . "'); window.location='daftar.php';</script>";
        exit();
    }

    $stmt->close();

} else {
    echo "<script>window.location='daftar.php';</script>";
    exit();
}
?>
