<nav class="floating-nav">
    <a href="dashboard.php" class="flex flex-col items-center <?= ($current_page == 'home') ? 'text-[#b22204]' : 'text-stone-400' ?>">
        <span class="material-symbols-outlined" style='font-variation-settings: "FILL" <?= ($current_page == 'home') ? "1" : "0" ?>;'>home</span>
        <span class="text-[9px] font-bold">Home</span>
    </a>

    <a href="pesanan.php" class="flex flex-col items-center <?= ($current_page == 'orders') ? 'text-[#b22204]' : 'text-stone-400' ?>">
        <span class="material-symbols-outlined" style='font-variation-settings: "FILL" <?= ($current_page == 'orders') ? "1" : "0" ?>;'>receipt_long</span>
        <span class="text-[9px] font-bold uppercase">Orders</span>
    </a>

    <a href="riwayat_pembeli.php" class="flex flex-col items-center <?= ($current_page == 'history') ? 'text-[#b22204]' : 'text-stone-400' ?>">
        <span class="material-symbols-outlined" style='font-variation-settings: "FILL" <?= ($current_page == 'history') ? "1" : "0" ?>;'>history</span>
        <span class="text-[9px] font-bold uppercase">History</span>
    </a>

    <a href="profil.php" class="flex flex-col items-center <?= ($current_page == 'profile') ? 'text-[#b22204]' : 'text-stone-400' ?>">
        <span class="material-symbols-outlined" style='font-variation-settings: "FILL" <?= ($current_page == 'profile') ? "1" : "0" ?>;'>person</span>
        <span class="text-[9px] font-bold uppercase">Profile</span>
    </a> 
</nav>