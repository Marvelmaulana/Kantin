<?php
/**
 * Login Process Handler
 * Proses login untuk siswa, guru, dan penjual
 */

// Set session timeout 1 jam
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);

session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');
include(__DIR__ . '/../../includes/auth_helpers.php');

kk_ensure_buyer_schema($koneksi);

if (isset($_POST['login_btn'])) {

    // Ambil input dari form
    $user_input_raw = trim($_POST['user_input'] ?? '');
    $password_raw = trim($_POST['password'] ?? '');

    // Validasi input tidak kosong
    if (empty($user_input_raw)) {
        echo "<script>alert('Username atau Email tidak boleh kosong!'); window.location='login.php';</script>";
        exit();
    }

    if (empty($password_raw)) {
        echo "<script>alert('Password tidak boleh kosong!'); window.location='login.php';</script>";
        exit();
    }

    // Escape input untuk keamanan
    $user_input = mysqli_real_escape_string($koneksi, $user_input_raw);

    // Cari user berdasarkan username atau email
    $user_data = get_user_by_username_or_email($koneksi, $user_input);

    if ($user_data) {
        // Verifikasi password dengan password_verify (secure)
        if (verify_password($password_raw, $user_data['password'])) {
            // ✅ Login berhasil - buat session
            error_log("Proses.php - User found, creating session for user_id: " . $user_data['id_user']);
            error_log("Proses.php - User role: " . $user_data['role']);
            
            if (create_user_session($user_data)) {
                error_log("Proses.php - Session created successfully");
                error_log("Proses.php - SESSION after create: " . json_encode($_SESSION));
                
                // Jika penjual, ambil data kantin
                if ($user_data['role'] === 'penjual') {
                    $id_user = $user_data['id_user'];
                    $query_kantin = mysqli_query($koneksi, "SELECT id_kantin, nama_kantin FROM kantin WHERE id_penjual = '$id_user' LIMIT 1");
                    
                    if ($query_kantin) {
                        $data_kantin = mysqli_fetch_assoc($query_kantin);
                        if ($data_kantin) {
                            $_SESSION['id_kantin'] = $data_kantin['id_kantin'];
                            $_SESSION['nama_kantin'] = $data_kantin['nama_kantin'];
                        } else {
                            // Penjual tidak punya kantin
                            echo "<script>alert('Akun penjual belum memiliki data kantin. Hubungi admin untuk setup kantin Anda.'); window.location='login.php';</script>";
                            exit();
                        }
                    } else {
                        echo "<script>alert('Error: Database query gagal'); window.location='login.php';</script>";
                        exit();
                    }
                }

                // Redirect ke loading page yang akan redirect ke dashboard sesuai role
                error_log("Proses.php - Redirecting to loading.php");
                header("Location: loading.php");
                exit();
            } else {
                echo "<script>alert('Gagal membuat session. Silakan coba lagi.'); window.location='login.php';</script>";
                exit();
            }
        } else {
            // ❌ Password salah
            echo "<script>alert('Password salah! Periksa kembali password Anda.'); window.location='login.php';</script>";
            exit();
        }
    } else {
        // ❌ User tidak ditemukan
        echo "<script>alert('Username atau email tidak terdaftar. Periksa kembali atau daftar akun baru.'); window.location='login.php';</script>";
        exit();
    }
}
?>