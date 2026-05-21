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
/* Gen-Z Buyer Sidebar Styles */
@media (min-width:1024px){
    body{padding-left:18rem!important;padding-bottom:2rem!important;}
    body>header.fixed{left:18rem!important;right:0!important;width:auto!important;max-width:none!important;}
}
.kk-buyer-sidebar{font-family:'Be Vietnam Pro',system-ui,sans-serif;}
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
.kk-sidebar-scroll::-webkit-scrollbar{width:6px;}
.kk-sidebar-scroll::-webkit-scrollbar-thumb{background:#fbbf24;border-radius:999px;}

@media (max-width:1023px){
    body{padding-left:0!important;padding-bottom:2rem!important;}
    body>header{padding-left:4.75rem!important;min-height:4.25rem;}
    .kk-menu-toggle{display:flex!important;position:fixed;left:1rem;top:.72rem;z-index:90;width:2.9rem;height:2.9rem;border-radius:1.1rem;background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;align-items:center;justify-content:center;box-shadow:0 16px 32px rgba(249,115,22,.28);border:none;}
    .kk-buyer-sidebar{display:flex!important;transform:translateX(-105%);transition:transform .3s cubic-bezier(0.4, 0, 0.2, 1);width:min(18rem,86vw);}
    .kk-buyer-sidebar.open{transform:translateX(0);}
    .kk-sidebar-overlay{display:block;position:fixed;inset:0;background:rgba(249,115,22,.18);backdrop-filter:blur(6px);z-index:65;opacity:0;pointer-events:none;transition:opacity .25s;}
    .kk-sidebar-overlay.open{opacity:1;pointer-events:auto;}
    .kk-mobile-nav{display:none!important;}
    .kk-sidebar-scroll{max-height:none!important;}
}
@media (max-width:420px){
    body>header{padding-left:4.35rem!important;}
    .kk-menu-toggle{left:.75rem;width:2.75rem;height:2.75rem;border-radius:1rem;}
    .kk-buyer-sidebar{width:88vw;}
}

/* Mobile Bottom Nav - Gen Z Style */
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
    <div class="px-3 pb-5 border-b-2 border-orange-100">
        <div class="flex items-center gap-3">
            <a href="dashboard.php" class="w-12 h-12 rounded-2xl bg-gradient-to-br from-orange-500 to-orange-600 text-white flex items-center justify-center shadow-xl shadow-orange-200/50 hover:scale-105 transition-all">
                <span class="material-symbols-outlined">local_dining</span>
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

    <nav class="py-5 space-y-2">
        <?php foreach ($buyerNav as [$key, $href, $icon, $label]):
            $active = ($current_page === $key) || ($navBase === $href);
        ?>
        <a href="<?= $href ?>" class="kk-nav-link <?= $active ? 'active' : '' ?>">
            <span class="material-symbols-outlined"><?= $icon ?></span>
            <span><?= $label ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <div class="flex-1 min-h-0 border-t-2 border-orange-100 pt-4">
        <div class="flex items-center justify-between px-2 mb-2">
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Kantin</p>
            <span class="text-[10px] font-black text-orange-600 bg-orange-50 px-2 py-1 rounded-full"><?= count($sidebarKantin) ?></span>
        </div>
        <div class="kk-sidebar-scroll overflow-y-auto pr-1 space-y-1" style="max-height:calc(100vh - 29rem);">
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

    <div class="rounded-3xl bg-gradient-to-br from-orange-50 to-orange-100 border-2 border-orange-200 p-4 shadow-lg shadow-orange-200/20">
        <p class="text-xs font-black text-gray-700"><?= t('buyer.butuh_bantuan') ?></p>
        <p class="text-[11px] text-gray-500 mt-1 leading-relaxed"><?= t('help.help_desc') ?></p>
        <a href="bantuan.php" class="mt-3 inline-flex items-center gap-1 text-xs font-black text-orange-600 hover:text-orange-700">
            <?= t('buyer.pusat_bantuan') ?> <span class="material-symbols-outlined text-sm">arrow_forward</span>
        </a>
    </div>
</aside>

<!-- Mobile Bottom Navigation - Gen Z Style -->
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
