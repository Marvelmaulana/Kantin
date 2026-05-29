<?php
/**
 * Admin Helpers
 * Helper functions untuk admin panel
 */

if (!function_exists('admin_session_check')) {
    /**
     * Check if user has valid admin session
     * Redirect to login if not
     * 
     * @return bool|array false if no session, array with user data if valid
     */
    function admin_session_check() {
        // Check if session has started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check id_user
        if (!isset($_SESSION['id_user'])) {
            header("Location: " . dirname(__DIR__) . "/app/admin/login.php");
            exit();
        }

        // Check role is admin
        if (($_SESSION['role'] ?? '') !== 'admin') {
            header("Location: " . dirname(__DIR__) . "/app/admin/login.php");
            exit();
        }

        // Check session timeout (1 hour default)
        $session_timeout = isset($_SESSION['session_timeout']) ? $_SESSION['session_timeout'] : 3600;
        
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $session_timeout) {
            session_destroy();
            header("Location: " . dirname(__DIR__) . "/app/admin/login.php?reason=timeout");
            exit();
        }

        // Update last activity
        $_SESSION['login_time'] = time();

        return [
            'id_user' => $_SESSION['id_user'],
            'username' => $_SESSION['username'] ?? '',
            'role' => $_SESSION['role'],
            'lang' => $_SESSION['lang'] ?? 'id'
        ];
    }
}

if (!function_exists('get_admin_data')) {
    /**
     * Get admin user data from database
     * 
     * @param mysqli $koneksi
     * @param int $id_user
     * @return array|null
     */
    function get_admin_data($koneksi, $id_user) {
        $query = "SELECT * FROM users WHERE id_user = ? AND role = 'admin' LIMIT 1";
        $stmt = mysqli_prepare($koneksi, $query);
        
        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param($stmt, "i", $id_user);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        return $data;
    }
}

if (!function_exists('admin_logout')) {
    /**
     * Safely logout admin
     */
    function admin_logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
        header("Location: login.php");
        exit();
    }
}

if (!function_exists('sanitize_admin_input')) {
    /**
     * Sanitize input for admin forms
     * 
     * @param string $input
     * @return string
     */
    function sanitize_admin_input($input) {
        $input = trim($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return $input;
    }
}

if (!function_exists('validate_admin_permission')) {
    /**
     * Validate if current admin can perform action
     * Can be extended for role-based permissions
     * 
     * @param string $action
     * @return bool
     */
    function validate_admin_permission($action = 'default') {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            return false;
        }

        // TODO: Implement role-based permissions if needed
        return true;
    }
}
