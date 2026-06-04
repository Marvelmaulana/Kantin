<?php
/**
 * Language Helper for Kantin Kita
 * Provides translation function t() and language utilities
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Get translated string for given key
 * @param string $key Translation key
 * @param string $default Default text if key not found
 * @return string Translated string
 */
if (!function_exists('t')) {
    function t($key, $default = null) {
        static $translations = null;
        static $current_lang = null;

        // Get current language from session/database
        $lang = get_current_language();

        // Reload translations if language changed
        if ($current_lang !== $lang || $translations === null) {
            $current_lang = $lang;
            $translations = [];

            // Load language file
            $lang_file = __DIR__ . '/../language/' . $lang . '.php';
            if (file_exists($lang_file)) {
                $translations = include $lang_file;
            }
        }

        // Get text
        $text = $translations[$key] ?? $default ?? $key;

       // Support placeholder replacement
if (func_num_args() > 1) {
    $args = array_slice(func_get_args(), 1);

    foreach ($args as $i => $val) {

        // Skip array here
        if (is_array($val)) {
            continue;
        }

        $text = str_replace(':' . ($i + 1), (string)$val, $text);
    }

    // Support named placeholders like :name
    if (is_array($args[0] ?? null)) {
        foreach ($args[0] as $k => $v) {
            $text = str_replace(':' . $k, (string)$v, $text);
        }
    }
}

        return $text;
    }
}

/**
 * Set user's language preference
 * @param string $lang Language code ('id' or 'en')
 */
if (!function_exists('set_language')) {
    function set_language($lang) {
        if (in_array($lang, ['id', 'en'], true)) {
            $_SESSION['lang'] = $lang;
            $_SESSION['bahasa'] = $lang;
        }
    }
}

// Automatically set language from query string if provided
if (isset($_GET['lang']) && in_array($_GET['lang'], ['id', 'en'], true)) {
    set_language($_GET['lang']);
}

/**
 * Get current language code
 * @return string Language code
 */
if (!function_exists('get_current_language')) {
    function get_current_language() {
        if (!empty($_SESSION['lang']) || !empty($_SESSION['bahasa'])) {
            return $_SESSION['lang'] ?? $_SESSION['bahasa'];
        }

        if (!empty($_SESSION['id_user'])) {
            global $koneksi;
            if (isset($koneksi) && $koneksi) {
                $id_user = (int)$_SESSION['id_user'];
                $q = @mysqli_query($koneksi, "SELECT bahasa FROM users WHERE id_user=$id_user LIMIT 1");
                $row = $q ? mysqli_fetch_assoc($q) : null;
                $lang = in_array($row['bahasa'] ?? '', ['id', 'en'], true) ? $row['bahasa'] : 'id';
                $_SESSION['lang'] = $lang;
                $_SESSION['bahasa'] = $lang;
                return $lang;
            }
        }

        return 'id';
    }
}

/**
 * Get human-readable language name
 * @param string $code Language code
 * @return string Language name
 */
if (!function_exists('get_language_name')) {
    function get_language_name($code) {
        $names = [
            'id' => get_current_language() === 'en' ? 'Indonesian' : 'Indonesia',
            'en' => 'English'
        ];
        return $names[$code] ?? $code;
    }
}

