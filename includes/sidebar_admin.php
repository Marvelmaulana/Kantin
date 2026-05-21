<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<button onclick="toggleSidebar()" class="lg:hidden fixed top-4 left-4 z-[60] bg-white border border-slate-100 p-2 rounded-xl shadow-lg text-primary-orange">
    <span class="material-symbols-outlined">menu</span>
</button>

<div id="overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black/20 z-40 hidden backdrop-blur-sm transition-opacity"></div>

<aside id="sidebar" class="fixed left-0 top-0 h-screen w-72 bg-white border-r border-slate-100 flex flex-col p-8 z-50 -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
    <div class="flex items-center gap-3 mb-12">
        <div class="w-10 h-10 rounded-xl bg-primary-orange flex items-center justify-center text-white shadow-lg">
            <span class="material-symbols-outlined font-bold">restaurant</span>
        </div>
        <div>
            <h1 class="text-xl font-extrabold text-[#003049] leading-none">Kantin Kita</h1>
            <p class="text-[9px] font-bold text-slate-400 tracking-[0.2em] uppercase mt-1">Management Portal</p>
        </div>
    </div>

    <nav class="flex-1 space-y-2">
        <a href="dashboard_admin.php" class="flex items-center gap-4 px-4 py-4 rounded-2xl font-bold transition-all <?= ($current_page == 'dashboard_admin.php') ? 'bg-[#F0F7FF] text-accent-blue shadow-sm' : 'text-slate-400 hover:bg-slate-50' ?>">
            <span class="material-symbols-outlined">grid_view</span>
            <span class="text-sm">Dashboard</span>
        </a>
        <a href="manajemen_user.php" class="flex items-center gap-4 px-4 py-4 rounded-2xl font-bold transition-all <?= ($current_page == 'manajemen_user.php') ? 'bg-[#F0F7FF] text-accent-blue shadow-sm' : 'text-slate-400 hover:bg-slate-50' ?>">
            <span class="material-symbols-outlined">group</span>
            <span class="text-sm">Management User</span>
        </a>
        <a href="manajemen_penjual.php" class="flex items-center gap-4 px-4 py-4 rounded-2xl font-bold transition-all <?= ($current_page == 'manajemen_penjual.php') ? 'bg-[#F0F7FF] text-accent-blue shadow-sm' : 'text-slate-400 hover:bg-slate-50' ?>">
            <span class="material-symbols-outlined">store</span>
            <span class="text-sm">Management Penjual</span>
        </a>
        <a href="manajemen_menu.php" class="flex items-center gap-4 px-4 py-4 rounded-2xl font-bold transition-all <?= ($current_page == 'manajemen_menu.php') ? 'bg-[#F0F7FF] text-accent-blue shadow-sm' : 'text-slate-400 hover:bg-slate-50' ?>">
            <span class="material-symbols-outlined">restaurant_menu</span>
            <span class="text-sm">Management Menu</span>
        </a>
    </nav>

    <div class="mt-auto pt-6 border-t border-slate-100 mb-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-[#003049] flex items-center justify-center text-white text-xs font-bold uppercase shrink-0">AD</div>
        <div class="overflow-hidden">
            <p class="text-xs font-black text-slate-800 leading-none truncate">Admin Kantin</p>
            <p class="text-[9px] text-slate-400 font-bold uppercase mt-1">Super Admin</p>
        </div>
    </div>

   <a href="../auth/logout.php" class="flex items-center gap-4 px-4 py-3 text-red-500 hover:bg-red-50 rounded-2xl transition-all">
    <span class="material-symbols-outlined">logout</span>
    <span class="text-sm font-bold">Logout</span>
</a>
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