<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once(__DIR__ . '/../config/config.php');

$current_page = basename($_SERVER['PHP_SELF']);
$user_display = trim($_SESSION['username'] ?? '');

$id_kantin = $_SESSION['id_kantin'] ?? 0;
$id_user = $_SESSION['id_user'] ?? 0;

if (empty($user_display) && $id_user) {
    $q_user = mysqli_query($koneksi, "SELECT username FROM users WHERE id_user = $id_user LIMIT 1");
    $user_row = mysqli_fetch_assoc($q_user);
    if ($user_row && !empty($user_row['username'])) {
        $user_display = $user_row['username'];
        $_SESSION['username'] = $user_display;
    }
}

if (empty($user_display)) {
    $user_display = 'kantin_user';
}

// HITUNG PESANAN MASUK
$q_notif = mysqli_query($koneksi, "
    SELECT COUNT(*) as total
    FROM pesanan
    WHERE id_kantin = '$id_kantin'
    AND status IN ('Pending', 'Diproses', 'Siap Diambil')
");

$data_notif = mysqli_fetch_assoc($q_notif);
$total_notif = $data_notif['total'] ?? 0;

// HITUNG UNREAD CHAT
$q_chat_unread = mysqli_query($koneksi, "
    SELECT COUNT(*) as total
    FROM chat_messages cm
    JOIN chat_conversations cc ON cm.id_conversation = cc.id_conversation
    WHERE cc.id_seller = '$id_user'
    AND cm.id_sender != '$id_user'
    AND cm.is_read = 0
");

$data_chat_unread = mysqli_fetch_assoc($q_chat_unread);
$chat_unread = $data_chat_unread['total'] ?? 0;
?>

<!-- BUTTON MOBILE -->
<button onclick="toggleSidebar()" class="lg:hidden fixed top-4 left-4 z-[60] bg-gradient-to-br from-orange-500 to-orange-600 border border-orange-500 p-2 rounded-2xl shadow-2xl shadow-orange-400/30 text-white">
    <span class="material-symbols-outlined">menu</span>
</button>

<!-- OVERLAY -->
<div id="overlay"
     onclick="toggleSidebar()"
     class="fixed inset-0 bg-orange-900/20 z-40 hidden lg:hidden backdrop-blur-sm">
</div>

<!-- SIDEBAR -->
<aside id="sidebar"
class="h-screen w-72 fixed left-0 top-0 flex flex-col overflow-hidden bg-[linear-gradient(180deg,_#fb923c_0%,_#f97316_40%,_#ea580c_100%)] border-r border-orange-700 z-50 -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out shadow-2xl shadow-orange-500/20">

    <div class="flex flex-col h-full p-8 gap-2">

        <!-- HEADER -->
        <div class="mb-10 flex justify-between items-start">
            <div class="flex items-center gap-3">
                <a href="dashboard_penjual.php" class="w-12 h-12 rounded-2xl flex items-center justify-center bg-white/10 hover:bg-white/20 hover:scale-110 transition-all flex-shrink-0 shadow-lg border border-white/20">
                    <img src="/kantin/uploads/logo/logo_kantin_kita.png" alt="Kantin Kita Logo" class="w-full h-full object-contain rounded-2xl shadow-lg">
                </a>
                <div>
                    <h1 class="text-2xl font-black text-white leading-none"
                        style="font-family: 'Plus Jakarta Sans', sans-serif;">
                        Kantin Kita
                    </h1>

                    <p class="text-[11px] font-bold text-white/70 tracking-[0.2em] uppercase mt-2">
                        <?= t('seller.seller_center') ?>
                    </p>
                </div>
            </div>

            <button onclick="toggleSidebar()" class="lg:hidden text-white/70 hover:text-white transition mt-1">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <!-- PROFILE -->
        <div class="mb-8 p-6 rounded-[2rem] bg-gradient-to-br from-white/20 to-white/10 flex items-center gap-4 shadow-lg shadow-black/10 border border-white/30">

            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-white to-white/80 text-orange-600 flex items-center justify-center font-black text-xl shadow-lg shadow-black/20">
                <?= strtoupper(substr($user_display, 0, 1)); ?>
            </div>

            <div class="overflow-hidden">

                <p class="text-[10px] font-bold text-white/60 uppercase tracking-wider">
                    <?= t('seller.welcome') ?>
                </p>

                <p class="text-md font-extrabold text-white truncate"
                   style="font-family: 'Plus Jakarta Sans', sans-serif;">
                    <?= $user_display; ?>
                </p>

            </div>

        </div>

        <!-- MENU -->
        <nav class="flex-1 space-y-3 overflow-y-auto pr-1 scrollbar-thin scrollbar-thumb-orange-400/70">

            <!-- DASHBOARD -->
            <a href="dashboard_penjual.php"
               class="flex items-center gap-4 px-6 py-4 rounded-[28px] text-sm transition-all <?= ($current_page == 'dashboard_penjual.php') ? 'bg-white/20 text-white font-bold shadow-2xl shadow-black/20 border border-white/30' : 'text-white/80 hover:bg-white/10 hover:text-white' ?>">

                <span class="material-symbols-outlined">grid_view</span>

                <?= t('nav.dashboard') ?>
            </a>

            <!-- MENU -->
            <a href="kelola_menu_penjual.php"
               class="flex items-center gap-4 px-6 py-4 rounded-[28px] text-sm transition-all <?= ($current_page == 'kelola_menu_penjual.php') ? 'bg-white/20 text-white font-bold shadow-2xl shadow-black/20 border border-white/30' : 'text-white/80 hover:bg-white/10 hover:text-white' ?>">

                <span class="material-symbols-outlined">restaurant_menu</span>

                <?= t('nav.manage_menu') ?>
            </a>

            <!-- PESANAN -->
            <a href="pesanan_masuk.php"
               class="flex items-center justify-between px-6 py-4 rounded-[28px] text-sm transition-all <?= ($current_page == 'pesanan_masuk.php') ? 'bg-white/20 text-white font-bold shadow-2xl shadow-black/20 border border-white/30' : 'text-white/80 hover:bg-white/10 hover:text-white' ?>">

                <div class="flex items-center gap-4">
                    <span class="material-symbols-outlined">pending_actions</span>

                    <span><?= t('nav.incoming_orders') ?></span>
                </div>

                <?php if($total_notif > 0): ?>

                    <div class="min-w-[24px] h-6 px-2 rounded-full bg-red-500 text-white text-[11px] font-black flex items-center justify-center shadow-lg animate-pulse">

                        <?= $total_notif ?>

                    </div>

                <?php endif; ?>


            </a>

            <!-- CHAT -->
            <a href="chat_penjual.php"
               class="flex items-center justify-between px-6 py-4 rounded-[28px] text-sm transition-all <?= ($current_page == 'chat_penjual.php') ? 'bg-white/20 text-white font-bold shadow-2xl shadow-black/20 border border-white/30' : 'text-white/80 hover:bg-white/10 hover:text-white' ?>">

                <div class="flex items-center gap-4">
                    <span class="material-symbols-outlined">chat</span>

                    <span><?= t('nav.chat') ?></span>
                </div>

                <?php if($chat_unread > 0): ?>

                    <div class="min-w-[24px] h-6 px-2 rounded-full bg-red-500 text-white text-[11px] font-black flex items-center justify-center shadow-lg animate-pulse">

                        <?= $chat_unread > 99 ? '99+' : $chat_unread ?>

                    </div>

                <?php endif; ?>


            </a>

            <!-- RIWAYAT -->
            <a href="riwayat_penjual.php"
               class="flex items-center gap-4 px-6 py-4 rounded-[28px] text-sm transition-all <?= ($current_page == 'riwayat_penjual.php') ? 'bg-white/20 text-white font-bold shadow-2xl shadow-black/20 border border-white/30' : 'text-white/80 hover:bg-white/10 hover:text-white' ?>">

                <span class="material-symbols-outlined">history</span>

                <?= t('nav.history') ?>
            </a>

            <!-- EDIT PROFIL -->
            <a href="edit_profil.php"
               class="flex items-center gap-4 px-6 py-4 rounded-[28px] text-sm transition-all <?= ($current_page == 'edit_profil.php') ? 'bg-white/20 text-white font-bold shadow-2xl shadow-black/20 border border-white/30' : 'text-white/80 hover:bg-white/10 hover:text-white' ?>">

                   <span class="material-symbols-outlined">edit_square</span>

               <?= t('nav.profile') ?>
            </a>

        </nav>

        <!-- LOGOUT -->
        <div class="mt-auto pt-6 border-t border-white/20">

            <a href="../auth/logout.php"
               onclick="return confirm('<?= t('msg.confirm_logout') ?>')"
               class="flex items-center gap-4 px-6 py-4 bg-white/20 text-white hover:bg-white/30 rounded-2xl font-bold text-sm transition-all shadow-sm border border-white/30">

                <span class="material-symbols-outlined">logout</span>

                <?= t('nav.logout') ?>
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