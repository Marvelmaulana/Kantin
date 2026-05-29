<?php
/**
 * API: Update Jam Operasional Kantin
 * Update jam buka dan jam tutup kantin
 * 
 * Method: POST
 * Parameters:
 *   - jam_buka: HH:MM format
 *   - jam_tutup: HH:MM format
 *   - tipe_operasi: 'manual' atau 'otomatis' (optional)
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
// PARSING & VALIDASI INPUT
// ================================
$data = $_POST;

$jam_buka = isset($data['jam_buka']) ? trim($data['jam_buka']) : '';
$jam_tutup = isset($data['jam_tutup']) ? trim($data['jam_tutup']) : '';
$tipe_operasi = isset($data['tipe_operasi']) ? trim($data['tipe_operasi']) : 'manual';

// Validasi format jam
$jam_buka_validated = kk_validate_time_format($jam_buka);
if (empty($jam_buka) || !$jam_buka_validated) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Jam buka tidak valid. Gunakan format HH:MM (contoh: 07:00)'
    ]);
    exit;
}

$jam_tutup_validated = kk_validate_time_format($jam_tutup);
if (empty($jam_tutup) || !$jam_tutup_validated) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Jam tutup tidak valid. Gunakan format HH:MM (contoh: 15:00)'
    ]);
    exit;
}

// Validasi tipe operasi
if (!in_array($tipe_operasi, ['manual', 'otomatis'], true)) {
    $tipe_operasi = 'manual';
}

// ================================
// UPDATE DATABASE
// ================================
try {
    // Format dengan validated time (sudah HH:MM:SS)
    $jam_buka_db = $jam_buka_validated;
    $jam_tutup_db = $jam_tutup_validated;

    $jam_buka_esc = mysqli_real_escape_string($koneksi, $jam_buka_db);
    $jam_tutup_esc = mysqli_real_escape_string($koneksi, $jam_tutup_db);
    $tipe_op_esc = mysqli_real_escape_string($koneksi, $tipe_operasi);

    $update_query = mysqli_query($koneksi, "
        UPDATE kantin 
        SET jam_buka = '$jam_buka_esc',
            jam_tutup = '$jam_tutup_esc',
            tipe_operasi = '$tipe_op_esc',
            updated_at = CURRENT_TIMESTAMP
        WHERE id_kantin = $id_kantin
    ");

    if (!$update_query) {
        throw new Exception('Database error: ' . mysqli_error($koneksi));
    }

    // Get updated data
    $get_updated = mysqli_query($koneksi, "
        SELECT jam_buka, jam_tutup, tipe_operasi, status_buka
        FROM kantin
        WHERE id_kantin = $id_kantin
        LIMIT 1
    ");

    $updated_data = mysqli_fetch_assoc($get_updated);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Jam operasional berhasil diperbarui',
        'data' => [
            'id_kantin' => $id_kantin,
            'jam_buka' => substr($updated_data['jam_buka'], 0, 5),
            'jam_tutup' => substr($updated_data['jam_tutup'], 0, 5),
            'tipe_operasi' => $updated_data['tipe_operasi'],
            'status_buka' => $updated_data['status_buka'],
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
