<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<button onclick="toggleSidebar()" class="lg:hidden fixed top-4 left-4 z-[60] bg-orange-700/95 border border-orange-600 p-2 rounded-2xl shadow-2xl shadow-orange-500/20 text-white">
    <span class="material-symbols-outlined">menu</span>
</button>

<div id="overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black/30 z-40 hidden backdrop-blur-sm transition-opacity"></div>

<aside id="sidebar" class="fixed left-0 top-0 h-screen w-72 bg-[radial-gradient(circle_at_top_left,_rgba(251,146,60,0.25),_transparent_35%),_linear-gradient(180deg,_#ffedd5_0%,_#f97316_40%,_#c2410c_100%)] border-r border-orange-700 shadow-[12px_0_60px_-30px_rgba(251,146,60,0.45)] flex flex-col overflow-hidden p-8 z-50 -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
    <div class="flex items-center gap-3 mb-12">
        <div class="w-12 h-12 rounded-3xl bg-gradient-to-br from-orange-400 via-orange-500 to-orange-600 flex items-center justify-center text-white shadow-2xl shadow-orange-500/30 border border-white/10">
            <span class="material-symbols-outlined text-2xl">restaurant</span>
        </div>
        <div>
            <h1 class="text-2xl font-black text-white leading-tight tracking-wide">Kantin Kita</h1>
            <p class="text-[9px] font-extrabold text-orange-100 uppercase tracking-[0.25em] mt-1">Gen-Z Kantin Vibes</p>
        </div>
    </div>

    <nav class="flex-1 space-y-2 overflow-y-auto pr-1 scrollbar-thin scrollbar-thumb-orange-400/70">
        <a href="dashboard_admin.php" class="flex items-center gap-4 px-4 py-4 rounded-[28px] font-bold transition-all <?= ($current_page == 'dashboard_admin.php') ? 'bg-[linear-gradient(90deg,_#fb923c,_#f97316,_#ea580c)] text-white shadow-2xl shadow-orange-400/30 scale-[1.01]' : 'text-orange-100 hover:bg-orange-900 hover:text-orange-50' ?>">
            <span class="material-symbols-outlined">grid_view</span>
            <span class="text-sm">Dashboard</span>
        </a>

        <div class="px-1">
            <div class="text-xs font-black text-orange-100 uppercase tracking-wider px-4 py-3">Data Master</div>
            <a href="manajemen_penjual.php" class="flex items-center gap-4 px-4 py-3 rounded-[22px] font-bold transition-all <?= ($current_page == 'manajemen_penjual.php') ? 'bg-orange-700/90 text-white' : 'text-orange-100 hover:bg-orange-900 hover:text-orange-50' ?>">
                <span class="material-symbols-outlined">store</span>
                <span class="text-sm">Penjual</span>
            </a>
            <a href="manajemen_kantin.php" class="flex items-center gap-4 px-4 py-3 rounded-[22px] font-bold transition-all <?= ($current_page == 'manajemen_kantin.php') ? 'bg-orange-700/90 text-white' : 'text-orange-100 hover:bg-orange-900 hover:text-orange-50' ?>">
                <span class="material-symbols-outlined">storefront</span>
                <span class="text-sm">Kantin</span>
            </a>
            <a href="manajemen_user.php" class="flex items-center gap-4 px-4 py-3 rounded-[22px] font-bold transition-all <?= ($current_page == 'manajemen_user.php') ? 'bg-orange-700/90 text-white' : 'text-orange-100 hover:bg-orange-900 hover:text-orange-50' ?>">
                <span class="material-symbols-outlined">group</span>
                <span class="text-sm">Pembeli</span>
            </a>
        </div>

        <div class="px-1">
            <div class="text-xs font-black text-orange-100 uppercase tracking-wider px-4 py-3">Manajemen</div>
            <a href="manajemen_menu.php" class="flex items-center gap-4 px-4 py-3 rounded-[22px] font-bold transition-all <?= ($current_page == 'manajemen_menu.php') ? 'bg-orange-700/90 text-white' : 'text-orange-100 hover:bg-orange-900 hover:text-orange-50' ?>">
                <span class="material-symbols-outlined">restaurant_menu</span>
                <span class="text-sm">Menu</span>
            </a>
        </div>

        <div class="px-1">
            <div class="text-xs font-black text-orange-100 uppercase tracking-wider px-4 py-3">Laporan</div>
            <a href="laporan_transaksi.php" class="flex items-center gap-4 px-4 py-3 rounded-[22px] font-bold transition-all text-orange-100 hover:bg-orange-900 hover:text-orange-50">
                <span class="material-symbols-outlined">receipt_long</span>
                <span class="text-sm">Laporan Transaksi</span>
            </a>
            <a href="laporan_penjualan.php" class="flex items-center gap-4 px-4 py-3 rounded-[22px] font-bold transition-all text-orange-100 hover:bg-orange-900 hover:text-orange-50">
                <span class="material-symbols-outlined">bar_chart</span>
                <span class="text-sm">Laporan Penjualan</span>
            </a>
            <a href="laporan_kantin.php" class="flex items-center gap-4 px-4 py-3 rounded-[22px] font-bold transition-all text-orange-100 hover:bg-orange-900 hover:text-orange-50">
                <span class="material-symbols-outlined">domain</span>
                <span class="text-sm">Laporan Kantin</span>
            </a>
            <a href="laporan_pembeli.php" class="flex items-center gap-4 px-4 py-3 rounded-[22px] font-bold transition-all text-orange-100 hover:bg-orange-900 hover:text-orange-50">
                <span class="material-symbols-outlined">person</span>
                <span class="text-sm">Laporan Pembeli</span>
            </a>
        </div>
    </nav>

    <div class="mt-auto pt-6 border-t border-orange-200 mb-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-orange-700 to-orange-500 flex items-center justify-center text-white text-xs font-bold uppercase shrink-0">AD</div>
        <div class="overflow-hidden">
            <p class="text-xs font-black text-orange-950 leading-none truncate">Admin Kantin</p>
            <p class="text-[9px] text-orange-600 font-bold uppercase mt-1">Super Admin</p>
        </div>
    </div>

   <a href="../auth/logout.php" class="flex items-center gap-4 px-4 py-3 bg-orange-100/90 text-orange-900 hover:bg-orange-200 rounded-2xl transition-all shadow-sm border border-orange-100">
    <span class="material-symbols-outlined">logout</span>
    <span class="text-sm font-bold">Logout</span>
</a>
</aside>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    }
</script>