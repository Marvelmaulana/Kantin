<?php
/**
 * Login Process Handler
 * Proses login untuk siswa, guru, dan penjual
 */

// Set session timeout 1 jam
ini_set('session.gc_maxlifetime', 3600);

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
        echo "<script>alert(" . json_encode(t('alert.username_or_email_required')) . "); window.location='login.php';</script>";
        exit();
    }

    if (empty($password_raw)) {
        echo "<script>alert(" . json_encode(t('alert.password_required')) . "); window.location='login.php';</script>";
        exit();
    }

    // Escape input untuk keamanan
    $user_input = mysqli_real_escape_string($koneksi, $user_input_raw);

    // Cari user berdasarkan username atau email
    $user_data = get_user_by_username_or_email($koneksi, $user_input);

    if ($user_data) {
        // Prevent admin accounts from logging in via the public login page
        if (($user_data['role'] ?? '') === 'admin') {
            echo "<script>alert(" . json_encode(t('alert.admin_use_admin_login')) . "); window.location='../admin/login.php';</script>";
            exit();
        }

        // Verifikasi password dengan password_verify (secure)
        if (verify_password($password_raw, $user_data['password'])) {
            // ✅ Login berhasil - buat session
            if (create_user_session($user_data)) {
                // Jika penjual, ambil data kantin dari users table
                if (($user_data['role'] ?? '') === 'penjual') {
                    $id_kantin = $user_data['id_kantin'] ?? null;

                    if ($id_kantin) {
                        $query_kantin = mysqli_query($koneksi, "SELECT id_kantin, nama_kantin FROM kantin WHERE id_kantin = $id_kantin LIMIT 1");

                        if ($query_kantin) {
                            $data_kantin = mysqli_fetch_assoc($query_kantin);
                            if ($data_kantin) {
                                $_SESSION['id_kantin'] = $data_kantin['id_kantin'];
                                $_SESSION['nama_kantin'] = $data_kantin['nama_kantin'];
                            } else {
                                echo "<script>alert(" . json_encode(t('alert.canteen_not_found')) . "); window.location='login.php';</script>";
                                exit();
                            }
                        } else {
                            echo "<script>alert(" . json_encode(t('alert.db_query_failed')) . "); window.location='login.php';</script>";
                            exit();
                        }
                    } else {
                        // Penjual tidak punya kantin
                        echo "<script>alert(" . json_encode(t('alert.seller_not_linked')) . "); window.location='login.php';</script>";
                        exit();
                    }
                }

                // Redirect ke loading page yang akan redirect ke dashboard sesuai role
                header("Location: loading.php");
                exit();
            } else {
                echo "<script>alert(" . json_encode(t('alert.session_create_failed')) . "); window.location='login.php';</script>";
                exit();
            }
        } else {
            // ❌ Password salah
            echo "<script>alert(" . json_encode(t('alert.wrong_password_check')) . "); window.location='login.php';</script>";
            exit();
        }
    } else {
        // ❌ User tidak ditemukan
        echo "<script>alert(" . json_encode(t('alert.not_registered')) . "); window.location='login.php';</script>";
        exit();
    }
}
?>