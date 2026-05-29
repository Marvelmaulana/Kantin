<?php
/**
 * Admin CRUD Security Functions
 * Centralized functions untuk safe database operations dengan prepared statements
 * 
 * @package Kantin Admin
 * @version 2.0
 */

// ==================== SECURITY UTILITIES ====================

/**
 * Initialize CSRF Token
 * Generate dan store CSRF token di session
 */
function admin_init_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 * Check if submitted token matches session token
 */
function admin_verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && 
           hash_equals($_SESSION['csrf_token'], $token ?? '');
}

/**
 * Get CSRF Token for Output
 * Return token for form hidden input
 */
function admin_csrf_token_field() {
    $token = admin_init_csrf_token();
    return "<input type=\"hidden\" name=\"csrf_token\" value=\"" . htmlspecialchars($token) . "\">";
}

// ==================== INPUT VALIDATION ====================

/**
 * Validate and Sanitize String Input
 * Remove special chars, trim whitespace, optional length check
 */
function admin_validate_string($value, $min = 1, $max = null, $allow_special = false) {
    if (!is_string($value)) {
        return null;
    }
    
    $value = trim($value);
    
    if (strlen($value) < $min) {
        return null;
    }
    
    if ($max && strlen($value) > $max) {
        return null;
    }
    
    if (!$allow_special) {
        // Remove dangerous characters but allow spaces, hyphens, dots
        $value = preg_replace('/[^\w\s\-\.\،]/u', '', $value);
    }
    
    return $value;
}

/**
 * Validate Email
 * Using PHP filter_var dengan sanitization
 */
function admin_validate_email($email) {
    $email = trim(strtolower($email));
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $email;
    }
    return null;
}

/**
 * Validate Numeric Value
 */
function admin_validate_numeric($value, $type = 'float', $min = null, $max = null) {
    if ($type === 'int') {
        $value = filter_var($value, FILTER_VALIDATE_INT);
    } else {
        $value = filter_var($value, FILTER_VALIDATE_FLOAT);
    }
    
    if ($value === false) {
        return null;
    }
    
    if ($min !== null && $value < $min) {
        return null;
    }
    
    if ($max !== null && $value > $max) {
        return null;
    }
    
    return $value;
}

/**
 * Validate Time Format
 */
function admin_validate_time($time) {
    if (preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $time)) {
        return $time;
    }
    return null;
}

/**
 * Validate Enum Value
 */
function admin_validate_enum($value, $allowed_values) {
    if (in_array($value, $allowed_values, true)) {
        return $value;
    }
    return null;
}

/**
 * Validate Database ID
 */
function admin_validate_id($id) {
    $id = filter_var($id, FILTER_VALIDATE_INT);
    return ($id && $id > 0) ? $id : null;
}

// ==================== PREPARED STATEMENTS ====================

/**
 * Execute Prepared Statement - SELECT Multiple Rows
 */
function admin_query_select($koneksi, $sql, $params = [], $types = '') {
    $stmt = mysqli_prepare($koneksi, $sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . mysqli_error($koneksi));
    }
    
    if (!empty($params)) {
        if (empty($types)) {
            // Auto-detect types
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
        }
        
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Execute failed: ' . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}

/**
 * Execute Prepared Statement - SELECT Single Row
 */
function admin_query_fetch_one($koneksi, $sql, $params = [], $types = '') {
    $result = admin_query_select($koneksi, $sql, $params, $types);
    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    return $row ?: null;
}

/**
 * Execute Prepared Statement - SELECT All Rows
 */
function admin_query_fetch_all($koneksi, $sql, $params = [], $types = '') {
    $result = admin_query_select($koneksi, $sql, $params, $types);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    mysqli_free_result($result);
    return $rows;
}

/**
 * Execute Prepared Statement - COUNT
 */
function admin_query_count($koneksi, $sql, $params = [], $types = '') {
    $result = admin_query_select($koneksi, $sql, $params, $types);
    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    return (int)($row['total'] ?? 0);
}

/**
 * Execute Prepared Statement - INSERT/UPDATE/DELETE
 */
function admin_query_execute($koneksi, $sql, $params = [], $types = '') {
    $stmt = mysqli_prepare($koneksi, $sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . mysqli_error($koneksi));
    }
    
    if (!empty($params)) {
        if (empty($types)) {
            // Auto-detect types
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
        }
        
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Execute failed: ' . mysqli_stmt_error($stmt));
    }
    
    $affected = mysqli_stmt_affected_rows($stmt);
    $insert_id = mysqli_stmt_insert_id($stmt);
    mysqli_stmt_close($stmt);
    
    return [
        'affected' => $affected,
        'insert_id' => $insert_id,
        'success' => $affected >= 0
    ];
}

