<?php
session_start();
include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/admin_functions_secure.php');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$id_menu = admin_validate_id($_POST['id_menu'] ?? 0);
$catatan_blokir = trim($_POST['catatan_blokir'] ?? '');

if (!$id_menu || $catatan_blokir === '') {
    header('Location: manajemen_menu.php?error=' . urlencode('Catatan pemblokiran wajib diisi.'));
    exit();
}

$catatan_blokir = admin_validate_string($catatan_blokir, 1, 500, true);
if ($catatan_blokir === null) {
    header('Location: manajemen_menu.php?error=' . urlencode('Catatan pemblokiran tidak valid.'));
    exit();
}

try {
    $menu = admin_query_fetch_one($koneksi,
        "SELECT id_menu, nama_menu, status FROM menu WHERE id_menu = ? LIMIT 1",
        [$id_menu], 'i'
    );

    if (!$menu) {
        header('Location: manajemen_menu.php?error=' . urlencode('Menu tidak ditemukan.'));
        exit();
    }

    if (in_array($menu['status'], ['Diblokir', 'Dinonaktifkan'], true)) {
        header('Location: manajemen_menu.php?error=' . urlencode('Menu sudah dalam status diblokir atau dinonaktifkan.'));
        exit();
    }

    admin_query_execute($koneksi,
        "UPDATE menu SET status = 'Diblokir', catatan_blokir = ? WHERE id_menu = ?",
        [$catatan_blokir, $id_menu], 'si'
    );

    if (function_exists('admin_log_action')) {
        admin_log_action($koneksi, $_SESSION['id_user'], 'UPDATE', 'menu', $id_menu,
            "Blokir menu: {$menu['nama_menu']} - $catatan_blokir");
    }

    header('Location: manajemen_menu.php?filter=blocked&message=' . urlencode('Menu berhasil diblokir.'));
    exit();
} catch (Exception $e) {
    header('Location: manajemen_menu.php?error=' . urlencode('Gagal memblokir menu: ' . $e->getMessage()));
    exit();
}
