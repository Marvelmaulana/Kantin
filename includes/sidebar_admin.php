<?php
// Determine current page reliably (handles query string and different URL structures)
$request_path = $_SERVER['REQUEST_URI'] ?? ($_SERVER['PHP_SELF'] ?? '');
$current_page = basename(parse_url($request_path, PHP_URL_PATH));
?>

<!-- Ensure icon fonts are available even if page head doesn't include them -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

<button onclick="toggleSidebar()" class="lg:hidden fixed top-4 left-4 z-[60] bg-rose-700/95 border border-rose-600 p-2 rounded-2xl shadow-2xl shadow-rose-500/20 text-white">
    <span class="material-symbols-outlined">menu</span>
</button>

<div id="overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black/30 z-40 hidden backdrop-blur-sm transition-opacity"></div>

<aside id="sidebar" class="fixed left-0 top-0 h-screen w-72 bg-[radial-gradient(circle_at_top_left,_rgba(255,255,255,0.22),_transparent_40%),linear-gradient(180deg,_#ef4444_0%,_#fb7185_35%,_#ea580c_100%)] border-r border-rose-700 shadow-[12px_0_60px_-30px_rgba(239,68,68,0.35)] flex flex-col overflow-hidden p-8 z-50 -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
    <div class="flex items-center gap-3 mb-12 bg-rose-500/10 rounded-[2rem] p-4 border border-white/10 shadow-lg shadow-rose-500/10">
        <div class="w-12 h-12 rounded-3xl bg-gradient-to-br from-rose-500 via-orange-500 to-orange-600 flex items-center justify-center text-white shadow-2xl shadow-rose-500/30 border border-white/10">
            <span class="material-symbols-outlined text-2xl">restaurant</span>
        </div>
        <div>
            <h1 class="text-2xl font-black text-white leading-tight tracking-wide">Kantin Kita</h1>
        </div>
    </div>

    <nav class="flex-1 space-y-2 overflow-y-auto pr-1 scrollbar-thin scrollbar-thumb-orange-400/70">
        <a href="dashboard_admin.php" class="flex items-center gap-4 px-4 py-4 rounded-[28px] font-bold transition-all <?= ($current_page == 'dashboard_admin.php') ? 'bg-[linear-gradient(90deg,_#fb923c,_#f97316,_#ea580c)] text-white shadow-2xl shadow-orange-400/30 scale-[1.01]' : 'text-orange-100 hover:bg-orange-900 hover:text-orange-50' ?>">
            <span class="material-symbols-outlined">grid_view</span>
            <span class="text-sm">Dashboard</span>
        </a>

        <div class="px-1">
            <div class="text-xs font-black text-rose-50 uppercase tracking-wider px-4 py-3 bg-rose-500/10 rounded-3xl">Data Master</div>
            <a href="manajemen_penjual.php" class="flex items-center gap-4 px-4 py-3 rounded-[22px] font-bold transition-all border border-transparent <?= ($current_page == 'manajemen_penjual.php') ? 'bg-white/20 text-white shadow-lg shadow-rose-500/20 border-white/20' : 'text-rose-50 hover:bg-white/15 hover:text-white' ?>">
                <span class="material-symbols-outlined">store</span>
                <span class="text-sm">Penjual</span>
            </a>
            <a href="manajemen_kantin.php" class="flex items-center gap-4 px-4 py-3 rounded-[22px] font-bold transition-all border border-transparent <?= ($current_page == 'manajemen_kantin.php') ? 'bg-white/20 text-white shadow-lg shadow-orange-500/20 border-white/20' : 'text-orange-50 hover:bg-white/15 hover:text-white' ?>">
                <span class="material-symbols-outlined">storefront</span>
                <span class="text-sm">Kantin</span>
            </a>
            <a href="manajemen_user.php" class="flex items-center gap-4 px-4 py-3 rounded-[22px] font-bold transition-all border border-transparent <?= ($current_page == 'manajemen_user.php') ? 'bg-white/20 text-white shadow-lg shadow-orange-500/20 border-white/20' : 'text-orange-50 hover:bg-white/15 hover:text-white' ?>">
                <span class="material-symbols-outlined">group</span>
                <span class="text-sm">Pembeli</span>
            </a>
        </div>

        <div class="px-1">
            <div class="text-xs font-black text-rose-50 uppercase tracking-wider px-4 py-3 bg-rose-500/10 rounded-3xl">Manajemen</div>
            <a href="manajemen_menu.php" class="flex items-center gap-4 px-4 py-3 rounded-[22px] font-bold transition-all border border-transparent <?= ($current_page == 'manajemen_menu.php') ? 'bg-white/20 text-white shadow-lg shadow-rose-500/20 border-white/20' : 'text-rose-50 hover:bg-white/15 hover:text-white' ?>">
                <span class="material-symbols-outlined">restaurant_menu</span>
                <span class="text-sm">Menu</span>
            </a>
        </div>

        <div class="px-1">
            <div class="text-xs font-black text-rose-50 uppercase tracking-wider px-4 py-3 bg-rose-500/10 rounded-3xl">Laporan</div>
            <a href="laporan_transaksi.php" class="flex items-center gap-4 px-4 py-3 rounded-[22px] font-bold transition-all border border-transparent <?= ($current_page == 'laporan_transaksi.php') ? 'bg-white/20 text-white shadow-lg shadow-rose-500/20 border-white/20' : 'text-rose-50 hover:bg-white/15 hover:text-white' ?>">
                <span class="material-symbols-outlined">receipt_long</span>
                <span class="text-sm">Laporan Transaksi</span>
            </a>
            <a href="laporan_penjualan.php" class="flex items-center gap-4 px-4 py-3 rounded-[22px] font-bold transition-all border border-transparent <?= ($current_page == 'laporan_penjualan.php') ? 'bg-white/20 text-white shadow-lg shadow-orange-500/20 border-white/20' : 'text-orange-50 hover:bg-white/15 hover:text-white' ?>">
                <span class="material-symbols-outlined">bar_chart</span>
                <span class="text-sm">Laporan Penjualan</span>
            </a>
            <a href="laporan_pendapatan_admin.php" class="flex items-center gap-4 px-4 py-3 rounded-[22px] font-bold transition-all border border-transparent <?= ($current_page == 'laporan_pendapatan_admin.php') ? 'bg-white/20 text-white shadow-lg shadow-orange-500/20 border-white/20' : 'text-orange-50 hover:bg-white/15 hover:text-white' ?>">
                <span class="material-symbols-outlined">attach_money</span>
                <span class="text-sm">Pendapatan Admin</span>
            </a>
            <a href="laporan_kantin.php" class="flex items-center gap-4 px-4 py-3 rounded-[22px] font-bold transition-all border border-transparent <?= ($current_page == 'laporan_kantin.php') ? 'bg-white/20 text-white shadow-lg shadow-orange-500/20 border-white/20' : 'text-orange-50 hover:bg-white/15 hover:text-white' ?>">
                <span class="material-symbols-outlined">domain</span>
                <span class="text-sm">Laporan Kantin</span>
            </a>
            <a href="laporan_pembeli.php" class="flex items-center gap-4 px-4 py-3 rounded-[22px] font-bold transition-all border border-transparent <?= ($current_page == 'laporan_pembeli.php') ? 'bg-white/20 text-white shadow-lg shadow-orange-500/20 border-white/20' : 'text-orange-50 hover:bg-white/15 hover:text-white' ?>">
                <span class="material-symbols-outlined">person</span>
                <span class="text-sm">Laporan Pembeli</span>
            </a>
        </div>
    </nav>

    <div class="mt-auto pt-6 border-t border-rose-200/70 mb-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-rose-700 via-orange-600 to-orange-500 flex items-center justify-center text-white text-xs font-bold uppercase shrink-0">AD</div>
        <div class="overflow-hidden">
            <p class="text-xs font-black text-white leading-none truncate">Admin Kantin</p>
            <p class="text-[9px] text-rose-100 font-bold uppercase mt-1">Super Admin</p>
        </div>
    </div>

   <a href="../auth/logout.php" class="flex items-center gap-4 px-4 py-3 bg-white/15 text-white hover:bg-white/25 rounded-2xl transition-all shadow-sm shadow-orange-500/10 border border-white/20">
    <span class="material-symbols-outlined">logout</span>
    <span class="text-sm font-bold">Logout</span>
</a>
</aside>

<script>
    // Restore sidebar scroll position saat halaman load
    window.addEventListener('load', function() {
        const nav = document.querySelector('nav');
        if (nav) {
            const savedScrollPos = sessionStorage.getItem('sidebar-scroll-pos');
            if (savedScrollPos !== null) {
                nav.scrollTop = parseInt(savedScrollPos);
            }
        }
    });

    // Simpan scroll position sebelum navigasi
    document.querySelectorAll('nav a').forEach(link => {
        link.addEventListener('click', function() {
            const nav = document.querySelector('nav');
            if (nav) {
                sessionStorage.setItem('sidebar-scroll-pos', nav.scrollTop);
            }
        });
    });

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    }
</script>