/**
 * Check If Record Exists
 */
function admin_record_exists($koneksi, $table, $id_column, $id_value) {
    $sql = "SELECT 1 FROM `" . preg_replace('/[^a-zA-Z0-9_]/', '', $table) . "` WHERE `" . preg_replace('/[^a-zA-Z0-9_]/', '', $id_column) . "` = ? LIMIT 1";
    $result = admin_query_select($koneksi, $sql, [$id_value], 'i');
    $exists = mysqli_fetch_assoc($result) !== null;
    mysqli_free_result($result);
    return $exists;
}

/**
 * Check If Value Already Exists (untuk duplicate check)
 */
function admin_value_exists($koneksi, $table, $column, $value, $exclude_id = null, $id_column = 'id') {
    $sql = "SELECT 1 FROM `" . preg_replace('/[^a-zA-Z0-9_]/', '', $table) . "` WHERE `" . preg_replace('/[^a-zA-Z0-9_]/', '', $column) . "` = ?";
    $params = [$value];
    $types = 's';
    
    if ($exclude_id) {
        $sql .= " AND `" . preg_replace('/[^a-zA-Z0-9_]/', '', $id_column) . "` != ?";
        $params[] = $exclude_id;
        $types .= 'i';
    }
    
    $sql .= " LIMIT 1";
    $result = admin_query_select($koneksi, $sql, $params, $types);
    $exists = mysqli_fetch_assoc($result) !== null;
    mysqli_free_result($result);
    return $exists;
}

// ==================== FILE UPLOAD HANDLERS ====================

/**
 * Validate File Upload
 * Check file size, type, MIME type
 */
