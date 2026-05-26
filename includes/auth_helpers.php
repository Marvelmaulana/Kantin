<?php
/**
 * Authentication Helpers
 * Validasi dan helper functions untuk sistem authentikasi
 */

if (!function_exists('validate_username')) {
    /**
     * Validasi username
     * - Tidak boleh mengandung spasi
     * - Hanya huruf, angka, underscore
     * - Minimal 3 karakter
     * 
     * @param string $username
     * @return array ['valid' => bool, 'error' => string|null]
     */
    function validate_username($username) {
        $username = trim($username);

        if (empty($username)) {
            return ['valid' => false, 'error' => 'Username tidak boleh kosong'];
        }

        // Cek spasi
        if (strpos($username, ' ') !== false) {
            return ['valid' => false, 'error' => 'Username tidak boleh mengandung spasi'];
        }

        // Cek karakter yang diizinkan (hanya huruf, angka, underscore)
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return ['valid' => false, 'error' => 'Username hanya boleh berisi huruf, angka, dan underscore (_)'];
        }

        // Cek panjang minimal
        if (strlen($username) < 3) {
            return ['valid' => false, 'error' => 'Username minimal 3 karakter'];
        }

        // Cek panjang maksimal
        if (strlen($username) > 50) {
            return ['valid' => false, 'error' => 'Username maksimal 50 karakter'];
        }

        return ['valid' => true, 'error' => null];
    }
}

if (!function_exists('validate_email')) {
    /**
     * Validasi email
     * - Harus format "@gmail.com"
     * - Valid email format
     * 
     * @param string $email
     * @return array ['valid' => bool, 'error' => string|null]
     */
    function validate_email($email) {
        $email = trim(strtolower($email));

        if (empty($email)) {
            return ['valid' => false, 'error' => 'Email tidak boleh kosong'];
        }

        // Cek format @gmail.com
        if (!preg_match('/^[a-zA-Z0-9._-]+@gmail\.com$/', $email)) {
            return ['valid' => false, 'error' => 'Email harus menggunakan format @gmail.com'];
        }

        // Cek format email valid
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'error' => 'Format email tidak valid'];
        }

        return ['valid' => true, 'error' => null];
    }
}

if (!function_exists('validate_password')) {
    /**
     * Validasi password
     * - Minimal 8 karakter
     * - Tidak boleh kosong
     * 
     * @param string $password
     * @return array ['valid' => bool, 'error' => string|null]
     */
    function validate_password($password) {
        if (empty($password)) {
            return ['valid' => false, 'error' => 'Password tidak boleh kosong'];
        }

        if (strlen($password) < 8) {
            return ['valid' => false, 'error' => 'Password minimal 8 karakter'];
        }

        if (strlen($password) > 255) {
            return ['valid' => false, 'error' => 'Password terlalu panjang'];
        }

        return ['valid' => true, 'error' => null];
    }
}

if (!function_exists('validate_password_match')) {
    /**
     * Validasi kecocokan password
     * 
     * @param string $password
     * @param string $confirm_password
     * @return array ['valid' => bool, 'error' => string|null]
     */
    function validate_password_match($password, $confirm_password) {
        if ($password !== $confirm_password) {
            return ['valid' => false, 'error' => 'Password tidak cocok'];
        }

        return ['valid' => true, 'error' => null];
    }
}

if (!function_exists('hash_password')) {
    /**
     * Hash password dengan bcrypt
     * 
     * @param string $password
     * @return string Hashed password
     */
    function hash_password($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}

if (!function_exists('verify_password')) {
    /**
     * Verifikasi password dengan hash
     * 
     * @param string $password
     * @param string $hash
     * @return bool
     */
    function verify_password($password, $hash) {
        return password_verify($password, $hash);
    }
}

if (!function_exists('user_exists')) {
    /**
     * Cek apakah username atau email sudah terdaftar
     * 
     * @param mysqli $koneksi
     * @param string $username
     * @param string $email
     * @return array ['exists' => bool, 'field' => string|null]
     */
    function user_exists($koneksi, $username = null, $email = null) {
        if (empty($username) && empty($email)) {
            return ['exists' => false, 'field' => null];
        }

        if (!empty($username)) {
            $username = mysqli_real_escape_string($koneksi, trim($username));
            $query = mysqli_query($koneksi, "SELECT id_user FROM users WHERE username = '$username' LIMIT 1");
            if ($query && mysqli_num_rows($query) > 0) {
                return ['exists' => true, 'field' => 'username'];
            }
        }

        if (!empty($email)) {
            $email = mysqli_real_escape_string($koneksi, trim(strtolower($email)));
            $query = mysqli_query($koneksi, "SELECT id_user FROM users WHERE LOWER(email) = '$email' LIMIT 1");
            if ($query && mysqli_num_rows($query) > 0) {
                return ['exists' => true, 'field' => 'email'];
            }
        }

        return ['exists' => false, 'field' => null];
    }
}

if (!function_exists('get_user_by_username_or_email')) {
    /**
     * Ambil user data berdasarkan username atau email
     * 
     * @param mysqli $koneksi
     * @param string $user_input (username atau email)
     * @return array|null User data atau null jika tidak ditemukan
     */
    function get_user_by_username_or_email($koneksi, $user_input) {
        $user_input = trim($user_input);

        if (empty($user_input)) {
            return null;
        }

        $user_input = mysqli_real_escape_string($koneksi, $user_input);

        // Cari berdasarkan username atau email
        $query = mysqli_query($koneksi, "
            SELECT * FROM users 
            WHERE username = '$user_input' OR LOWER(email) = LOWER('$user_input') 
            LIMIT 1
        ");

        if ($query && mysqli_num_rows($query) > 0) {
            return mysqli_fetch_assoc($query);
        }

        return null;
    }
}

if (!function_exists('create_user_session')) {
    /**
     * Buat session user setelah login berhasil
     * 
     * @param array $user_data Data user dari database
     */
    function create_user_session($user_data) {
        if (!isset($user_data['id_user'])) {
            return false;
        }

        $_SESSION['id_user'] = $user_data['id_user'];
        $_SESSION['username'] = $user_data['username'];
        $_SESSION['email'] = $user_data['email'];
        $_SESSION['role'] = $user_data['role'];
        $_SESSION['tipe_pengguna'] = $user_data['tipe_pengguna'] ?? null;
        $_SESSION['status'] = 'login';
        $_SESSION['lang'] = $user_data['bahasa'] ?? 'id';
        $_SESSION['bahasa'] = $user_data['bahasa'] ?? 'id';

        // Set kelas jika ada (untuk siswa)
        if (!empty($user_data['kelas'])) {
            $_SESSION['kelas'] = $user_data['kelas'];
        }

        return true;
    }
}

if (!function_exists('get_redirect_url_by_role')) {
    /**
     * Dapatkan URL redirect berdasarkan role user
     * 
     * @param string $role
     * @return string URL redirect
     */
    function get_redirect_url_by_role($role) {
        switch ($role) {
            case 'penjual':
                return '/kantin/app/penjual/dashboard_penjual.php';
            case 'admin':
                return '/kantin/app/admin/dashboard_admin.php';
            case 'pembeli':
            default:
                return '/kantin/app/pembeli/dashboard.php';
        }
    }
}
?>
