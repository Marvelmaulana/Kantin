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
        header('Location: manajemen_menu.php?error=Invalid+CSRF+token');
        exit();
    }
    $id_menu = admin_validate_id($_POST['id'] ?? 0);
} else {
    // GET requests must have confirmation
    $id_menu = admin_validate_id($_GET['id'] ?? 0);
}

if (!$id_menu) {
    header('Location: manajemen_menu.php?error=Data+tidak+valid');
    exit();
}

try {
    // Fetch menu data to get photo file
    $menu = admin_query_fetch_one($koneksi,
        "SELECT id_menu, foto, foto_menu FROM menu WHERE id_menu = ? LIMIT 1",
        [$id_menu], 'i');
    
    if (!$menu) {
        header('Location: manajemen_menu.php?error=Menu+tidak+ditemukan');
        exit();
    }
    
    $foto_file = $menu['foto'] ?? null;
    $foto_menu_file = $menu['foto_menu'] ?? null;
    
    // Delete with transaction (cascade delete)
    admin_execute_transaction($koneksi, function($koneksi) use ($id_menu) {
        // Delete from keranjang first (foreign key)
        admin_query_execute($koneksi,
            "DELETE FROM keranjang WHERE id_menu = ?",
            [$id_menu], 'i');
        
        // Delete from favorit
        admin_query_execute($koneksi,
            "DELETE FROM favorit WHERE id_menu = ?",
            [$id_menu], 'i');
        
        // Delete from ulasan/rating
        admin_query_execute($koneksi,
            "DELETE FROM ulasan WHERE id_menu = ?",
            [$id_menu], 'i');
        
        admin_query_execute($koneksi,
            "DELETE FROM rating_menu WHERE id_menu = ?",
            [$id_menu], 'i');
        
        // Delete menu
        admin_query_execute($koneksi,
            "DELETE FROM menu WHERE id_menu = ?",
            [$id_menu], 'i');
        
        // Log action
        admin_log_action($koneksi, $_SESSION['id_user'], 'DELETE', 'menu', $id_menu,
            "Deleted menu");
    });
    
    // Delete image files after successful deletion
    if ($foto_file) {
        admin_delete_file(__DIR__ . '/../../uploads/' . $foto_file);
    }
    if ($foto_menu_file) {
        admin_delete_file(__DIR__ . '/../../uploads/' . $foto_menu_file);
    }
    
    header('Location: manajemen_menu.php?success=hapus');
    exit();
    
} catch (Throwable $e) {
    error_log('Delete menu error: ' . $e->getMessage());
    header('Location: manajemen_menu.php?error=' . urlencode('Gagal menghapus menu'));
    exit();
}
