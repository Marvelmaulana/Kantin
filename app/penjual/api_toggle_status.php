<?php
/**
 * API: Toggle Status Kantin (Manual)
 * Untuk mode operasi manual, penjual bisa langsung toggle status buka/tutup
 * 
 * Method: POST
 * Parameters:
 *   - status: 'Buka' atau 'Tutup'
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    SELECT id_kantin, id_user, status_buka, tipe_operasi
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
    AND (id_kantin = $id_kantin OR id_user = {$kantin_data['id_user']})
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
// PARSING & VALIDASI INPUT
// ================================
$data = $_POST;

$status = isset($data['status']) ? trim($data['status']) : '';

// Validasi status
if (!in_array($status, ['Buka', 'Tutup'], true)) {
    // Default: toggle dari status saat ini
    $new_status = ($kantin_data['status_buka'] === 'Buka') ? 'Tutup' : 'Buka';
} else {
    $new_status = $status;
}

// Jika mode otomatis, tidak boleh di-toggle manual
if ($kantin_data['tipe_operasi'] === 'otomatis') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Kantin menggunakan mode otomatis. Ubah ke mode manual terlebih dahulu untuk toggle status.',
        'current_mode' => $kantin_data['tipe_operasi']
    ]);
    exit;
}

// ================================
// UPDATE DATABASE
// ================================
try {
    $new_status_esc = mysqli_real_escape_string($koneksi, $new_status);

    $update_query = mysqli_query($koneksi, "
        UPDATE kantin 
        SET status_buka = '$new_status_esc',
            updated_at = CURRENT_TIMESTAMP
        WHERE id_kantin = $id_kantin
    ");

    if (!$update_query) {
        throw new Exception('Database error: ' . mysqli_error($koneksi));
    }

    // Get updated data
    $get_updated = mysqli_query($koneksi, "
        SELECT status_buka, jam_buka, jam_tutup, tipe_operasi
        FROM kantin
        WHERE id_kantin = $id_kantin
        LIMIT 1
    ");

    $updated_data = mysqli_fetch_assoc($get_updated);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Status kantin berhasil diubah menjadi ' . $new_status,
        'data' => [
            'id_kantin' => $id_kantin,
            'status_buka' => $updated_data['status_buka'],
            'jam_buka' => substr($updated_data['jam_buka'], 0, 5),
            'jam_tutup' => substr($updated_data['jam_tutup'], 0, 5),
            'tipe_operasi' => $updated_data['tipe_operasi'],
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