function admin_validate_file_upload($file_array, $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'], $max_size = 5242880) {
    // Check if file was uploaded
    if (!isset($file_array['tmp_name']) || !is_uploaded_file($file_array['tmp_name'])) {
        return ['success' => false, 'error' => 'File not uploaded properly'];
    }
    
    // Check file size
    if ($file_array['size'] > $max_size) {
        return ['success' => false, 'error' => 'File size exceeds limit (max ' . ($max_size / 1024 / 1024) . 'MB)'];
    }
    
    // Check extension
    $ext = strtolower(pathinfo($file_array['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_extensions)) {
        return ['success' => false, 'error' => 'Invalid file type. Allowed: ' . implode(', ', $allowed_extensions)];
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file_array['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];
    
    if (!isset($allowed_mimes[$mime])) {
        return ['success' => false, 'error' => 'Invalid MIME type: ' . $mime];
    }
    
    return ['success' => true, 'ext' => $ext, 'mime' => $mime];
}

/**
 * Process File Upload
 * Save file dengan nama yang aman
 */
function admin_process_file_upload($file_array, $upload_dir, $prefix = 'file', $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'], $max_size = 5242880) {
    // Validate file
    $validation = admin_validate_file_upload($file_array, $allowed_extensions, $max_size);
    if (!$validation['success']) {
        return ['success' => false, 'error' => $validation['error']];
    }
    
    $ext = $validation['ext'];
    
    // Create upload directory jika belum ada
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            return ['success' => false, 'error' => 'Failed to create upload directory'];
        }
    }
    
    // Generate safe filename
    $filename = $prefix . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $filepath = $upload_dir . DIRECTORY_SEPARATOR . $filename;
    
    // Move file
    if (!move_uploaded_file($file_array['tmp_name'], $filepath)) {
        return ['success' => false, 'error' => 'Failed to move uploaded file'];
    }
    
    // Set proper permissions
    chmod($filepath, 0644);
    
    return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
}

/**
 * Delete File Safely
 */
function admin_delete_file($filepath) {
    if (empty($filepath)) {
        return true;
    }
    
    // Prevent directory traversal
    $filepath = realpath($filepath);
    if (!$filepath) {
        return false;
    }
    
    // Check if file exists and is a file (not directory)
    if (is_file($filepath)) {
        return @unlink($filepath);
    }
    
    return false;
}

/**
 * Delete Old File When Updating
 */
function admin_replace_file($new_file, $old_filepath, $upload_dir, $prefix = 'file', $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'], $max_size = 5242880) {
    // Process new file
    $upload_result = admin_process_file_upload($new_file, $upload_dir, $prefix, $allowed_extensions, $max_size);
    
    if (!$upload_result['success']) {
        return ['success' => false, 'error' => $upload_result['error']];
    }
    
    // Delete old file
    if (!empty($old_filepath)) {
        admin_delete_file($upload_dir . DIRECTORY_SEPARATOR . $old_filepath);
    }
    
    return $upload_result;
}

// ==================== PAGINATION ====================

/**
 * Calculate Pagination
 */
function admin_pagination_calc($total_records, $per_page = 10, $current_page = 1) {
    $total_pages = ceil($total_records / $per_page);
    $current_page = max(1, min($current_page, $total_pages));
    $offset = ($current_page - 1) * $per_page;
    
    return [
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'per_page' => $per_page,
        'offset' => $offset,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $total_pages,
        'prev_page' => max(1, $current_page - 1),
        'next_page' => min($total_pages, $current_page + 1)
    ];
}

// ==================== SEARCH UTILITIES ====================

/**
 * Build Safe Search Query
 * Untuk LIKE search yang aman dengan prepared statement
 */
function admin_build_search_where($fields = [], $search_term = '') {
    if (empty($search_term) || empty($fields)) {
        return ['where' => '', 'params' => [], 'types' => ''];
    }
    
    $search_term = '%' . $search_term . '%';
    $where_parts = [];
    $params = [];
    $types = '';
    
    foreach ($fields as $field) {
        // Sanitize field name
        $field = preg_replace('/[^a-zA-Z0-9_`.]/', '', $field);
        $where_parts[] = "`" . $field . "` LIKE ?";
        $params[] = $search_term;
        $types .= 's';
    }
    
    return [
        'where' => '(' . implode(' OR ', $where_parts) . ')',
        'params' => $params,
        'types' => $types
    ];
}

// ==================== TRANSACTION HELPERS ====================

/**
 * Begin Transaction
 */
function admin_transaction_begin($koneksi) {
    mysqli_begin_transaction($koneksi, MYSQLI_TRANS_START_READ_WRITE);
}

/**
 * Commit Transaction
 */
function admin_transaction_commit($koneksi) {
    mysqli_commit($koneksi);
}

/**
 * Rollback Transaction
 */
function admin_transaction_rollback($koneksi) {
    mysqli_rollback($koneksi);
}

/**
 * Execute with Transaction
 * Handy function untuk wrap operations dalam transaction
 */
function admin_execute_transaction($koneksi, $callback) {
    try {
        admin_transaction_begin($koneksi);
        $result = call_user_func($callback, $koneksi);
        admin_transaction_commit($koneksi);
        return $result;
    } catch (Throwable $e) {
        admin_transaction_rollback($koneksi);
        throw $e;
    }
}

// ==================== RESPONSE HELPERS ====================

/**
 * Build Success Response
 */
function admin_response_success($message, $data = []) {
    return [
        'success' => true,
        'message' => $message,
        'data' => $data
    ];
}

/**
 * Build Error Response
 */
function admin_response_error($message) {
    return [
        'success' => false,
        'message' => $message,
        'data' => null
    ];
}

/**
 * Output JSON Response
 */
function admin_json_response($response) {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// ==================== LOGGING ====================

/**
 * Log Admin Action
 * Untuk audit trail
 */
function admin_log_action($koneksi, $id_user, $action, $table, $record_id, $details = '') {
    $sql = "INSERT INTO admin_logs (id_user, action, table_name, record_id, details, ip_address, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    try {
        admin_query_execute($koneksi, $sql, [
            $id_user,
            $action,
            $table,
            $record_id,
            $details,
            $ip
        ], 'isssss');
    } catch (Exception $e) {
        // Silently fail - jangan block operation jika logging gagal
        error_log('Admin action logging failed: ' . $e->getMessage());
    }
}

?>
