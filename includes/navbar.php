<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($koneksi)) {
    @include_once(__DIR__ . '/../config/config.php');
}
if (!function_exists('kk_upload_url')) {
    @include_once(__DIR__ . '/pembeli_helpers.php');
}

$current_page = $current_page ?? '';
$buyerNav = [
    ['home', 'dashboard.php', 'home', t('nav.home')],
    ['menu', 'semua_menu.php', 'restaurant_menu', t('nav.menu')],
    ['orders', 'pesanan.php', 'receipt_long', t('nav.orders')],
    ['history', 'riwayat_pembeli.php', 'history', t('nav.order_history')],
    ['chat', 'chat_pembeli.php', 'chat', t('nav.chat')],
    ['cart', 'keranjang.php', 'shopping_bag', t('nav.cart')],
    ['profile', 'profil.php', 'person', t('nav.account')],
];

$navBase = basename($_SERVER['PHP_SELF'] ?? '');
$sidebarKantin = [];
if (isset($koneksi) && $koneksi) {
    $q_sidebar_kantin = @mysqli_query($koneksi, "
        SELECT k.id_kantin, k.nama_kantin, k.logo,
               COUNT(CASE WHEN COALESCE(m.status,'Tersedia') <> 'Habis' THEN 1 END) AS total_menu
        FROM kantin k
        LEFT JOIN menu m ON k.id_kantin = m.id_kantin
        GROUP BY k.id_kantin
        ORDER BY k.nama_kantin ASC
    ");
    if ($q_sidebar_kantin) {
        while ($kantin = mysqli_fetch_assoc($q_sidebar_kantin)) {
            $sidebarKantin[] = $kantin;
        }
    }
}
?>
<style>
@media (min-width:1024px){
    body{padding-left:18rem!important;padding-bottom:2rem!important;}
    body>header{left:18rem!important;right:0!important;width:auto!important;max-width:none!important;}
}
.kk-buyer-sidebar{font-family:'Be Vietnam Pro',system-ui,sans-serif;display:flex !important;flex-direction:column !important;height:100% !important;}
.kk-menu-toggle{display:none;}
.kk-sidebar-overlay{display:none;}

.kk-nav-link{
    display:flex;
    align-items:center;
    gap:.9rem;
    border-radius:22px;
    padding:1rem 1rem;
    font-size:.9rem;
    font-weight:800;
    color:#7c2d12;
    text-decoration:none;
    transition:all .28s cubic-bezier(0.4, 0, 0.2, 1);
    background:transparent;
    border:2px solid transparent;
}
.kk-nav-link:hover{
    background:linear-gradient(135deg, #fff2e7, #ffe8d7);
    color:#c2410c;
    transform:translateX(4px);
    border-color:#fbbf24;
}
.kk-nav-link.active{
    background:linear-gradient(135deg, #fb923c, #f97316, #ea580c);
    color:white;
    box-shadow:0 20px 45px rgba(249,115,22,.22);
    border-color:transparent;
}

.kk-nav-link .material-symbols-outlined{font-size:21px;font-variation-settings:'FILL' 1,'wght' 600,'GRAD' 0,'opsz' 24;}
.kk-kantin-link{display:flex;align-items:center;gap:.65rem;padding:.7rem;border-radius:18px;text-decoration:none;color:#422006;transition:.25s;}
.kk-kantin-link:hover{background:linear-gradient(135deg, #fff4e6, #ffe9d5);color:#c2410c;transform:translateX(4px);border-radius:18px;}
.kk-sidebar-scroll{scrollbar-width:thin;scrollbar-color:#fbbf24 transparent;}
.kk-nav-scroll{scrollbar-width:thin;scrollbar-color:#fbbf24 transparent;max-height:50%;flex-shrink:0;overflow-y:auto;}
.kk-kantin-section{flex:1;min-height:0;display:flex;flex-direction:column;overflow:hidden;}

@media (max-width:1023px){
    body{padding-left:0!important;padding-bottom:2rem!important;}
    body>header{padding-left:4.75rem!important;min-height:4.25rem;}
    .kk-menu-toggle{display:flex!important;position:fixed;left:1rem;top:.72rem;z-index:90;width:2.9rem;height:2.9rem;border-radius:1.1rem;background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;align-items:center;justify-content:center;box-shadow:0 16px 32px rgba(249,115,22,.28);border:none;}
    .kk-buyer-sidebar{display:flex!important;transform:translateX(-105%);transition:transform .3s cubic-bezier(0.4, 0, 0.2, 1);width:min(18rem,86vw);}
    .kk-buyer-sidebar.open{transform:translateX(0);}
    .kk-sidebar-overlay{display:block;position:fixed;inset:0;background:rgba(249,115,22,.18);backdrop-filter:blur(6px);z-index:65;opacity:0;pointer-events:none;transition:opacity .25s;}
    .kk-sidebar-overlay.open{opacity:1;pointer-events:auto;}
    .kk-mobile-nav{display:none!important;}
    .kk-nav-scroll{max-height:none;}
}
@media (max-width:420px){
    body>header{padding-left:4.35rem!important;}
    .kk-menu-toggle{left:.75rem;width:2.75rem;height:2.75rem;border-radius:1rem;}
    .kk-buyer-sidebar{width:88vw;}
}

.kk-mobile-nav {
    background: rgba(255,255,255,0.98);
    backdrop-filter: blur(18px);
    border-top: 2px solid #fbbf24;
}
.kk-mobile-nav a {
    transition: all 0.18s ease;
}
.kk-mobile-nav a:active {
    transform: scale(0.95);
}
.kk-mobile-nav a.active span.material-symbols-outlined {
    color: #f97316 !important;
}
.kk-mobile-nav a.active {
    color: #f97316 !important;
    font-weight: 800;
}
</style>

<button type="button" class="kk-menu-toggle" onclick="kkToggleBuyerSidebar(true)" aria-label="Menu">
    <span class="material-symbols-outlined">menu</span>
</button>

<div id="kk-sidebar-overlay" class="kk-sidebar-overlay" onclick="kkToggleBuyerSidebar(false)"></div>

<aside id="kk-buyer-sidebar" class="kk-buyer-sidebar fixed left-0 top-0 bottom-0 z-[70] w-72 bg-white/98 backdrop-blur-xl border-r-2 border-orange-100 px-4 py-5 hidden lg:flex flex-col shadow-2xl shadow-orange-200/20">
    <div class="px-3 pb-3 border-b-2 border-orange-100">
        <div class="flex items-center gap-3">
            <a href="dashboard.php" class="w-12 h-12 rounded-2xl bg-gradient-to-br from-orange-500 to-orange-600 text-white flex items-center justify-center shadow-xl shadow-orange-200/50 hover:scale-105 transition-all flex-shrink-0">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Fork and knife for kantin/restaurant -->
                    <g fill="currentColor">
                        <path d="M6 2C5.45 2 5 2.45 5 3V13C5 13.55 5.45 14 6 14H5V20C5 20.55 5.45 21 6 21C6.55 21 7 20.55 7 20V14H6V3C6 2.45 6.45 2 6 2Z"/>
                        <path d="M11 4C10.45 4 10 4.45 10 5V15C10 15.55 10.45 16 11 16C11.55 16 12 15.55 12 15V5C12 4.45 11.55 4 11 4Z"/>
                        <path d="M11 16C10.45 16 10 16.45 10 17V20C10 20.55 10.45 21 11 21C11.55 21 12 20.55 12 20V17C12 16.45 11.55 16 11 16Z"/>
                        <path d="M16 3C15.45 3 15 3.45 15 4V9H15L16 7L17 9H17V4C17 3.45 16.55 3 16 3Z"/>
                        <path d="M14 10C13.45 10 13 10.45 13 11V20C13 20.55 13.45 21 14 21C14.55 21 15 20.55 15 20V11C15 10.45 14.55 10 14 10Z"/>
                    </g>
                </svg>
            </a>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Kantin Kita</p>
                <h2 class="text-lg font-black text-transparent bg-clip-text bg-gradient-to-r from-orange-600 to-orange-400 italic leading-none">Shop</h2>
            </div>
            <button type="button" class="ml-auto lg:hidden w-9 h-9 rounded-2xl bg-orange-50 text-orange-600 flex items-center justify-center hover:bg-orange-100 transition" onclick="kkToggleBuyerSidebar(false)">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>
    </div>

    <nav class="kk-nav-scroll space-y-1">
        <?php foreach ($buyerNav as [$key, $href, $icon, $label]):
            $active = ($current_page === $key) || ($navBase === $href);
        ?>
        <a href="<?= $href ?>" class="kk-nav-link <?= $active ? 'active' : '' ?>">
            <span class="material-symbols-outlined"><?= $icon ?></span>
            <span><?= $label ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <div class="kk-kantin-section border-t-2 border-orange-100 pt-3">
        <div class="flex items-center justify-between px-2 mb-2 flex-shrink-0">
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Kantin</p>
            <span class="text-[10px] font-black text-orange-600 bg-orange-50 px-2 py-1 rounded-full"><?= count($sidebarKantin) ?></span>
        </div>
        <div class="kk-sidebar-scroll overflow-y-auto pr-1 space-y-1 flex-1 min-h-0">
            <?php if (!empty($sidebarKantin)): ?>
                <?php foreach ($sidebarKantin as $kantin):
                    $logo = function_exists('kk_upload_url') ? kk_upload_url($kantin['logo'] ?? '', 'logo') : '../../public/assets/img/default-logo.svg';
                ?>
                <a href="kantin_detail.php?id=<?= (int)$kantin['id_kantin'] ?>" class="kk-kantin-link">
                    <img src="<?= $logo ?>" class="w-10 h-10 rounded-2xl object-cover bg-orange-50 border-2 border-white shadow-sm shrink-0" onerror="this.src='../../public/assets/img/default-logo.svg'">
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-black truncate"><?= htmlspecialchars($kantin['nama_kantin'] ?? 'Kantin') ?></p>
                        <p class="text-[10px] text-gray-400 font-bold"><?= (int)($kantin['total_menu'] ?? 0) ?> menu</p>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="rounded-2xl bg-orange-50 p-3 text-[11px] text-gray-400 font-bold text-center"><?= t('buyer.belum_ada_kantin') ?></div>
            <?php endif; ?>
        </div>
    </div>
</aside>

<nav class="kk-mobile-nav fixed bottom-0 left-0 right-0 z-[70] bg-white/98 backdrop-blur-xl border-t-2 border-orange-100 px-2 pt-2 pb-5 hidden grid-cols-5 gap-1">
    <?php foreach ($buyerNav as [$key, $href, $icon, $label]):
        $active = ($current_page === $key) || ($navBase === $href);
    ?>
    <a href="<?= $href ?>" class="flex flex-col items-center gap-1 rounded-2xl py-2 text-[10px] font-bold <?= $active ? 'text-orange-500' : 'text-gray-400' ?> hover:text-orange-500 transition-all">
        <span class="material-symbols-outlined text-[22px]" style="font-variation-settings:'FILL' <?= $active ? '1' : '0' ?>"><?= $icon ?></span>
        <?= $label ?>
    </a>
    <?php endforeach; ?>
</nav>

<script>
function kkToggleBuyerSidebar(open) {
    const sidebar = document.getElementById('kk-buyer-sidebar');
    const overlay = document.getElementById('kk-sidebar-overlay');
    if (!sidebar || !overlay) return;
    sidebar.classList.toggle('open', !!open);
    overlay.classList.toggle('open', !!open);
}
</script>
