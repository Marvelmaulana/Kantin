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
        "SELECT id_kantin, id_user, logo, banner FROM kantin WHERE id_kantin = ? LIMIT 1", 
        [$id_kantin], 'i');
    
    if (!$kantin) {
        header('Location: manajemen_kantin.php?error=Kantin+tidak+ditemukan');
        exit();
    }
    
    $id_user = (int)($kantin['id_user'] ?? 0);
    $logo_file = $kantin['logo'] ?? null;
    $banner_file = $kantin['banner'] ?? null;
    
    // Execute delete with transaction
    admin_execute_transaction($koneksi, function($koneksi) use ($id_kantin, $id_user, $logo_file, $banner_file) {
        // Delete dependent records (cascade)
        admin_query_execute($koneksi, 
            "DELETE FROM keranjang WHERE id_menu IN (SELECT id_menu FROM menu WHERE id_kantin = ?)", 
            [$id_kantin], 'i');
        
        admin_query_execute($koneksi, 
            "DELETE FROM favorit WHERE id_menu IN (SELECT id_menu FROM menu WHERE id_kantin = ?)", 
            [$id_kantin], 'i');
        
        admin_query_execute($koneksi, 
            "DELETE FROM menu WHERE id_kantin = ?", 
            [$id_kantin], 'i');
        
        // Delete kantin
        admin_query_execute($koneksi, 
            "DELETE FROM kantin WHERE id_kantin = ?", 
            [$id_kantin], 'i');
        
        // Update user if exists
        if ($id_user > 0) {
            admin_query_execute($koneksi, 
                "UPDATE users SET id_kantin = NULL, nama_kantin = NULL WHERE id_user = ? AND role = 'penjual' AND id_kantin = ?", 
                [$id_user, $id_kantin], 'ii');
        }
        
        // Log action
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
    
    header('Location: manajemen_kantin.php?success=hapus');
    exit();
    
} catch (Throwable $e) {
    error_log('Delete kantin error: ' . $e->getMessage());
    header('Location: manajemen_kantin.php?error=' . urlencode('Gagal menghapus kantin: ' . $e->getMessage()));
    exit();
}

