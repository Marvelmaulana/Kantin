<?php
/**
 * Student Management Helper Functions
 * Fungsi-fungsi untuk manajemen siswa dan kelas
 */

if (!function_exists('get_siswa_list')) {
    /**
     * Ambil daftar semua siswa dengan filter optional
     * 
     * @param mysqli $koneksi
     * @param string $kelas Filter kelas (optional): '10', '11', '12', atau null untuk semua
     * @param string $order_by Order by column (default: 'username')
     * @return array Array of siswa records
     */
    function get_siswa_list($koneksi, $kelas = null, $order_by = 'username ASC') {
        $order_by = preg_replace('/[^a-zA-Z0-9_\s,ASC DESC]/i', '', $order_by);
        if (empty($order_by)) $order_by = 'username ASC';
        
        $where = "WHERE role = 'pembeli' AND tipe_pengguna = 'siswa'";
        
        if ($kelas !== null && in_array($kelas, ['10', '11', '12'])) {
            $kelas = mysqli_real_escape_string($koneksi, $kelas);
            $where .= " AND kelas = '$kelas'";
        }
        
        $query = "SELECT id_user, username, email, kelas, created_at, updated_at FROM users $where ORDER BY $order_by";
        $result = mysqli_query($koneksi, $query);
        
        $siswa_list = [];
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $siswa_list[] = $row;
            }
        }
        
        return $siswa_list;
    }
}

if (!function_exists('get_siswa_count')) {
    /**
     * Hitung total siswa dengan filter optional
     * 
     * @param mysqli $koneksi
     * @param string $kelas Filter kelas (optional)
     * @return int Total count
     */
    function get_siswa_count($koneksi, $kelas = null) {
        $where = "WHERE role = 'pembeli' AND tipe_pengguna = 'siswa'";
        
        if ($kelas !== null && in_array($kelas, ['10', '11', '12'])) {
            $kelas = mysqli_real_escape_string($koneksi, $kelas);
            $where .= " AND kelas = '$kelas'";
        }
        
        $query = "SELECT COUNT(*) as total FROM users $where";
        $result = mysqli_query($koneksi, $query);
        
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            return (int)($row['total'] ?? 0);
        }
        
        return 0;
    }
}

if (!function_exists('get_guru_list')) {
    /**
     * Ambil daftar semua guru
     * 
     * @param mysqli $koneksi
     * @param string $order_by Order by column (default: 'username')
     * @return array Array of guru records
     */
    function get_guru_list($koneksi, $order_by = 'username ASC') {
        $order_by = preg_replace('/[^a-zA-Z0-9_\s,ASC DESC]/i', '', $order_by);
        if (empty($order_by)) $order_by = 'username ASC';
        
        $query = "SELECT id_user, username, email, nip, created_at, updated_at 
                 FROM users 
                 WHERE role IN ('pembeli', 'penjual') AND tipe_pengguna = 'guru'
                 ORDER BY $order_by";
        $result = mysqli_query($koneksi, $query);
        
        $guru_list = [];
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $guru_list[] = $row;
            }
        }
        
        return $guru_list;
    }
}

if (!function_exists('promote_student_kelas')) {
    /**
     * Naikkan kelas seorang siswa
     * Kelas 10 → 11, Kelas 11 → 12
     * Kelas 12 akan dihapus
     * 
     * @param mysqli $koneksi
     * @param int $id_user ID user siswa
     * @return array ['success' => bool, 'message' => string]
     */
    function promote_student_kelas($koneksi, $id_user) {
        $id_user = (int)$id_user;
        
        // Ambil data siswa
        $query = "SELECT id_user, kelas FROM users WHERE id_user = $id_user AND role = 'pembeli' AND tipe_pengguna = 'siswa'";
        $result = mysqli_query($koneksi, $query);
        
        if (!$result || mysqli_num_rows($result) === 0) {
            return ['success' => false, 'message' => 'Siswa tidak ditemukan'];
        }
        
        $student = mysqli_fetch_assoc($result);
        $current_kelas = $student['kelas'];
        
        // Validasi kelas
        if (!in_array($current_kelas, ['10', '11', '12'])) {
            return ['success' => false, 'message' => 'Data kelas tidak valid'];
        }
        
        // Tentukan kelas baru
        $new_kelas = null;
        if ($current_kelas === '10') {
            $new_kelas = '11';
        } elseif ($current_kelas === '11') {
            $new_kelas = '12';
        } elseif ($current_kelas === '12') {
            // Kelas 12 akan dihapus
            $delete_query = "DELETE FROM users WHERE id_user = $id_user";
            if (mysqli_query($koneksi, $delete_query)) {
                return ['success' => true, 'message' => 'Siswa kelas 12 berhasil dihapus'];
            } else {
                return ['success' => false, 'message' => 'Gagal menghapus siswa: ' . mysqli_error($koneksi)];
            }
        }
        
        // Update kelas
        if ($new_kelas !== null) {
            $update_query = "UPDATE users SET kelas = '$new_kelas' WHERE id_user = $id_user";
            if (mysqli_query($koneksi, $update_query)) {
                return ['success' => true, 'message' => "Kelas berhasil dinaikkan dari $current_kelas ke $new_kelas"];
            } else {
                return ['success' => false, 'message' => 'Gagal mengupdate kelas: ' . mysqli_error($koneksi)];
            }
        }
        
        return ['success' => false, 'message' => 'Terjadi kesalahan'];
    }
}