if (!function_exists('kk_translation_map_for_english')) {
    function kk_translation_map_for_english() {
        static $map = null;

        if ($map !== null) {
            return $map;
        }

        $id_file = __DIR__ . '/../language/id.php';
        $en_file = __DIR__ . '/../language/en.php';
        $id_translations = file_exists($id_file) ? include $id_file : [];
        $en_translations = file_exists($en_file) ? include $en_file : [];
        $map = [];

        foreach ($id_translations as $key => $id_text) {
            if (!isset($en_translations[$key]) || !is_string($id_text) || !is_string($en_translations[$key])) {
                continue;
            }

            if ($id_text !== '' && strpos($id_text, ':') === false && strpos($en_translations[$key], ':') === false) {
                $map[$id_text] = $en_translations[$key];
            }
        }

        $extra = [
            'Syarat & Ketentuan' => 'Terms & Conditions',
            'Kebijakan Privasi' => 'Privacy Policy',
            'Sekolah Digital' => 'Digital School',
            'Sistem Pemesanan Kantin Sekolah' => 'School Canteen Ordering System',
            'Dicetak otomatis melalui Sistem Admin Kantin Kita' => 'Automatically printed through the Kantin Kita Admin System',
            'Terima kasih atas transaksi Anda.' => 'Thank you for your transaction.',
            'Daftar Item Belanja' => 'Shopping Item List',
            'Nama Menu' => 'Menu Name',
            'Harga Satuan' => 'Unit Price',
            'Tidak ada rincian item belanja.' => 'No item details available.',
            'Pilihan:' => 'Option:',
            'Catatan:' => 'Note:',
            'Pembayaran Berhasil' => 'Payment Successful',
            'Terimakasih' => 'Thank you',
            'Pesananmu sedang diproses oleh penjual' => 'Your order is being processed by the seller',
            'Status Pesanan' => 'Order Status',
            'Pantau pesananmu di halaman Pesanan' => 'Track your order on the Orders page',
            'Menunggu Konfirmasi' => 'Waiting for Confirmation',
            'Penjual akan memproses pesananmu' => 'The seller will process your order',
            'Sedang Diproses' => 'Being Processed',
            'Pesananmu sedang dibuat' => 'Your order is being prepared',
            'Siap Diambil' => 'Ready for Pickup',
            'Pesanan siap diambil' => 'Your order is ready for pickup',
            'Lihat Pesanan' => 'View Orders',
            'Jelajahi Menu' => 'Explore Menu',
            'Menu Favoritmu' => 'Your Favorite Menu',
            'Belum ada favorit' => 'No favorites yet',
            'Kantin buka pukul' => 'Canteen opens at',
            'Stok Tidak Tersedia' => 'Stock Unavailable',
            'Stok Habis' => 'Out of Stock',
            'Menu ini sedang tidak tersedia. Coba menu lain ya!' => 'This menu is currently unavailable. Please try another menu.',
            'Kantin Sedang Tutup' => 'Canteen Currently Closed',
            'Stok Terbatas!' => 'Limited Stock!',
            'Menu ini sedang tersedia dan siap untuk dipesan' => 'This menu is available and ready to order',
            'Stok tinggal' => 'Stock remaining',
            'Belum ada ulasan' => 'No reviews yet',
            'Menu tidak ditemukan' => 'Menu not found',
            'Gagal mengirim pesan. Silakan coba lagi.' => 'Failed to send message. Please try again.',
            'Gagal memulai percakapan' => 'Failed to start conversation',
            'Gagal menyimpan' => 'Failed to save',
            'Gagal mereset stok' => 'Failed to reset stock',
            'Menyimpan...' => 'Saving...',
            'Mereset...' => 'Resetting...',
            'Simpan Pengaturan' => 'Save Settings',
            'Jam buka dan jam tutup harus diisi' => 'Opening and closing times are required',
            'Pengaturan jam operasional berhasil disimpan!' => 'Operating hours settings saved successfully!',
            'Reset stok semua menu hari ini? Stok akan menjadi 0 untuk semua menu.' => 'Reset stock for all menus today? Stock will become 0 for all menus.',
            'Stok berhasil direset untuk' => 'Stock successfully reset for',
            'Stok tidak mencukupi. Maksimal:' => 'Insufficient stock. Maximum:',
            'Maaf, menu ini sedang habis.' => 'Sorry, this menu is out of stock.',
            'Maaf, menu ini sedang habis' => 'Sorry, this menu is out of stock',
            'Maaf, kantin ini sedang tutup. Buka pukul' => 'Sorry, this canteen is closed. Opens at',
            'Pilih bintang dulu!' => 'Please select a star rating first!',
            'Terjadi error. Coba lagi.' => 'An error occurred. Please try again.',
            'Ditambahkan ke favorit!' => 'Added to favorites!',
            'Dihapus dari favorit' => 'Removed from favorites',
            'Maaf, kantin sedang tutup. Coba lagi saat jam operasional!' => 'Sorry, the canteen is closed. Try again during operating hours!',
            'Ada menu yang stoknya habis atau tidak mencukupi!' => 'Some menu stock is empty or insufficient!',
            'Item ini belum bisa dipilih.' => 'This item cannot be selected yet.',
            'Hapus item ini dari keranjang?' => 'Remove this item from cart?',
            'item dari keranjang?' => 'item(s) from cart?',
            'Pilih menu yang tidak habis ya!' => 'Please select menu items that are not out of stock!',
            'Batalkan pesanan ini?' => 'Cancel this order?',
            'Yakin ingin hapus kantin' => 'Are you sure you want to delete canteen',
            'Hapus menu ini?' => 'Delete this menu?',
            'Hapus mitra ini? Semua menu yang terdaftar di stand ini juga akan ikut terhapus!' => 'Delete this partner? All menus registered in this stand will also be deleted!',
            'Konfirmasi kenaikan kelas massal?' => 'Confirm mass class promotion?',
            'Hapus user ini?' => 'Delete this user?',
            'Username/Email dan Password tidak boleh kosong!' => 'Username/Email and Password cannot be empty!',
            'Username atau Email tidak boleh kosong!' => 'Username or Email cannot be empty!',
            'Password tidak boleh kosong!' => 'Password cannot be empty!',
            'Kesalahan database. Silakan coba lagi.' => 'Database error. Please try again.',
            'User tidak ditemukan atau bukan admin!' => 'User not found or not an admin!',
            'Password salah!' => 'Wrong password!',
            'Password salah! Periksa kembali password Anda.' => 'Wrong password! Please check your password again.',
            'Username atau email tidak terdaftar. Periksa kembali atau daftar akun baru.' => 'Username or email is not registered. Check again or register a new account.',
            'Data kantin tidak ditemukan. Hubungi admin.' => 'Canteen data not found. Contact admin.',
            'Error: Database query gagal' => 'Error: Database query failed',
            'Gagal membuat session. Silakan coba lagi.' => 'Failed to create session. Please try again.',
            'Silakan login terlebih dahulu' => 'Please log in first',
            'Maaf, stok tidak mencukupi.' => 'Sorry, insufficient stock.',
            'Berhasil ditambah ke keranjang!' => 'Successfully added to cart!',
            'Terjadi kesalahan. Silakan coba lagi.' => 'An error occurred. Please try again.',
            'Nama pengguna hanya boleh berisi huruf, angka, spasi, titik, dan garis bawah.' => 'Username may only contain letters, numbers, spaces, dots, and underscores.',
            'Jumlah melebihi stok. Sisa stok:' => 'Quantity exceeds stock. Remaining stock:',
            'Sesi keamanan tidak valid. Silakan muat ulang halaman.' => 'Invalid security session. Please reload the page.',
            'Sesi keamanan tidak valid. Muat ulang halaman.' => 'Invalid security session. Reload the page.',
            'Sesi berakhir, muat ulang halaman.' => 'Session expired, reload the page.',
            'Perbarui informasi pelanggan.' => 'Update customer information.',
            'Perbarui informasi dasar kantin. Kelola penjual di menu Manajemen Penjual.' => 'Update basic canteen information. Manage sellers in the Seller Management menu.',
            'Belum ada data kantin' => 'No canteen data yet',
            'Belum ada data pembeli' => 'No buyer data yet',
            'Belum ada data penjualan' => 'No sales data yet',
            'Belum ada transaksi' => 'No transactions yet',
            'Belum ada data pajak transaksi' => 'No transaction tax data yet',
            'Tidak ada data siswa' => 'No student data',
            'Tidak ada kantin yang tersedia' => 'No canteens available',
            'Belum ada penjual' => 'No seller yet',
            'Hapus semua akun siswa kelas 12' => 'Delete all grade 12 student accounts',
            'Hapus Semua Kelas 12' => 'Delete All Grade 12',
            'Daftar Siswa' => 'Student List',
            'Manajemen Siswa & Kelas' => 'Student & Class Management',
            'Informasi Profil' => 'Profile Information',
            'User Tidak Ditemukan' => 'User Not Found',
            'Tambah User Baru' => 'Add New User',
            'Kelas (jika Pembeli)' => 'Class (if Buyer)',
            'Daftar Penjual' => 'Seller List',
            'Penjual Aktif' => 'Active Sellers',
            'Informasi Penjual' => 'Seller Information',
            'Belum Ada Penjual' => 'No Sellers Yet',
            'Tambahkan penjual baru untuk mengisi stand dan menu.' => 'Add a new seller to fill stands and menus.',
            'Kelola Kantin' => 'Manage Canteens',
            'Kelola Penjual' => 'Manage Sellers',
            'Laporan Penjualan' => 'Sales Report',
            'Laporan Kantin' => 'Canteen Report',
            'Laporan Pembeli' => 'Buyer Report',
            'Profil Saya' => 'My Profile',
            'Tambah Kantin Baru' => 'Add New Canteen',
            'Atau Pilih Pemilik (opsional)' => 'Or Select Owner (optional)',
            'Pilih pemilik jika sudah ada akun penjual.' => 'Select an owner if the seller account already exists.',
            'Kantin akan secara otomatis buka/tutup sesuai jam operasional yang diatur.' => 'The canteen will automatically open/close according to the configured operating hours.',
            'Tambahkan Kantin' => 'Add Canteen',
            'Pilih Kantin' => 'Select Canteen',
            'Pilih kantin yang akan dikelola oleh penjual ini:' => 'Select the canteen this seller will manage:',
            'Simpan Penjual' => 'Save Seller',
            'Masuk Akun' => 'Sign In',
            'Masuk Admin' => 'Admin Login',
            'Gagal Masuk' => 'Login Failed',
            'Bantuan Login' => 'Login Help',
            'Masukkan email akun' => 'Enter account email',
            'Simpan Password' => 'Save Password',
            'Pendaftaran Akun' => 'Account Registration',
            'Pilih Kelas Anda' => 'Select Your Class',
            'Daftar Sekarang' => 'Register Now',
            'Akun penjual belum ditautkan ke kantin. Hubungi admin.' => 'Seller account is not linked to a canteen. Contact admin.',
            'Pilih kelas dengan benar!' => 'Select a valid class!',
            'Total Kantin' => 'Total Canteens',
            'Kantin Buka' => 'Open Canteens',
            'Semua Penjual' => 'All Sellers',
            'Pembeli Aktif' => 'Active Buyers',
            'Pembeli Baru Bulan Ini' => 'New Buyers This Month',
            'Total Pesanan' => 'Total Orders',
            'Pesanan Bulan Ini' => 'Orders This Month',
            'ID Pesanan' => 'Order ID',
            'Pesanan Terakhir' => 'Last Order',
            'Tanggal Pesanan' => 'Order Date',
            'Nama Pembeli' => 'Buyer Name',
            'Nama Kantin' => 'Canteen Name',
            'Nama Penjual' => 'Seller Name',
            'Detail Menu' => 'Menu Details',
            'Catatan Pembeli' => 'Buyer Note',
            'Pesanan selesai & batal' => 'Completed & cancelled orders',
            'Tambah Foto' => 'Add Photo',
            'Jam Operasional' => 'Operating Hours',
            'Dashboard Pembeli' => 'Buyer Dashboard',
            'Lihat semua kantin & menu' => 'View all canteens & menus',
            'Kantin Detail' => 'Canteen Detail',
            'Lihat badge BUKA/TUTUP' => 'View OPEN/CLOSED badges',
            'Detail Menu' => 'Menu Detail',
            'Daftar Kantin & Status' => 'Canteen List & Status',
            'Langkah-langkah Test' => 'Test Steps',
            'Buka Detail Kantin' => 'Open Canteen Detail',
            'Cek Detail Menu' => 'Check Menu Detail',
            'Kondisi:' => 'Condition:',
            'Mode Manual dengan Status' => 'Manual Mode with Status',
            'Penjual bisa set status secara manual' => 'Seller can set status manually',
            'tidak tergantung waktu' => 'not time-dependent',
            'Buka F12 Console untuk melihat debug info jika ada masalah' => 'Open F12 Console to view debug info if there is a problem',
            'Penuh' => 'Full',
            'Tersedia' => 'Available',
            'Buka' => 'Open',
            'Tutup' => 'Closed',
            'Makanan' => 'Food',
            'Minuman' => 'Drinks',
            'Camilan' => 'Snacks',
            'Diproses' => 'Processing',
            'Selesai' => 'Completed',
            'Dibatalkan' => 'Cancelled',
            'Batal' => 'Cancel',
            'Habis' => 'Out of Stock',
            'Pembeli' => 'Buyer',
            'Penjual' => 'Seller',
            'Pesanan' => 'Orders',
            'Tanggal' => 'Date',
            'Jumlah' => 'Quantity',
            'Harga' => 'Price',
            'Stok' => 'Stock',
            'Catatan' => 'Note',
            'Kembali' => 'Back',
            'Tambah' => 'Add',
            'Hapus' => 'Delete',
            'Simpan' => 'Save',
            'Cari' => 'Search',
            'Lihat' => 'View',
            'Semua' => 'All',
            'Belum Ada' => 'No',
            'Tidak Ada' => 'None',
        ];

        $map = array_merge($map, $extra);
        uksort($map, function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });

        return $map;
    }
}

