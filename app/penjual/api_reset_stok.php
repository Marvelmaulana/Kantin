<?php
/**
 * API: Reset Stok Harian
 * Menjalankan reset stok untuk semua menu penjual
 * 
 * Method: POST
 * Parameter: id_kantin (dari session)
 */

include('../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');
kk_ensure_buyer_schema($koneksi);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ================================
// VALIDASI SESSION & AUTHORIZATION
// ================================
if (!isset($_SESSION['id_user']) || !isset($_SESSION['id_kantin'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized: Silakan login terlebih dahulu'
    ]);
    exit;
}

if ($_REQUEST['_method'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method tidak diizinkan. Gunakan POST.'
    ]);
    exit;
}

$id_user = (int)$_SESSION['id_user'];
$id_kantin = (int)$_SESSION['id_kantin'];

// ================================
// VALIDASI OWNERSHIP KANTIN
// ================================
$check_kantin = mysqli_query($koneksi, "
    SELECT id_kantin, id_penjual 
    FROM kantin 
    WHERE id_kantin = $id_kantin 
    LIMIT 1
");

if (!$check_kantin || mysqli_num_rows($check_kantin) === 0) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Kantin tidak ditemukan'
    ]);
    exit;
}

$kantin_data = mysqli_fetch_assoc($check_kantin);

// Verifikasi user adalah penjual kantin ini
$verify_user = mysqli_query($koneksi, "
    SELECT id_user 
    FROM users 
    WHERE id_user = $id_user 
    AND role = 'penjual'
    AND (id_kantin = $id_kantin OR id_user = {$kantin_data['id_penjual']})
    LIMIT 1
");

if (!$verify_user || mysqli_num_rows($verify_user) === 0) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Anda tidak memiliki akses ke kantin ini'
    ]);
    exit;
}

// ================================
// RESET STOK
// ================================
try {
    $reset_query = mysqli_query($koneksi, "
        UPDATE menu 
        SET stok = 0, status = 'Habis'
        WHERE id_kantin = $id_kantin
    ");

    if (!$reset_query) {
        throw new Exception('Database error: ' . mysqli_error($koneksi));
    }

    $affected_rows = mysqli_affected_rows($koneksi);

    // Log aktivitas
    $log_message = "Reset stok harian untuk kantin ID: $id_kantin";
    error_log($log_message . " | " . date('Y-m-d H:i:s'));

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => "Stok berhasil direset untuk $affected_rows menu",
        'data' => [
            'id_kantin' => $id_kantin,
            'affected_rows' => $affected_rows,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
