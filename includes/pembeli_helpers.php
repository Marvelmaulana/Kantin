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
            ['users', 'kelas', "ALTER TABLE users ADD COLUMN kelas ENUM('10','11','12') NULL"],
            ['menu', 'opsi_pilihan', "ALTER TABLE menu ADD COLUMN opsi_pilihan TEXT NULL"],
            ['menu', 'foto_menu', "ALTER TABLE menu ADD COLUMN foto_menu VARCHAR(255) NULL"],
            ['keranjang', 'opsi_pilihan', "ALTER TABLE keranjang ADD COLUMN opsi_pilihan VARCHAR(120) NULL"],
            ['detail_pesanan', 'harga', "ALTER TABLE detail_pesanan ADD COLUMN harga DECIMAL(12,2) NOT NULL DEFAULT 0"],
            ['detail_pesanan', 'subtotal', "ALTER TABLE detail_pesanan ADD COLUMN subtotal DECIMAL(12,2) NOT NULL DEFAULT 0"],
            ['detail_pesanan', 'catatan', "ALTER TABLE detail_pesanan ADD COLUMN catatan TEXT NULL"],
            ['detail_pesanan', 'nama_menu', "ALTER TABLE detail_pesanan ADD COLUMN nama_menu VARCHAR(150) NULL"],
            ['detail_pesanan', 'nama_kantin', "ALTER TABLE detail_pesanan ADD COLUMN nama_kantin VARCHAR(150) NULL"],
            ['detail_pesanan', 'opsi_pilihan', "ALTER TABLE detail_pesanan ADD COLUMN opsi_pilihan VARCHAR(120) NULL"],
            ['pesanan', 'pajak', "ALTER TABLE pesanan ADD COLUMN pajak INT NOT NULL DEFAULT 0"],
            ['pesanan', 'bukti_pembayaran', "ALTER TABLE pesanan ADD COLUMN bukti_pembayaran VARCHAR(255) NULL"],
            ['pesanan', 'created_at', "ALTER TABLE pesanan ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP"],
            ['kantin', 'pasword_kantin', "ALTER TABLE kantin ADD COLUMN pasword_kantin VARCHAR(100) NULL"],
            ['kantin', 'jam_buka', "ALTER TABLE kantin ADD COLUMN jam_buka TIME NULL DEFAULT '07:00:00'"],
            ['kantin', 'jam_tutup', "ALTER TABLE kantin ADD COLUMN jam_tutup TIME NULL DEFAULT '15:00:00'"],
            ['kantin', 'status_buka', "ALTER TABLE kantin ADD COLUMN status_buka ENUM('Buka','Tutup') NULL DEFAULT 'Buka'"],
            ['kantin', 'tipe_operasi', "ALTER TABLE kantin ADD COLUMN tipe_operasi ENUM('manual','otomatis') NOT NULL DEFAULT 'otomatis'"],
            ['kantin', 'total_ulasan', "ALTER TABLE kantin ADD COLUMN total_ulasan INT NOT NULL DEFAULT 0"],
            ['kantin', 'total_rating', "ALTER TABLE kantin ADD COLUMN total_rating INT NOT NULL DEFAULT 0"],
            ['kantin', 'created_at', "ALTER TABLE kantin ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP"],
            ['chat_conversations', 'id_order', "ALTER TABLE chat_conversations ADD COLUMN id_order INT NULL"],
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
        @mysqli_query($koneksi, "UPDATE kantin SET tipe_operasi = 'otomatis' WHERE tipe_operasi IS NULL");
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
        END
        WHERE tipe_operasi = 'otomatis'";

        @mysqli_query($koneksi, $query);
    }
}