if (!function_exists('kk_translate_inline_to_english')) {
    function kk_translate_inline_to_english($text) {
        if ($text === '') {
            return $text;
        }

        $brand_tokens = [];
        $text = preg_replace_callback('/Kantin Kita/i', function ($matches) use (&$brand_tokens) {
            $token = '%%KK_BRAND_' . count($brand_tokens) . '%%';
            $brand_tokens[$token] = $matches[0];
            return $token;
        }, $text);

        foreach (kk_translation_map_for_english() as $id => $en) {
            $text = str_replace($id, $en, $text);
        }

        return strtr($text, $brand_tokens);
    }
}

if (!function_exists('kk_translate_script_to_english')) {
    function kk_translate_script_to_english($script) {
        $script = preg_replace_callback('/\b(alert|confirm|showToast)\(\s*([\'"])(.*?)\2/is', function ($matches) {
            return $matches[1] . '(' . $matches[2] . kk_translate_inline_to_english($matches[3]) . $matches[2];
        }, $script);

        $script = preg_replace_callback('/\bshowAlert\(\s*([\'"])(.*?)\1\s*,\s*([\'"])(.*?)\3/is', function ($matches) {
            return 'showAlert(' . $matches[1] . $matches[2] . $matches[1] . ', ' . $matches[3] . kk_translate_inline_to_english($matches[4]) . $matches[3];
        }, $script);

        $script = preg_replace_callback('/\b(innerHTML|textContent)\s*=\s*([\'"])(.*?)\2/is', function ($matches) {
            return $matches[1] . ' = ' . $matches[2] . kk_translate_inline_to_english($matches[3]) . $matches[2];
        }, $script);

        return $script;
    }
}

