<?php
if (!function_exists('kk_column_exists')) {
    function kk_column_exists($koneksi, $table, $column) {
        $table = mysqli_real_escape_string($koneksi, $table);
        $column = mysqli_real_escape_string($koneksi, $column);
        $q = mysqli_query($koneksi, "SHOW COLUMNS FROM `$table` LIKE '$column'");
        return $q && mysqli_num_rows($q) > 0;
    }
}

if (!function_exists('kk_ensure_buyer_schema')) {
    function kk_ensure_buyer_schema($koneksi) {
        static $done = false;
        if ($done) return;
        $done = true;

        $alters = [
            ['users', 'foto_profil', "ALTER TABLE users ADD COLUMN foto_profil VARCHAR(255) NULL"],
            ['users', 'bahasa', "ALTER TABLE users ADD COLUMN bahasa VARCHAR(10) NOT NULL DEFAULT 'id'"],
            ['users', 'reset_token', "ALTER TABLE users ADD COLUMN reset_token VARCHAR(100) NULL"],
            ['users', 'reset_expired', "ALTER TABLE users ADD COLUMN reset_expired DATETIME NULL"],
            ['menu', 'opsi_pilihan', "ALTER TABLE menu ADD COLUMN opsi_pilihan TEXT NULL"],
            ['keranjang', 'opsi_pilihan', "ALTER TABLE keranjang ADD COLUMN opsi_pilihan VARCHAR(120) NULL"],
            ['detail_pesanan', 'harga', "ALTER TABLE detail_pesanan ADD COLUMN harga DECIMAL(12,2) NOT NULL DEFAULT 0"],
            ['detail_pesanan', 'subtotal', "ALTER TABLE detail_pesanan ADD COLUMN subtotal DECIMAL(12,2) NOT NULL DEFAULT 0"],
            ['detail_pesanan', 'catatan', "ALTER TABLE detail_pesanan ADD COLUMN catatan TEXT NULL"],
            ['detail_pesanan', 'nama_menu', "ALTER TABLE detail_pesanan ADD COLUMN nama_menu VARCHAR(150) NULL"],
            ['detail_pesanan', 'opsi_pilihan', "ALTER TABLE detail_pesanan ADD COLUMN opsi_pilihan VARCHAR(120) NULL"],
            ['pesanan', 'pajak', "ALTER TABLE pesanan ADD COLUMN pajak INT NOT NULL DEFAULT 0"],
            ['kantin', 'pasword_kantin', "ALTER TABLE kantin ADD COLUMN pasword_kantin VARCHAR(100) NULL"],
            ['kantin', 'jam_buka', "ALTER TABLE kantin ADD COLUMN jam_buka TIME NULL DEFAULT '07:00:00'"],
            ['kantin', 'jam_tutup', "ALTER TABLE kantin ADD COLUMN jam_tutup TIME NULL DEFAULT '15:00:00'"],
            ['kantin', 'status_buka', "ALTER TABLE kantin ADD COLUMN status_buka ENUM('Buka','Tutup') NULL DEFAULT 'Buka'"],
            ['kantin', 'tipe_operasi', "ALTER TABLE kantin ADD COLUMN tipe_operasi ENUM('manual','otomatis') NOT NULL DEFAULT 'manual'"],
        ];

        foreach ($alters as [$table, $column, $sql]) {
            if (!kk_column_exists($koneksi, $table, $column)) {
                @mysqli_query($koneksi, $sql);
            }
        }

        @mysqli_query($koneksi, "
            CREATE TABLE IF NOT EXISTS password_resets (
                id_reset INT AUTO_INCREMENT PRIMARY KEY,
                id_user INT NOT NULL,
                email VARCHAR(150) NOT NULL,
                token VARCHAR(100) NOT NULL,
                expired_at DATETIME NOT NULL,
                used_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX (token),
                INDEX (id_user)
            )
        ");

        // Chat tables
        @mysqli_query($koneksi, "
            CREATE TABLE IF NOT EXISTS chat_conversations (
                id_conversation INT AUTO_INCREMENT PRIMARY KEY,
                id_seller INT NOT NULL,
                id_buyer INT NOT NULL,
                id_kantin INT NOT NULL,
                id_order INT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                last_message TEXT NULL,
                last_message_at DATETIME NULL,
                UNIQUE KEY unique_convo (id_seller, id_buyer, id_kantin)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        @mysqli_query($koneksi, "
            CREATE TABLE IF NOT EXISTS chat_messages (
                id_message INT AUTO_INCREMENT PRIMARY KEY,
                id_conversation INT NOT NULL,
                id_sender INT NOT NULL,
                message TEXT NOT NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                read_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_conversation (id_conversation),
                INDEX idx_sender (id_sender),
                INDEX idx_created (created_at),
                FOREIGN KEY (id_conversation) REFERENCES chat_conversations(id_conversation) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        @mysqli_query($koneksi, "UPDATE menu SET stok = 0 WHERE stok IS NULL");
        @mysqli_query($koneksi, "UPDATE menu SET status = 'Habis' WHERE COALESCE(stok,0) <= 0");
        @mysqli_query($koneksi, "UPDATE menu SET status = 'Tersedia' WHERE COALESCE(stok,0) > 0 AND COALESCE(status,'') <> 'Tersedia'");
        @mysqli_query($koneksi, "UPDATE kantin SET jam_buka = '07:00:00' WHERE jam_buka IS NULL");
        @mysqli_query($koneksi, "UPDATE kantin SET jam_tutup = '15:00:00' WHERE jam_tutup IS NULL");
        @mysqli_query($koneksi, "UPDATE kantin SET status_buka = 'Buka' WHERE status_buka IS NULL");
    }
}

if (!function_exists('kk_csrf_token')) {
    function kk_csrf_token() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('kk_csrf_field')) {
    function kk_csrf_field() {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(kk_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('kk_verify_csrf')) {
    function kk_verify_csrf($token = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $token = $token ?? ($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '');
        return is_string($token)
            && isset($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('kk_abort_csrf')) {
    function kk_abort_csrf() {
        http_response_code(403);
        exit('Sesi keamanan tidak valid. Silakan muat ulang halaman.');
    }
}

if (!function_exists('kk_time_hm')) {
    function kk_time_hm($value, $fallback) {
        $value = trim((string)$value);
        if ($value === '') $value = $fallback;
        $ts = strtotime($value);
        return $ts ? date('H:i', $ts) : $fallback;
    }
}

if (!function_exists('kk_refresh_kantin_status')) {
    function kk_refresh_kantin_status($koneksi) {
        date_default_timezone_set('Asia/Jakarta');
        $now = date('H:i:s');
        $nowEsc = mysqli_real_escape_string($koneksi, $now);

        $query = "UPDATE kantin SET status_buka = CASE
            WHEN COALESCE(NULLIF(jam_buka,''),'07:00:00') = COALESCE(NULLIF(jam_tutup,''),'15:00:00') THEN 'Buka'
            WHEN COALESCE(NULLIF(jam_buka,''),'07:00:00') < COALESCE(NULLIF(jam_tutup,''),'15:00:00')
                 AND '$nowEsc' BETWEEN COALESCE(NULLIF(jam_buka,''),'07:00:00') AND COALESCE(NULLIF(jam_tutup,''),'15:00:00') THEN 'Buka'
            WHEN COALESCE(NULLIF(jam_buka,''),'07:00:00') > COALESCE(NULLIF(jam_tutup,''),'15:00:00')
                 AND ('$nowEsc' >= COALESCE(NULLIF(jam_buka,''),'07:00:00') OR '$nowEsc' <= COALESCE(NULLIF(jam_tutup,''),'15:00:00')) THEN 'Buka'
            ELSE 'Tutup'
        END";

        @mysqli_query($koneksi, $query);
    }
}

if (!function_exists('kk_is_kantin_open')) {
    function kk_is_kantin_open($kantin, $now = null) {
        $status = $kantin['status_buka'] ?? 'Buka';
        if ($status !== 'Buka') {
            return false;
        }

        $now = $now ?: date('H:i');
        $open = kk_time_hm($kantin['jam_buka'] ?? '', '07:00');
        $close = kk_time_hm($kantin['jam_tutup'] ?? '', '15:00');

        if ($open === $close) {
            return true;
        }

        if ($open < $close) {
            return $now >= $open && $now <= $close;
        }

        return $now >= $open || $now <= $close;
    }
}

if (!function_exists('kk_kantin_hours_label')) {
    function kk_kantin_hours_label($kantin) {
        return kk_time_hm($kantin['jam_buka'] ?? '', '07:00') . ' - ' . kk_time_hm($kantin['jam_tutup'] ?? '', '15:00');
    }
}

if (!function_exists('kk_is_menu_available')) {
    function kk_is_menu_available($menu) {
        return ($menu['status'] ?? 'Tersedia') === 'Tersedia' && (int)($menu['stok'] ?? 0) > 0;
    }
}

if (!function_exists('kk_checkout_tax')) {
    function kk_checkout_tax() {
        return 1000;
    }
}

if (!function_exists('kk_upload_image')) {
    function kk_upload_image($field, $targetDir) {
        if (empty($_FILES[$field]['name']) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return '';
        }
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0777, true);
        }
        $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            return '';
        }
        $name = 'profile_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
        $dest = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;
        if (move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) {
            return $name;
        }
        return '';
    }
}

if (!function_exists('kk_format_rupiah')) {
    function kk_format_rupiah($number) {
        return 'Rp ' . number_format((float)$number, 0, ',', '.');
    }
}

if (!function_exists('kk_upload_url')) {
    function kk_upload_url($filename, $kind = 'menu') {
        $filename = trim((string)$filename);
        $fallbacks = [
            'menu' => '../../public/assets/img/default-food.svg',
            'logo' => '../../public/assets/img/default-logo.svg',
            'banner' => '../../public/assets/img/default-banner.svg',
            'profile' => '../../public/assets/img/default-logo.svg',
        ];
        if ($filename === '') {
            return $fallbacks[$kind] ?? $fallbacks['menu'];
        }

        $clean = ltrim(str_replace('\\', '/', $filename), '/');
        $clean = preg_replace('#^(\.\./)+#', '', $clean);
        $candidates = [];

        if (str_starts_with($clean, 'uploads/')) {
            $candidates[] = $clean;
        } else {
            if ($kind === 'logo') $candidates[] = 'uploads/logo/' . $clean;
            if ($kind === 'banner') $candidates[] = 'uploads/banner/' . $clean;
            if ($kind === 'profile') $candidates[] = 'uploads/profil/' . $clean;
            $candidates[] = 'uploads/' . $clean;
            $candidates[] = 'uploads/logo/' . $clean;
            $candidates[] = 'uploads/banner/' . $clean;
        }

        foreach (array_unique($candidates) as $path) {
            $absolute = BASE_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
            if (is_file($absolute)) {
                return '../../' . $path;
            }
        }

        return $fallbacks[$kind] ?? $fallbacks['menu'];
    }
}
?>