if (!function_exists('promote_all_students')) {
    /**
     * Naikkan kelas semua siswa (Kenaikan Kelas Otomatis)
     * Kelas 10 → 11, Kelas 11 → 12
     * Kelas 12 otomatis dihapus
     * 
     * @param mysqli $koneksi
     * @return array ['success' => bool, 'message' => string, 'detail' => array]
     */
    function promote_all_students($koneksi) {
        // Start transaction
        if (!mysqli_query($koneksi, "START TRANSACTION")) {
            return [
                'success' => false,
                'message' => 'Gagal memulai transaksi: ' . mysqli_error($koneksi)
            ];
        }
        
        try {
            $detail = [
                'deleted_kelas_12' => 0,
                'promoted_10_to_11' => 0,
                'promoted_11_to_12' => 0,
                'errors' => []
            ];
            
            // 1. Hapus semua siswa kelas 12
            $delete_query = "DELETE FROM users WHERE role = 'pembeli' AND tipe_pengguna = 'siswa' AND kelas = '12'";
            if (!mysqli_query($koneksi, $delete_query)) {
                throw new Exception('Gagal menghapus siswa kelas 12: ' . mysqli_error($koneksi));
            }
            $detail['deleted_kelas_12'] = mysqli_affected_rows($koneksi);
            
            // 2. Naikkan siswa kelas 11 ke 12
            $update_11_query = "UPDATE users SET kelas = '12' WHERE role = 'pembeli' AND tipe_pengguna = 'siswa' AND kelas = '11'";
            if (!mysqli_query($koneksi, $update_11_query)) {
                throw new Exception('Gagal menaikkan siswa kelas 11: ' . mysqli_error($koneksi));
            }
            $detail['promoted_11_to_12'] = mysqli_affected_rows($koneksi);
            
            // 3. Naikkan siswa kelas 10 ke 11
            $update_10_query = "UPDATE users SET kelas = '11' WHERE role = 'pembeli' AND tipe_pengguna = 'siswa' AND kelas = '10'";
            if (!mysqli_query($koneksi, $update_10_query)) {
                throw new Exception('Gagal menaikkan siswa kelas 10: ' . mysqli_error($koneksi));
            }
            $detail['promoted_10_to_11'] = mysqli_affected_rows($koneksi);
            
            // Commit transaction
            if (!mysqli_query($koneksi, "COMMIT")) {
                throw new Exception('Gagal commit transaksi: ' . mysqli_error($koneksi));
            }
            
            $message = "Kenaikan kelas otomatis berhasil!\n";
            $message .= "- Siswa kelas 12 dihapus: {$detail['deleted_kelas_12']}\n";
            $message .= "- Siswa kelas 11 → 12: {$detail['promoted_11_to_12']}\n";
            $message .= "- Siswa kelas 10 → 11: {$detail['promoted_10_to_11']}";
            
            return [
                'success' => true,
                'message' => $message,
                'detail' => $detail
            ];
            
        } catch (Exception $e) {
            // Rollback on error
            mysqli_query($koneksi, "ROLLBACK");
            
            return [
                'success' => false,
                'message' => 'Kenaikan kelas gagal: ' . $e->getMessage(),
                'detail' => ['errors' => [$e->getMessage()]]
            ];
        }
    }
}