if (!function_exists('kk_translate_html_to_english')) {
    function kk_translate_html_to_english($html) {
        if (get_current_language() !== 'en' || trim($html) === '') {
            return $html;
        }

        $html = preg_replace('/<html([^>]*)\slang=(["\'])id\2/i', '<html$1 lang="en"', $html);

        $protected = [];
        $html = preg_replace_callback('/<style\b[^>]*>.*?<\/style>/is', function ($matches) use (&$protected) {
            $token = '%%KK_BLOCK_' . count($protected) . '%%';
            $protected[$token] = $matches[0];
            return $token;
        }, $html);

        $html = preg_replace_callback('/<script\b[^>]*>.*?<\/script>/is', function ($matches) use (&$protected) {
            $token = '%%KK_BLOCK_' . count($protected) . '%%';
            $protected[$token] = kk_translate_script_to_english($matches[0]);
            return $token;
        }, $html);

        $html = preg_replace_callback('/\b(placeholder|title|alt|aria-label)=(["\'])(.*?)\2/is', function ($matches) {
            return $matches[1] . '=' . $matches[2] . htmlspecialchars(kk_translate_inline_to_english(html_entity_decode($matches[3], ENT_QUOTES | ENT_HTML5, 'UTF-8')), ENT_QUOTES, 'UTF-8') . $matches[2];
        }, $html);

        $parts = preg_split('/(<[^>]+>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $i => $part) {
            if ($part === '' || $part[0] === '<') {
                continue;
            }
            $parts[$i] = kk_translate_inline_to_english($part);
        }

        return strtr(implode('', $parts), $protected);
    }
}

if (!defined('KK_LANGUAGE_OUTPUT_BUFFER')) {
    define('KK_LANGUAGE_OUTPUT_BUFFER', true);
    ob_start('kk_translate_html_to_english');
}

/**
 * Format relative time for chat messages
 * @param string $datetime MySQL datetime string
 * @return string Relative time string
 */
if (!function_exists('format_chat_time')) {
    function format_chat_time($datetime) {
        if (!$datetime) return '';

        $now = new DateTime();
        $then = new DateTime($datetime);
        $diff = $now->diff($then);

        if ($diff->days == 0) {
            if ($diff->h == 0) {
                $mins = $diff->i;
                return $mins <= 1 ? t('time.just_now') : $mins . ' ' . t('time.minutes');
            }
            $hours = $diff->h;
            return $hours . ' ' . t('time.hours');
        } elseif ($diff->days == 1) {
            return t('time.yesterday');
        } elseif ($diff->days < 7) {
            return $diff->days . ' ' . t('time.days');
        }

        return $then->format('d M');
    }
}
?>
