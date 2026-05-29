<?php
/**
 * Sidebar Admin
 * Navigasi sidebar untuk semua halaman admin
 * 
 * Session check: Ya
 * Role check: Admin only
 */

// Pastikan session sudah dimulai (jangan start lagi jika sudah running)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security: Validasi session
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Ambil halaman aktif
$current_page = basename($_SERVER['PHP_SELF']);

// Helper function untuk active link
function is_active($page) {
    return ($GLOBALS['current_page'] === $page) ? 'active' : '';
}

?>

<aside id="sidebar" class="fixed lg:static top-0 left-0 z-50 w-64 h-screen bg-gradient-to-b from-[#E25E3E] to-[#C2410C] text-white overflow-y-auto shadow-2xl transition-transform duration-300 ease-in-out">
    
    <!-- Logo & Brand -->
    <div class="p-6 border-b border-white/20">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-white/20 flex items-center justify-center">
                <span class="material-symbols-outlined text-lg">admin_panel_settings</span>
            </div>
            <div>
                <h1 class="text-lg font-bold">Kantin Kita</h1>
                <p class="text-xs text-white/70">Panel Admin</p>
            </div>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="px-3 py-6 space-y-2">
        
        <!-- Dashboard -->
        <a href="dashboard_admin.php" class="menu-link <?= is_active('dashboard_admin.php') ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition-colors duration-200 <?= ($current_page === 'dashboard_admin.php') ? 'bg-white/20 font-semibold' : '' ?>">
            <span class="material-symbols-outlined text-xl">dashboard</span>
            <span>Dashboard</span>
        </a>

        <!-- Separator -->
        <div class="my-4 border-t border-white/20"></div>
        <p class="px-4 text-xs font-semibold text-white/60 uppercase tracking-widest">Management</p>

        <!-- Kantin Management -->
        <a href="manajemen_kantin.php" class="menu-link flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition-colors duration-200 <?= ($current_page === 'manajemen_kantin.php') ? 'bg-white/20 font-semibold' : '' ?>">
            <span class="material-symbols-outlined text-xl">storefront</span>
            <span>Kelola Kantin</span>
        </a>

        <!-- Penjual Management -->
        <a href="manajemen_penjual.php" class="menu-link flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition-colors duration-200 <?= ($current_page === 'manajemen_penjual.php') ? 'bg-white/20 font-semibold' : '' ?>">
            <span class="material-symbols-outlined text-xl">store</span>
            <span>Kelola Penjual</span>
        </a>

        <!-- Menu Management -->
        <a href="manajemen_menu.php" class="menu-link flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition-colors duration-200 <?= ($current_page === 'manajemen_menu.php') ? 'bg-white/20 font-semibold' : '' ?>">
            <span class="material-symbols-outlined text-xl">restaurant_menu</span>
            <span>Kelola Menu</span>
        </a>

        <!-- User Management -->
        <a href="manajemen_user.php" class="menu-link flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition-colors duration-200 <?= ($current_page === 'manajemen_user.php') ? 'bg-white/20 font-semibold' : '' ?>">
            <span class="material-symbols-outlined text-xl">group</span>
            <span>Kelola User</span>
        </a>

        <!-- Siswa/Student Management -->
        <a href="manajemen_siswa.php" class="menu-link flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition-colors duration-200 <?= ($current_page === 'manajemen_siswa.php') ? 'bg-white/20 font-semibold' : '' ?>">
            <span class="material-symbols-outlined text-xl">school</span>
            <span>Kelola Siswa</span>
        </a>

        <!-- Separator -->
        <div class="my-4 border-t border-white/20"></div>
        <p class="px-4 text-xs font-semibold text-white/60 uppercase tracking-widest">Laporan</p>

        <!-- Transaction Report -->
        <a href="laporan_transaksi.php" class="menu-link flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition-colors duration-200 <?= ($current_page === 'laporan_transaksi.php') ? 'bg-white/20 font-semibold' : '' ?>">
            <span class="material-symbols-outlined text-xl">receipt_long</span>
            <span>Laporan Transaksi</span>
        </a>

        <!-- Sales Report -->
        <a href="laporan_penjualan.php" class="menu-link flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition-colors duration-200 <?= ($current_page === 'laporan_penjualan.php') ? 'bg-white/20 font-semibold' : '' ?>">
            <span class="material-symbols-outlined text-xl">trending_up</span>
            <span>Laporan Penjualan</span>
        </a>

        <!-- Income Report -->
        <a href="laporan_pendapatan_admin.php" class="menu-link flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition-colors duration-200 <?= ($current_page === 'laporan_pendapatan_admin.php') ? 'bg-white/20 font-semibold' : '' ?>">
            <span class="material-symbols-outlined text-xl">attach_money</span>
            <span>Laporan Pajak</span>
        </a>

        <!-- Kantin Report -->
        <a href="laporan_kantin.php" class="menu-link flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition-colors duration-200 <?= ($current_page === 'laporan_kantin.php') ? 'bg-white/20 font-semibold' : '' ?>">
            <span class="material-symbols-outlined text-xl">summarize</span>
            <span>Laporan Kantin</span>
        </a>

        <!-- Separator -->
        <div class="my-4 border-t border-white/20"></div>

        <!-- Profile -->
        <a href="edit_profil.php" class="menu-link flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition-colors duration-200 <?= ($current_page === 'edit_profil.php') ? 'bg-white/20 font-semibold' : '' ?>">
            <span class="material-symbols-outlined text-xl">account_circle</span>
            <span>Profil Saya</span>
        </a>

        <!-- Logout -->
        <a href="../auth/logout.php" class="menu-link flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-red-500/30 transition-colors duration-200">
            <span class="material-symbols-outlined text-xl">logout</span>
            <span>Logout</span>
        </a>

    </nav>

    <!-- User Info Footer -->
    <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-white/20 bg-white/5">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                <span class="material-symbols-outlined">account_circle</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold truncate"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></p>
                <p class="text-xs text-white/70 truncate">Admin Account</p>
            </div>
        </div>
    </div>

</aside>

<!-- Overlay untuk mobile -->
<div id="overlay" class="fixed inset-0 bg-black/50 lg:hidden transition-opacity duration-300 hidden"></div>

<!-- Mobile Menu Toggle (di-include di setiap halaman) -->
<script>
    // Toggle sidebar on mobile
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        
        if (sidebar && overlay) {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('hidden');
        }
    }

    // Close sidebar when clicking overlay
    document.addEventListener('DOMContentLoaded', function() {
        const overlay = document.getElementById('overlay');
        if (overlay) {
            overlay.addEventListener('click', toggleSidebar);
        }

        // Close sidebar on navigation
        const navLinks = document.querySelectorAll('#sidebar .menu-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 1024) {
                    toggleSidebar();
                }
            });
        });
    });
</script>
