<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/admin_functions_secure.php');

// Check auth
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Validate CSRF for POST, allow GET with confirmation for backward compat
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header('Location: manajemen_kantin.php?error=Invalid+CSRF+token');
        exit();
    }
    $id_kantin = admin_validate_id($_POST['id'] ?? 0);
} else {
    $id_kantin = admin_validate_id($_GET['id'] ?? 0);
}

if (!$id_kantin) {
    header('Location: manajemen_kantin.php?error=Data+tidak+valid');
    exit();
}

try {
    // Fetch kantin data with logo and banner
    $kantin = admin_query_fetch_one($koneksi, 
        "SELECT id_kantin, logo, banner FROM kantin WHERE id_kantin = ? LIMIT 1", 
        [$id_kantin], 'i');
    
    if (!$kantin) {
        header('Location: manajemen_kantin.php?error=Kantin+tidak+ditemukan');
        exit();
    }
    
    // id_user tidak lagi ada di tabel kantin
    $logo_file = $kantin['logo'] ?? null;
    $banner_file = $kantin['banner'] ?? null;
    
    // Fetch all menu photos before deletion so we can clean up files
    $menu_photos = [];
    $menu_list = admin_query_fetch_all($koneksi, 
        "SELECT foto, foto_menu FROM menu WHERE id_kantin = ?", 
        [$id_kantin], 'i');
    foreach ($menu_list as $m) {
        if (!empty($m['foto'])) $menu_photos[] = $m['foto'];
        if (!empty($m['foto_menu'])) $menu_photos[] = $m['foto_menu'];
    }
    
    // Execute delete with transaction
    admin_execute_transaction($koneksi, function($koneksi) use ($id_kantin, $id_user, $logo_file, $banner_file) {
        // 1. Delete ulasan (reviews) for menus in this canteen
        admin_query_execute($koneksi, 
            "DELETE FROM ulasan WHERE id_menu IN (SELECT id_menu FROM menu WHERE id_kantin = ?)", 
            [$id_kantin], 'i');
        
        // 2. Delete rating_menu for menus in this canteen
        admin_query_execute($koneksi, 
            "DELETE FROM rating_menu WHERE id_menu IN (SELECT id_menu FROM menu WHERE id_kantin = ?)", 
            [$id_kantin], 'i');
        
        // 3. Delete keranjang (cart items) for menus in this canteen
        admin_query_execute($koneksi, 
            "DELETE FROM keranjang WHERE id_menu IN (SELECT id_menu FROM menu WHERE id_kantin = ?)", 
            [$id_kantin], 'i');
        
        // 4. Delete favorit for menus in this canteen
        admin_query_execute($koneksi, 
            "DELETE FROM favorit WHERE id_menu IN (SELECT id_menu FROM menu WHERE id_kantin = ?)", 
            [$id_kantin], 'i');
        
        // 5. Delete detail_pesanan for menus in this canteen
        admin_query_execute($koneksi, 
            "DELETE FROM detail_pesanan WHERE id_menu IN (SELECT id_menu FROM menu WHERE id_kantin = ?)", 
            [$id_kantin], 'i');
        
        // 6. Delete detail_transaksi for menus in this canteen
        admin_query_execute($koneksi, 
            "DELETE FROM detail_transaksi WHERE id_menu IN (SELECT id_menu FROM menu WHERE id_kantin = ?)", 
            [$id_kantin], 'i');
        
        // 7. Delete pesanan for this canteen
        admin_query_execute($koneksi, 
            "DELETE FROM pesanan WHERE id_kantin = ?", 
            [$id_kantin], 'i');
        
        // 8. Delete transaksi for this canteen
        admin_query_execute($koneksi, 
            "DELETE FROM transaksi WHERE id_kantin = ?", 
            [$id_kantin], 'i');
        
        // 9. Delete all menus for this canteen
        admin_query_execute($koneksi, 
            "DELETE FROM menu WHERE id_kantin = ?", 
            [$id_kantin], 'i');
        
        // 10. Delete kantin
        admin_query_execute($koneksi, 
            "DELETE FROM kantin WHERE id_kantin = ?", 
            [$id_kantin], 'i');
        
        // Cek apakah id_user masih terhubung dengan kantin lain
        $kantin_count = 0;
        if ($id_user > 0) {
            $check = admin_query_fetch_all($koneksi, "SELECT id_kantin FROM kantin WHERE id_user = ?", [$id_user], 'i');
            $kantin_count = count($check);
        }
        
        // 11. Delete user if linked via id_user AND no other kantins reference them
        if ($id_user > 0 && $kantin_count === 0) {
            admin_query_execute($koneksi, 
                "DELETE FROM users WHERE id_user = ? AND role = 'penjual'", 
                [$id_user], 'i');
        }
        
        // Removed id_penjual logic as it doesn't exist.
        
        // Log action (will silently fail if admin_logs table doesn't exist)
        admin_log_action($koneksi, $_SESSION['id_user'], 'DELETE', 'kantin', $id_kantin, 
            "Deleted kantin with logo: $logo_file, banner: $banner_file");
    });
    
    // Delete files after successful deletion
    if ($logo_file) {
        admin_delete_file(__DIR__ . '/../../uploads/' . $logo_file);
    }
    if ($banner_file) {
        admin_delete_file(__DIR__ . '/../../uploads/' . $banner_file);
    }
    // Delete menu photo files
    foreach ($menu_photos as $photo) {
        admin_delete_file(__DIR__ . '/../../uploads/' . $photo);
    }
    
    header('Location: manajemen_kantin.php?success=hapus');
    exit();
    
} catch (Throwable $e) {
    error_log('Delete kantin error: ' . $e->getMessage());
    header('Location: manajemen_kantin.php?error=' . urlencode('Gagal menghapus kantin: ' . $e->getMessage()));
    exit();
}