if (!function_exists('kk_is_kantin_open')) {
    function kk_is_kantin_open($kantin, $now = null) {
        $tipe_operasi = $kantin['tipe_operasi'] ?? 'otomatis';
        if ($tipe_operasi === 'otomatis' && false) { // disable manual check
            return ($kantin['status_buka'] ?? 'Buka') === 'Buka';
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
        return 500;
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

// Fungsi untuk membuat entry transaksi (pajak) ketika pesanan diselesaikan
if (!function_exists('kk_create_transaction')) {
    function kk_create_transaction($koneksi, $id_pesanan, $jumlah_pajak = 1000, $metode_pembayaran = 'Cash') {
        if (!$id_pesanan) return false;
        
        // Cek apakah transaksi sudah ada untuk pesanan ini
        $cek = mysqli_query($koneksi, "SELECT id_transaksi FROM transaksi WHERE id_pesanan = $id_pesanan LIMIT 1");
        if ($cek && mysqli_num_rows($cek) > 0) {
            return true; // Transaksi sudah ada
        }
        
        $jumlah_pajak = max(0, (float)$jumlah_pajak);
        $metode = mysqli_real_escape_string($koneksi, $metode_pembayaran ?: 'Cash');
        
        // Ambil data pesanan
        $q = mysqli_query($koneksi, "SELECT id_user, id_kantin, total_harga FROM pesanan WHERE id_pesanan = $id_pesanan");
        if (!$q || mysqli_num_rows($q) === 0) {
            return false;
        }
        
        $pesanan = mysqli_fetch_assoc($q);
        
        // Buat entry transaksi
        $result = mysqli_query($koneksi, "
            INSERT INTO transaksi (id_user, id_kantin, id_pesanan, total_harga, jumlah_pajak, metode_pembayaran, tanggal, status)
            VALUES ({$pesanan['id_user']}, {$pesanan['id_kantin']}, $id_pesanan, {$pesanan['total_harga']}, $jumlah_pajak, '$metode', NOW(), 'Berhasil')
        ");
        
        return $result ? true : false;
    }
}

// Fungsi untuk mendapatkan pajak checkout
if (!function_exists('kk_checkout_tax')) {
    function kk_checkout_tax() {
        return 1000; // Pajak tetap 1000 per transaksi
    }
}

// ========================================
// FUNGSI BARU: STATUS MENU & KANTIN
// ========================================

/**
 * Mendapatkan status menu dengan logika lengkap
 * Status bisa: 'tersedia', 'habis', 'tutup'
 */
if (!function_exists('kk_get_menu_status')) {
    function kk_get_menu_status($menu, $kantin = null) {
        if (!is_array($menu)) {
            return 'tutup';
        }

        $stok = (int)($menu['stok'] ?? 0);
        $menuStatus = trim($menu['status'] ?? 'Tersedia');

        // Jika stok habis, return 'habis'
        if ($stok <= 0) {
            return 'habis';
        }

        // Jika kantin ditutup, return 'tutup'
        if ($kantin !== null && !kk_is_kantin_open($kantin)) {
            return 'tutup';
        }

        // Jika menu status Habis di database, return 'habis'
        if ($menuStatus === 'Habis' || strtolower($menuStatus) === 'habis') {
            return 'habis';
        }

        // Selain itu tersedia
        return 'tersedia';
    }
}

/**
 * Mendapatkan label status menu dalam bahasa Indonesia
 */
if (!function_exists('kk_get_menu_status_label')) {
    function kk_get_menu_status_label($status) {
        $labels = [
            'tersedia' => 'Tersedia',
            'habis'    => 'Stok Habis',
            'tutup'    => 'Kantin Tutup'
        ];
        $status = strtolower(trim($status));
        return $labels[$status] ?? 'Tidak Diketahui';
    }
}

/**
 * Cek apakah menu bisa dibeli
 */
if (!function_exists('kk_can_buy_menu')) {
    function kk_can_buy_menu($menu, $kantin = null) {
        $status = kk_get_menu_status($menu, $kantin);
        return $status === 'tersedia';
    }
}

/**
 * Mendapatkan warna badge untuk status menu
 */
if (!function_exists('kk_get_status_badge_class')) {
    function kk_get_status_badge_class($status) {
        $status = strtolower(trim($status));
        switch ($status) {
            case 'tersedia':
                return 'badge bg-success'; // Hijau
            case 'habis':
                return 'badge bg-warning text-dark'; // Kuning
            case 'tutup':
                return 'badge bg-danger'; // Merah
            default:
                return 'badge bg-secondary';
        }
    }
}

/**
 * Mendapatkan HTML badge untuk status menu
 */
if (!function_exists('kk_get_status_badge')) {
    function kk_get_status_badge($status) {
        $label = kk_get_menu_status_label($status);
        $class = kk_get_status_badge_class($status);
        return '<span class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '">'
             . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
             . '</span>';
    }
}

/**
 * Mendapatkan status kantin dengan label
 */
if (!function_exists('kk_get_kantin_status_label')) {
    function kk_get_kantin_status_label($kantin) {
        $isOpen = kk_is_kantin_open($kantin);
        if ($isOpen) {
            $jam = kk_kantin_hours_label($kantin);
            return "Buka ({$jam})";
        }
        $jam = kk_kantin_hours_label($kantin);
        return "Tutup sampai {$jam}";
    }
}

/**
 * Mendapatkan badge status kantin
 */
if (!function_exists('kk_get_kantin_badge')) {
    function kk_get_kantin_badge($kantin) {
        $isOpen = kk_is_kantin_open($kantin);
        $class = $isOpen ? 'badge bg-success' : 'badge bg-danger';
        $label = $isOpen ? 'BUKA' : 'TUTUP';
        return '<span class="' . $class . '">' . $label . '</span>';
    }
}

/**
 * Format jam ke HH:MM dengan validasi
 */
if (!function_exists('kk_format_jam')) {
    function kk_format_jam($jam) {
        $jam = trim((string)$jam);
        if (empty($jam)) return '00:00';
        
        if (preg_match('/^\d{1,2}:\d{2}/', $jam)) {
            return substr($jam, 0, 5);
        }
        
        $ts = strtotime($jam);
        if ($ts) {
            return date('H:i', $ts);
        }
        
        return '00:00';
    }
}

/**
 * Validasi format jam HH:MM
 */
if (!function_exists('kk_validate_jam')) {
    function kk_validate_jam($jam) {
        return preg_match('/^\d{2}:\d{2}$/', trim((string)$jam)) === 1;
    }
}

/**
 * Mendapatkan status badge array kantin
 */
if (!function_exists('kk_kantin_status_badge')) {
    function kk_kantin_status_badge($kantin) {
        $is_open = kk_is_kantin_open($kantin);
        return [
            'is_open' => $is_open,
            'status' => $is_open ? 'Buka' : 'Tutup',
            'icon' => $is_open ? 'storefront' : 'block'
        ];
    }
}

/**
 * Validate and format time to HH:MM:SS
 */
if (!function_exists('kk_validate_time_format')) {
    function kk_validate_time_format($time) {
        $time = trim((string)$time);
        if (preg_match('/^([0-1][0-9]|2[0-3]):([0-5][0-9])(:[0-5][0-9])?$/', $time, $matches)) {
            $hours = $matches[1];
            $minutes = $matches[2];
            $seconds = isset($matches[3]) && $matches[3] !== '' ? str_replace(':', '', $matches[3]) : '00';
            return "$hours:$minutes:$seconds";
        }
        return false;
    }
}
?>
