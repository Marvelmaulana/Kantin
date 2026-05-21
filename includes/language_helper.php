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
            'id' => 'Indonesia',
            'en' => 'English'
        ];
        return $names[$code] ?? $code;
    }
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
                return $mins <= 1 ? 'Baru saja' : $mins . ' menit';
            }
            $hours = $diff->h;
            return $hours == 1 ? '1 jam' : $hours . ' jam';
        } elseif ($diff->days == 1) {
            return 'Kemarin';
        } elseif ($diff->days < 7) {
            return $diff->days . ' hari';
        }

        return $then->format('d M');
    }
}
?>
