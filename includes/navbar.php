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
.kk-buyer-sidebar{
    font-family:'Be Vietnam Pro',system-ui,sans-serif;
    display:flex !important;
    flex-direction:column !important;
    height:100% !important;
    background: linear-gradient(180deg, #fff9f5 0%, #fffaf8 50%, #fef8f5 100%) !important;
    border-right: 2px solid #fed7aa !important;
}
.kk-menu-toggle{display:none;}
.kk-sidebar-overlay{display:none;}

.kk-nav-link{
    display:flex;
    align-items:center;
    gap:.9rem;
    border-radius:18px;
    padding:1rem 1rem;
    font-size:.9rem;
    font-weight:800;
    color:#7c2d12;
    text-decoration:none;
    transition:all .3s cubic-bezier(0.4, 0, 0.2, 1);
    background:transparent;
    border:2px solid transparent;
    position:relative;
    overflow:hidden;
}

.kk-nav-link:hover{
    background:linear-gradient(135deg, #fff2e7, #ffe8d7);
    color:#c2410c;
    transform:translateX(6px) translateY(-2px);
    box-shadow: 0 8px 20px rgba(249,115,22,.12);
    border-color:#fbbf24;
}

.kk-nav-link.active{
    background:linear-gradient(135deg, #fb923c, #f97316, #ea580c);
    color:white;
    box-shadow:0 12px 30px rgba(249,115,22,.25);
    border-color:transparent;
    position:relative;
}

.kk-nav-link .material-symbols-outlined{
    font-size:24px;
    font-variation-settings:'FILL' 1,'wght' 600,'GRAD' 0,'opsz' 24;
    transition: transform .3s;
}

.kk-nav-link:hover .material-symbols-outlined{
    transform: scale(1.15) rotate(5deg);
}

.kk-kantin-link{
    display:flex;
    align-items:center;
    gap:.7rem;
    padding:.8rem;
    border-radius:16px;
    text-decoration:none;
    color:#422006;
    transition:.3s;
    background:linear-gradient(135deg, #fff5e6, #fff0db);
    border-left:4px solid #f97316;
}

.kk-kantin-link:hover{
    transform:translateX(6px) translateY(-1px);
    box-shadow: 0 6px 16px rgba(249,115,22,.18);
    background:linear-gradient(135deg, #ffe8cc, #ffd99d);
}

.kk-sidebar-scroll{scrollbar-width:thin;scrollbar-color:#fbbf24 transparent;}
.kk-nav-scroll{scrollbar-width:thin;scrollbar-color:#fbbf24 transparent;max-height:50%;flex-shrink:0;overflow-y:auto;}
.kk-kantin-section{flex:1;min-height:0;display:flex;flex-direction:column;overflow:hidden;}

@media (max-width:1023px){
    body{padding-left:0!important;padding-bottom:2rem!important;}
    body>header{padding-left:4.75rem!important;min-height:4.25rem;}
    .kk-menu-toggle{display:flex!important;position:fixed;left:1rem;top:.72rem;z-index:90;width:2.9rem;height:2.9rem;border-radius:1.1rem;background:linear-gradient(135deg,#f97316,#ea580c);color:#fff;align-items:center;justify-content:center;box-shadow:0 16px 32px rgba(249,115,22,.35);border:none;}
    .kk-buyer-sidebar{display:flex!important;transform:translateX(-105%);transition:transform .3s cubic-bezier(0.4, 0, 0.2, 1);width:min(18rem,86vw);}
    .kk-buyer-sidebar.open{transform:translateX(0);}
    .kk-sidebar-overlay{display:block;position:fixed;inset:0;background:rgba(249,115,22,.15);backdrop-filter:blur(8px);z-index:65;opacity:0;pointer-events:none;transition:opacity .25s;}
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
    background: linear-gradient(180deg, rgba(255,255,255,0.98) 0%, rgba(255,250,245,0.98) 100%);
    backdrop-filter: blur(20px);
    border-top: 3px solid #f97316;
}

<?php if ($current_page !== 'home' && $navBase !== 'dashboard.php'): ?>
body {
    background:
        radial-gradient(circle at 12% 10%, rgba(251, 146, 60, .18), transparent 28%),
        radial-gradient(circle at 88% 8%, rgba(78, 205, 196, .14), transparent 25%),
        radial-gradient(circle at 72% 88%, rgba(236, 72, 153, .10), transparent 30%),
        linear-gradient(135deg, #fff7ed 0%, #fff1f2 42%, #ecfeff 100%) !important;
}

body::after {
    content: "";
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: -1;
    background-image:
        linear-gradient(rgba(249,115,22,.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(20,184,166,.04) 1px, transparent 1px);
    background-size: 32px 32px;
}

main [class*="bg-white"],
#leftPanel [class*="bg-white"],
#rightPanel [class*="bg-white"],
.receipt-print,
.chat-bubble-received {
    background: linear-gradient(135deg, rgba(255,255,255,.96), rgba(255,247,237,.94) 52%, rgba(236,254,255,.88)) !important;
    border-color: rgba(251,146,60,.24) !important;
    box-shadow: 0 16px 36px rgba(249,115,22,.08) !important;
}

main header[class*="bg-white"],
header[class*="bg-white"],
#leftPanel > div[class*="bg-white"],
#rightPanel > div[class*="bg-white"] {
    background: linear-gradient(135deg, rgba(255,247,237,.96), rgba(255,241,242,.95), rgba(240,253,250,.92)) !important;
    border-color: rgba(251,146,60,.22) !important;
}

input,
select,
textarea {
    background: linear-gradient(135deg, #fff7ed, #ffffff) !important;
    border-color: rgba(251,146,60,.24) !important;
}

table thead,
.conversation-item:hover,
.conversation-item.active {
    background: linear-gradient(135deg, #fff7ed, #ffedd5, #ecfeff) !important;
}
<?php endif; ?>

.kk-mobile-nav a {
    transition: all 0.25s ease;
    border-radius: 14px;
    position: relative;
    color: #999;
}

.kk-mobile-nav a:active {
    transform: scale(0.92);
}

.kk-mobile-nav a.active span.material-symbols-outlined {
    color: #f97316 !important;
}

.kk-mobile-nav a.active {
    color: #f97316 !important;
    font-weight: 800;
    background: linear-gradient(135deg, #fff2e7, #ffe8d7);
}

.kk-mobile-nav a:active span.material-symbols-outlined{
    animation: bounce 0.5s;
}

@keyframes bounce {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.2); }
}
</style>

<button type="button" class="kk-menu-toggle" onclick="kkToggleBuyerSidebar(true)" aria-label="Menu">
    <span class="material-symbols-outlined">menu</span>
</button>

<div id="kk-sidebar-overlay" class="kk-sidebar-overlay" onclick="kkToggleBuyerSidebar(false)"></div>

<aside id="kk-buyer-sidebar" class="kk-buyer-sidebar fixed left-0 top-0 bottom-0 z-[70] w-72 bg-white/98 backdrop-blur-xl px-4 py-5 hidden lg:flex flex-col shadow-2xl shadow-orange-200/20">
    <div class="px-3 pb-3 border-b-2 border-orange-100">
        <div class="flex items-center gap-3">
            <a href="dashboard.php" class="w-12 h-12 rounded-2xl flex items-center justify-center hover:scale-110 transition-all flex-shrink-0">
                <img src="/kantin/uploads/logo/logo_kantin_kita.png" alt="Kantin Kita Logo" class="w-full h-full object-contain rounded-2xl shadow-lg">
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
            <span class="text-[10px] font-black text-white bg-gradient-to-r from-orange-500 to-orange-600 px-2 py-1 rounded-full shadow-lg shadow-orange-200"><?= count($sidebarKantin) ?></span>
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
                <div class="rounded-2xl bg-orange-50 p-3 text-[11px] text-gray-400 font-bold text-center border-2 border-orange-200"><?= t('buyer.belum_ada_kantin') ?></div>
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