if (!function_exists('delete_siswa_kelas_12')) {
    /**
     * Hapus semua siswa kelas 12
     * 
     * @param mysqli $koneksi
     * @return array ['success' => bool, 'message' => string, 'deleted_count' => int]
     */
    function delete_siswa_kelas_12($koneksi) {
        $query = "DELETE FROM users WHERE role = 'pembeli' AND tipe_pengguna = 'siswa' AND kelas = '12'";
        
        if (mysqli_query($koneksi, $query)) {
            $deleted_count = mysqli_affected_rows($koneksi);
            
            return [
                'success' => true,
                'message' => "Berhasil menghapus $deleted_count siswa kelas 12",
                'deleted_count' => $deleted_count
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Gagal menghapus siswa: ' . mysqli_error($koneksi),
                'deleted_count' => 0
            ];
        }
    }
}

if (!function_exists('delete_student_by_id')) {
    /**
     * Hapus seorang siswa berdasarkan ID
     * 
     * @param mysqli $koneksi
     * @param int $id_user
     * @return array ['success' => bool, 'message' => string]
     */
    function delete_student_by_id($koneksi, $id_user) {
        $id_user = (int)$id_user;
        
        // Verifikasi bahwa ini adalah siswa
        $check_query = "SELECT id_user FROM users WHERE id_user = $id_user AND role = 'pembeli' AND tipe_pengguna = 'siswa'";
        $check_result = mysqli_query($koneksi, $check_query);
        
        if (!$check_result || mysqli_num_rows($check_result) === 0) {
            return ['success' => false, 'message' => 'Siswa tidak ditemukan'];
        }
        
        // Hapus siswa
        $delete_query = "DELETE FROM users WHERE id_user = $id_user";
        
        if (mysqli_query($koneksi, $delete_query)) {
            return ['success' => true, 'message' => 'Siswa berhasil dihapus'];
        } else {
            return ['success' => false, 'message' => 'Gagal menghapus siswa: ' . mysqli_error($koneksi)];
        }
    }
}

if (!function_exists('get_siswa_by_id')) {
    /**
     * Ambil data siswa berdasarkan ID
     * 
     * @param mysqli $koneksi
     * @param int $id_user
     * @return array|false Data siswa atau false jika tidak ditemukan
     */
    function get_siswa_by_id($koneksi, $id_user) {
        $id_user = (int)$id_user;
        
        $query = "SELECT id_user, username, email, kelas, created_at, updated_at 
                 FROM users 
                 WHERE id_user = $id_user AND role = 'pembeli' AND tipe_pengguna = 'siswa'";
        $result = mysqli_query($koneksi, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }
        
        return false;
    }
}

if (!function_exists('get_kelas_label')) {
    /**
     * Ambil label kelas yang lebih readable
     * 
     * @param string $kelas Kelas value ('10', '11', '12')
     * @return string Label kelas (e.g., 'Kelas X')
     */
    function get_kelas_label($kelas) {
        $labels = [
            '10' => 'Kelas X',
            '11' => 'Kelas XI',
            '12' => 'Kelas XII'
        ];
        
        return $labels[$kelas] ?? 'Tidak Diketahui';
    }
}

if (!function_exists('validate_kelas')) {
    /**
     * Validasi nilai kelas
     * 
     * @param string $kelas
     * @return bool
     */
    function validate_kelas($kelas) {
        return in_array($kelas, ['10', '11', '12'], true);
    }
}

if (!function_exists('get_kelas_options')) {
    /**
     * Ambil daftar opsi kelas
     * 
     * @return array Array of kelas options
     */
    function get_kelas_options() {
        return [
            ['value' => '10', 'label' => 'Kelas X (10)'],
            ['value' => '11', 'label' => 'Kelas XI (11)'],
            ['value' => '12', 'label' => 'Kelas XII (12)']
        ];
    }
}

if (!function_exists('get_total_students_by_class')) {
    /**
     * Ambil statistik jumlah siswa per kelas
     * 
     * @param mysqli $koneksi
     * @return array [['kelas' => '10', 'count' => int], ...]
     */
    function get_total_students_by_class($koneksi) {
        $query = "
            SELECT kelas, COUNT(*) as count 
            FROM users 
            WHERE role = 'pembeli' AND tipe_pengguna = 'siswa' AND kelas IS NOT NULL
            GROUP BY kelas
            ORDER BY kelas ASC
        ";
        
        $result = mysqli_query($koneksi, $query);
        $stats = [];
        
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $stats[] = [
                    'kelas' => $row['kelas'],
                    'label' => get_kelas_label($row['kelas']),
                    'count' => (int)$row['count']
                ];
            }
        }
        
        return $stats;
    }
}
?>
