<?php
// Set session timeout 1 jam
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);

session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');
kk_ensure_buyer_schema($koneksi);

if (isset($_POST['login_btn'])) {

    // Simplified login - only username/email and password
    $user_input_raw = trim($_POST['user_input'] ?? '');
    $user_input = mysqli_real_escape_string($koneksi, $user_input_raw);
    $password = mysqli_real_escape_string($koneksi, trim($_POST['password'] ?? ''));

    if (!$user_input || !$password) {
        echo "<script>alert('Username/Email dan Password tidak boleh kosong!'); window.location='login.php';</script>";
        exit();
    }

    // Try to find user (both siswa pembeli and guru penjual)
    $query = mysqli_query($koneksi, "SELECT * FROM users WHERE (username='$user_input' OR email='$user_input') LIMIT 1");
    $data = mysqli_fetch_assoc($query);

    if ($data) {
        // Verify Password
        if (password_verify($password, $data['password'])) {
            // Login successful
            $_SESSION['id_user']  = $data['id_user'];
            $_SESSION['username'] = $data['username'];
            $_SESSION['role']     = $data['role'];
            $_SESSION['status']   = "login";

            // Load language preference
            $bahasa = $data['bahasa'] ?? 'id';
            $_SESSION['lang'] = $bahasa;
            $_SESSION['bahasa'] = $bahasa;

            // Set kelas if available (for students)
            if (!empty($data['kelas'])) {
                $_SESSION['kelas'] = $data['kelas'];
            }

            // If role is penjual (seller), get kantin info
            if ($data['role'] === 'penjual') {
                $id_user = $data['id_user'];
                $query_kantin = mysqli_query($koneksi, "SELECT id_kantin, nama_kantin FROM kantin WHERE id_penjual = '$id_user' LIMIT 1");
                
                if (!$query_kantin) {
                    echo "<script>alert('Error: Koneksi database gagal: " . mysqli_error($koneksi) . "'); window.location='login.php';</script>";
                    exit();
                }
                
                $data_kantin = mysqli_fetch_assoc($query_kantin);

                if ($data_kantin) {
                    $_SESSION['id_kantin'] = $data_kantin['id_kantin'];
                    $_SESSION['nama_kantin'] = $data_kantin['nama_kantin'];
                    $_SESSION['nama_penjual'] = $data['username'];
                } else {
                    echo "<script>alert('Akun penjual belum memiliki data kantin. Hubungi admin untuk mendaftarkan kantin Anda.'); window.location='login.php';</script>";
                    exit();
                }
            }

            // Redirect to loading.php
            header("Location: loading.php");
            exit();

        } else {
            echo "<script>alert('Password salah!'); window.location='login.php';</script>";
            exit();
        }
    } else {
        echo "<script>alert('Pengguna tidak ditemukan!'); window.location='login.php';</script>";
        exit();
    }
}
    }
}
?>