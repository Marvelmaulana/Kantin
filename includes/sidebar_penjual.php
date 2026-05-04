<?php
$current_page = basename($_SERVER['PHP_SELF']);
$user_display = $_SESSION['username'] ?? 'kantin_user';
?>

<button onclick="toggleSidebar()" class="lg:hidden fixed top-4 left-4 z-[60] bg-white border border-orange-100 p-2 rounded-xl shadow-lg text-primary">
    <span class="material-symbols-outlined">menu</span>
</button>

<div id="overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black/20 z-40 hidden lg:hidden backdrop-blur-sm"></div>

<aside id="sidebar" class="h-screen w-72 fixed left-0 top-0 flex flex-col bg-white border-r border-orange-50 z-50 -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
    <div class="flex flex-col h-full p-8 gap-2">
        <div class="mb-10 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-black text-primary leading-none" style="font-family: 'Plus Jakarta Sans', sans-serif;">Kantin Kita</h1>
                <p class="text-[11px] font-bold text-stone-400 tracking-[0.2em] uppercase mt-2">Seller Center</p>
            </div>
            <button onclick="toggleSidebar()" class="lg:hidden text-stone-400">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div class="mb-8 p-6 rounded-[2rem] bg-[#fff0ee] flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center font-bold text-xl shadow-md">
                <?= strtoupper(substr($user_display, 0, 1)); ?>
            </div>
            <div class="overflow-hidden">
                <p class="text-[10px] font-bold text-stone-400 uppercase tracking-wider">Selamat Datang</p>
                <p class="text-md font-extrabold text-stone-800 truncate" style="font-family: 'Plus Jakarta Sans', sans-serif;"><?= $user_display; ?></p>
            </div>
        </div>

        <nav class="flex-1 space-y-3">
            <a href="dashboard_penjual.php" class="flex items-center gap-4 px-6 py-4 rounded-2xl text-sm transition-all <?= ($current_page == 'dashboard_penjual.php') ? 'bg-soft-orange text-primary font-bold shadow-sm' : 'text-stone-500 hover:bg-orange-50' ?>">
                <span class="material-symbols-outlined">grid_view</span> Dashboard
            </a>
            <a href="kelola_menu_penjual.php" class="flex items-center gap-4 px-6 py-4 rounded-2xl text-sm transition-all <?= ($current_page == 'kelola_menu_penjual.php') ? 'bg-soft-orange text-primary font-bold shadow-sm' : 'text-stone-500 hover:bg-orange-50' ?>">
                <span class="material-symbols-outlined">restaurant_menu</span> Kelola Menu
            </a>
            <a href="pesanan_masuk.php" class="flex items-center gap-4 px-6 py-4 rounded-2xl text-sm transition-all <?= ($current_page == 'pesanan_masuk.php') ? 'bg-soft-orange text-primary font-bold shadow-sm' : 'text-stone-500 hover:bg-orange-50' ?>">
                <span class="material-symbols-outlined">pending_actions</span> Pesanan Masuk
            </a>
        </nav>

        <div class="mt-auto pt-6 border-t border-orange-50">
            <a href="../auth/logout.php" onclick="return confirm('Yakin ingin keluar?')" class="flex items-center gap-4 px-6 py-4 text-stone-400 hover:text-red-600 hover:bg-red-50 rounded-2xl font-bold text-sm transition-all">
                <span class="material-symbols-outlined">logout</span> Keluar
            </a>
        </div>
    </div>
</aside>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    }
</script>