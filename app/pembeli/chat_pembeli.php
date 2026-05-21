<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include(__DIR__ . '/../../config/config.php');
include(__DIR__ . '/../../includes/pembeli_helpers.php');
include(__DIR__ . '/../../includes/language_helper.php');
kk_ensure_buyer_schema($koneksi);

if (!isset($_SESSION['id_user']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'pembeli') {
    header("Location: ../auth/login.php");
    exit();
}

$id_user = (int)$_SESSION['id_user'];
$current_page = 'chat';

// Ambil semua kantin untuk taburan chat
$q_kantin = mysqli_query($koneksi, "
    SELECT k.id_kantin, k.nama_kantin, k.logo,
           COALESCE((SELECT COUNT(*) FROM menu m WHERE m.id_kantin = k.id_kantin), 0) AS total_menu
    FROM kantin k
    ORDER BY k.nama_kantin ASC
");
$allKantin = [];
while ($k = mysqli_fetch_assoc($q_kantin)) $allKantin[] = $k;

// Ambil conversations dengan last message
$q_conv = mysqli_query($koneksi, "
    SELECT c.*, u.username AS seller_name, k.nama_kantin AS kantin_name, k.logo AS kantin_logo,
           (SELECT COUNT(*) FROM chat_messages cm WHERE cm.id_conversation = c.id_conversation AND cm.id_sender != $id_user AND cm.is_read = 0) AS unread_count
    FROM chat_conversations c
    JOIN users u ON c.id_seller = u.id_user
    JOIN kantin k ON c.id_kantin = k.id_kantin
    WHERE c.id_buyer = $id_user
    ORDER BY COALESCE(c.last_message_at, c.created_at) DESC
");
$conversations = [];
while ($c = mysqli_fetch_assoc($q_conv)) $conversations[] = $c;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chat - Kantin Kita</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@600;700;800;900&family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<style>
* { font-family: 'Be Vietnam Pro', sans-serif; scrollbar-width: thin; scrollbar-color: #fed7aa transparent; }
::-webkit-scrollbar { width: 4px; }
::-webkit-scrollbar-thumb { background: #fed7aa; border-radius: 999px; }
html, body { height: 100%; overflow: hidden; }
body {
    background: #fff;
    background-image:
        radial-gradient(circle at 20% 80%, rgba(249,115,22,0.04) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(139,92,246,0.03) 0%, transparent 50%);
}
.headline { font-family: 'Plus Jakarta Sans', sans-serif; }
.material-symbols-outlined { font-variation-settings: 'FILL' 1,'wght' 500,'GRAD' 0,'opsz' 24; }
.gradient-text {
    background: linear-gradient(135deg, #f97316, #ea580c);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.chat-bubble-sent {
    background: linear-gradient(135deg, #f97316, #ea580c);
    color: white;
    border-radius: 18px 18px 4px 18px;
}
.chat-bubble-received {
    background: #f1f5f9;
    color: #1e293b;
    border-radius: 18px 18px 18px 4px;
}
.unread-badge {
    min-width: 20px;
    height: 20px;
    background: #f97316;
    color: white;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 6px;
    animation: pulse 2s infinite;
}
@keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.1); } }
.conversation-item {
    transition: all 0.2s;
    cursor: pointer;
}
.conversation-item:hover { background: #fff7ed; }
.conversation-item.active {
    background: #fff1ee;
    border-left: 3px solid #f97316;
}
.online-dot {
    width: 12px; height: 12px;
    background: #22c55e;
    border: 2px solid white;
    border-radius: 50%;
    position: absolute;
    bottom: 0; right: 0;
}
</style>
</head>
<body class="bg-white">

<!-- WhatsApp-style Layout -->
<div class="flex h-screen">

    <!-- ============ LEFT PANEL: Chat List ============ -->
    <div id="leftPanel" class="w-full lg:w-[400px] flex flex-col border-r border-gray-100 h-full">

        <!-- Header -->
        <div class="px-5 py-4 bg-white border-b border-gray-100 flex items-center justify-between sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center shadow-lg shadow-orange-200">
                    <span class="material-symbols-outlined text-white text-lg">chat</span>
                </div>
                <div>
                    <h1 class="font-black text-gray-900 headline text-lg">Pesan</h1>
                    <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-widest">Chat Penjual</p>
                </div>
            </div>
            <a href="dashboard.php" class="w-10 h-10 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 hover:bg-orange-50 hover:text-orange-500 transition-all">
                <i class="fa-solid fa-house text-sm"></i>
            </a>
        </div>

        <!-- Search -->
        <div class="px-4 py-3 bg-white border-b border-gray-50">
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-300 text-sm"></i>
                <input type="text" id="searchInput" placeholder="Cari kantin..."
                       class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-100 rounded-2xl text-sm outline-none focus:border-orange-300 focus:ring-2 focus:ring-orange-100 transition-all">
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex bg-white border-b border-gray-100 sticky top-[73px] z-10">
            <button onclick="showTab('recent')" id="tabRecent" class="flex-1 py-3 text-xs font-bold text-orange-600 border-b-2 border-orange-500 flex items-center justify-center gap-1.5">
                <i class="fa-solid fa-clock text-[10px]"></i> Terbaru
            </button>
            <button onclick="showTab('all')" id="tabAll" class="flex-1 py-3 text-xs font-bold text-gray-400 border-b-2 border-transparent flex items-center justify-center gap-1.5">
                <i class="fa-solid fa-store text-[10px]"></i> Semua Kantin
            </button>
        </div>

        <!-- Conversation / Kantin List -->
        <div id="chatList" class="flex-1 overflow-y-auto">

            <!-- Recent Chats Tab -->
            <div id="recentTab">
                <?php if (empty($conversations)): ?>
                <div class="flex flex-col items-center justify-center h-64 text-center p-6">
                    <div class="w-20 h-20 rounded-full bg-orange-50 flex items-center justify-center mb-4">
                        <i class="fa-solid fa-comment-dots text-3xl text-orange-300"></i>
                    </div>
                    <h3 class="font-black text-gray-600 text-sm">Belum ada percakapan</h3>
                    <p class="text-xs text-gray-400 mt-1">Mulai chat dengan penjual di tab Semua Kantin</p>
                </div>
                <?php else: ?>
                <?php foreach ($conversations as $conv): ?>
                <?php
                    $displayName = $conv['kantin_name'] ?? $conv['seller_name'] ?? 'Kantin';
                    $initials = strtoupper(substr($displayName, 0, 1));
                    $lastTime = $conv['last_message_at'] ? format_chat_time($conv['last_message_at']) : '';
                    $unread = (int)$conv['unread_count'];
                    $logoUrl = kk_upload_url($conv['kantin_logo'] ?? '', 'logo');
                ?>
                <div class="conversation-item px-5 py-4 border-b border-gray-50 flex items-center gap-3"
                     data-conv-id="<?= (int)$conv['id_conversation'] ?>"
                     onclick="openChat(<?= $conv['id_conversation'] ?>, '<?= htmlspecialchars(addslashes($displayName)) ?>', '<?= htmlspecialchars(addslashes($conv['seller_name'] ?? 'Penjual')) ?>')"
                     data-name="<?= htmlspecialchars(strtolower($displayName . ' ' . ($conv['seller_name'] ?? ''))) ?>">
                    <!-- Avatar -->
                    <div class="relative flex-shrink-0">
                        <div class="w-14 h-14 rounded-full overflow-hidden bg-gradient-to-br from-orange-100 to-orange-200 flex items-center justify-center text-orange-600 font-black text-lg shadow-md">
                            <?php if (!empty($conv['kantin_logo'])): ?>
                            <img src="<?= $logoUrl ?>" class="w-full h-full object-cover" onerror="this.style.display='none';this.parentElement.textContent='<?= $initials ?>'">
                            <?php else: ?>
                            <?= $initials ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($unread > 0): ?>
                        <div class="online-dot" style="background:#f97316;border-color:#fff7ed"></div>
                        <?php endif; ?>
                    </div>
                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <h4 class="font-bold text-gray-900 text-sm truncate"><?= htmlspecialchars($displayName) ?></h4>
                            <span class="text-[10px] <?= $unread > 0 ? 'text-orange-500 font-bold' : 'text-gray-400' ?>"><?= $lastTime ?></span>
                        </div>
                        <div class="flex items-center justify-between mt-0.5">
                            <p class="text-xs text-gray-500 truncate <?= $unread > 0 ? 'font-semibold text-gray-700' : '' ?>">
                                <?= $conv['last_message'] ? htmlspecialchars($conv['last_message']) : '<span class="text-gray-300 italic">Belum ada pesan</span>' ?>
                            </p>
                            <?php if ($unread > 0): ?>
                            <span class="unread-badge"><?= $unread ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- All Kantin Tab -->
            <div id="allTab" class="hidden">
                <?php if (empty($allKantin)): ?>
                <div class="flex flex-col items-center justify-center h-64 text-center p-6">
                    <div class="w-20 h-20 rounded-full bg-orange-50 flex items-center justify-center mb-4">
                        <i class="fa-solid fa-store text-3xl text-orange-300"></i>
                    </div>
                    <h3 class="font-black text-gray-600 text-sm">Belum ada kantin</h3>
                </div>
                <?php else: ?>
                <?php foreach ($allKantin as $k): ?>
                <?php
                    $logoUrl = kk_upload_url($k['logo'] ?? '', 'logo');
                    $initials = strtoupper(substr($k['nama_kantin'] ?? 'K', 0, 1));
                ?>
                <div class="conversation-item px-5 py-4 border-b border-gray-50 flex items-center gap-3"
                     onclick="startNewChat(<?= $k['id_kantin'] ?>, '<?= htmlspecialchars(addslashes($k['nama_kantin'])) ?>')"
                     data-name="<?= htmlspecialchars(strtolower($k['nama_kantin'] ?? '')) ?>">
                    <!-- Avatar -->
                    <div class="w-14 h-14 rounded-full overflow-hidden flex-shrink-0 bg-gradient-to-br from-orange-100 to-orange-200 shadow-md">
                        <?php if (!empty($k['logo'])): ?>
                        <img src="<?= $logoUrl ?>" class="w-full h-full object-cover" onerror="this.parentElement.innerHTML='<div class=\\'w-full h-full flex items-center justify-center text-orange-600 font-black text-lg\\'><?= $initials ?></div>'">
                        <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center text-orange-600 font-black text-lg"><?= $initials ?></div>
                        <?php endif; ?>
                    </div>
                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <h4 class="font-bold text-gray-900 text-sm truncate"><?= htmlspecialchars($k['nama_kantin']) ?></h4>
                            <span class="text-[10px] text-gray-400 font-bold"><?= (int)($k['total_menu'] ?? 0) ?> menu</span>
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5">Ketuk untuk chat kantin</p>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ============ RIGHT PANEL: Chat Detail (Desktop) ============ -->
    <div id="rightPanel" class="hidden lg:flex flex-1 flex-col bg-white">

        <!-- Empty State -->
        <div id="emptyChat" class="flex-1 flex flex-col items-center justify-center text-center p-8">
            <div class="w-32 h-32 rounded-full bg-gradient-to-br from-orange-100 to-orange-200 flex items-center justify-center mb-6">
                <i class="fa-solid fa-comments text-5xl text-orange-300"></i>
            </div>
            <h2 class="headline font-black text-2xl text-gray-800 mb-2">Kantin Kita Chat</h2>
            <p class="text-sm text-gray-400 max-w-xs">Pilih percakapan di sebelah kiri atau mulai chat dengan penjual</p>
        </div>

        <!-- Chat Content (hidden initially) -->
        <div id="chatContent" class="hidden flex-1 flex flex-col">

            <!-- Chat Header -->
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-4 bg-gradient-to-r from-orange-50 to-white sticky top-0 z-10">
                <button onclick="closeChat()" class="lg:hidden w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500">
                    <i class="fa-solid fa-arrow-left"></i>
                </button>
                <div id="chatAvatar" class="w-12 h-12 rounded-full bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center text-white font-black text-lg flex-shrink-0">
                    ?
                </div>
                <div class="flex-1 min-w-0">
                    <h3 id="chatSellerName" class="font-bold text-gray-900 truncate">-</h3>
                    <p class="text-xs text-gray-400 flex items-center gap-1">
                        <i class="fa-solid fa-store text-orange-400 text-[10px]"></i>
                        <span id="chatKantinName">-</span>
                    </p>
                </div>
                <input type="hidden" id="currentConversationId" value="">
                <input type="hidden" id="currentKantinId" value="">
            </div>

            <!-- Messages -->
            <div id="messagesContainer" class="flex-1 overflow-y-auto p-6 space-y-3 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIGZpbGw9IiNmM2Y0ZjQiLz48L3N2Zz4=')]">
                <!-- Messages loaded here -->
            </div>

            <!-- Message Input -->
            <div class="px-6 py-4 border-t border-gray-100 bg-white sticky bottom-0">
                <form id="messageForm" class="flex items-end gap-3">
                    <div class="flex-1 relative">
                        <input type="text" id="messageInput" placeholder="Ketik pesan..."
                               class="w-full px-5 py-3.5 bg-gray-50 border-2 border-gray-100 rounded-3xl text-sm outline-none focus:border-orange-300 focus:ring-2 focus:ring-orange-100 transition-all">
                    </div>
                    <button type="submit" class="w-14 h-14 rounded-full bg-gradient-to-br from-orange-500 to-orange-600 text-white flex items-center justify-center shadow-lg shadow-orange-200 hover:shadow-xl hover:scale-105 active:scale-95 transition-all">
                        <i class="fa-solid fa-paper-plane text-sm"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// ============ State ============
let currentConvId = null;
let pollingInterval = null;
let activeTab = 'recent';

// ============ Init ============
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('messageForm').addEventListener('submit', (e) => {
        e.preventDefault();
        sendMessage();
    });

    document.getElementById('searchInput').addEventListener('input', (e) => {
        filterList(e.target.value);
    });

    // Desktop: auto-open first conversation if exists
    if (window.innerWidth >= 1024 && <?= !empty($conversations) ? 'true' : 'false' ?>) {
        const first = <?= json_encode($conversations[0] ?? null) ?>;
        if (first) openChat(first.id_conversation, first.kantin_name || first.seller_name, first.seller_name);
    }
});

// ============ Tabs ============
function showTab(tab) {
    activeTab = tab;
    document.getElementById('recentTab').classList.toggle('hidden', tab !== 'recent');
    document.getElementById('allTab').classList.toggle('hidden', tab !== 'all');
    document.getElementById('tabRecent').className = 'flex-1 py-3 text-xs font-bold border-b-2 flex items-center justify-center gap-1.5 transition-all ' + (tab === 'recent' ? 'text-orange-600 border-orange-500' : 'text-gray-400 border-transparent');
    document.getElementById('tabAll').className = 'flex-1 py-3 text-xs font-bold border-b-2 flex items-center justify-center gap-1.5 transition-all ' + (tab === 'all' ? 'text-orange-600 border-orange-500' : 'text-gray-400 border-transparent');
}

// ============ Filter ============
function filterList(query) {
    query = query.toLowerCase().trim();
    document.querySelectorAll('.conversation-item').forEach(el => {
        const name = (el.dataset.name || '').toLowerCase();
        el.style.display = name.includes(query) ? '' : 'none';
    });
}

// ============ Open Existing Chat ============
async function openChat(convId, displayName, sellerName = 'Penjual') {
    currentConvId = convId;

    // Update UI
    document.getElementById('emptyChat').classList.add('hidden');
    document.getElementById('chatContent').classList.remove('hidden');
    document.getElementById('chatSellerName').textContent = displayName || 'Kantin';
    document.getElementById('chatKantinName').textContent = sellerName || 'Penjual';
    document.getElementById('chatAvatar').textContent = (displayName || 'K').charAt(0).toUpperCase();
    document.getElementById('currentConversationId').value = convId;

    // Highlight in list
    document.querySelectorAll('.conversation-item').forEach(el => el.classList.remove('active'));
    const activeItem = document.querySelector(`.conversation-item[data-conv-id="${convId}"]`);
    if (activeItem) activeItem.classList.add('active');

    // Mobile: hide list
    if (window.innerWidth < 1024) {
        document.getElementById('leftPanel').classList.add('hidden');
        document.getElementById('rightPanel').classList.remove('hidden');
        document.getElementById('rightPanel').classList.add('flex');
    }

    // Load messages
    await loadMessages(convId);

    // Mark read
    await markAsRead(convId);

    // Start polling
    if (pollingInterval) clearInterval(pollingInterval);
    pollingInterval = setInterval(() => loadMessages(convId), 5000);

    document.getElementById('messageInput').focus();
}

// ============ Start New Chat ============
async function startNewChat(kantinId, kantinName) {
    try {
        const formData = new FormData();
        formData.append('action', 'start_conversation');
        formData.append('id_kantin', kantinId);

        const resp = await fetch('../penjual/chat_handler.php', { method: 'POST', body: formData });
        const data = await resp.json();

        if (data.success) {
            openChat(data.id_conversation, kantinName, 'Penjual');
        } else {
            alert('Gagal memulai percakapan');
        }
    } catch (err) {
        console.error(err);
    }
}

// ============ Load Messages ============
async function loadMessages(convId) {
    try {
        const resp = await fetch(`../penjual/chat_handler.php?action=get_messages&id_conversation=${convId}`);
        const data = await resp.json();
        if (data.success) {
            renderMessages(data.messages);
        }
    } catch (err) {
        console.error(err);
    }
}

function renderMessages(messages) {
    const container = document.getElementById('messagesContainer');
    if (!messages || messages.length === 0) {
        container.innerHTML = `
            <div class="flex flex-col items-center justify-center h-full text-center">
                <div class="w-16 h-16 rounded-full bg-orange-50 flex items-center justify-center mb-3">
                    <i class="fa-solid fa-comment-dots text-2xl text-orange-300"></i>
                </div>
                <p class="text-sm text-gray-400 font-semibold">Belum ada pesan</p>
                <p class="text-xs text-gray-300 mt-1">Kirim pesan pertama!</p>
            </div>`;
        return;
    }

    let html = '';
    let lastDate = null;

    messages.forEach(msg => {
        const d = new Date(msg.created_at);
        const dateStr = d.toDateString();

        // Date separator
        if (dateStr !== lastDate) {
            const today = new Date();
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            let dateLabel;
            if (dateStr === today.toDateString()) dateLabel = 'Hari ini';
            else if (dateStr === yesterday.toDateString()) dateLabel = 'Kemarin';
            else dateLabel = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });

            html += `<div class="flex justify-center my-4"><span class="bg-gray-100 text-gray-400 text-[10px] font-bold px-4 py-1.5 rounded-full">${dateLabel}</span></div>`;
            lastDate = dateStr;
        }

        const isMine = parseInt(msg.id_sender) === <?= $id_user ?>;
        const time = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

        html += `
        <div class="flex ${isMine ? 'justify-end' : 'justify-start'}">
            <div class="max-w-[80%] ${isMine ? 'chat-bubble-sent' : 'chat-bubble-received'} px-4 py-2.5 ${isMine ? 'text-right' : ''}">
                <p class="text-sm leading-relaxed">${escapeHtml(msg.message)}</p>
                <p class="text-[10px] ${isMine ? 'text-orange-200' : 'text-gray-400'} mt-1 flex items-center ${isMine ? 'justify-end' : ''} gap-1">
                    ${time}
                    ${isMine ? (parseInt(msg.is_read) ? '<i class="fa-solid fa-check-double text-[9px]"></i>' : '<i class="fa-solid fa-check text-[9px]"></i>') : ''}
                </p>
            </div>
        </div>`;
    });

    container.innerHTML = html;
    container.scrollTop = container.scrollHeight;
}

// ============ Send Message ============
async function sendMessage() {
    const input = document.getElementById('messageInput');
    const message = input.value.trim();
    if (!message || !currentConvId) return;

    try {
        const formData = new FormData();
        formData.append('action', 'send_message');
        formData.append('id_conversation', currentConvId);
        formData.append('message', message);

        const resp = await fetch('../penjual/chat_handler.php', { method: 'POST', body: formData });
        const data = await resp.json();

        if (data.success) {
            input.value = '';
            await loadMessages(currentConvId);
        }
    } catch (err) {
        console.error(err);
    }
}

// ============ Mark Read ============
async function markAsRead(convId) {
    try {
        const formData = new FormData();
        formData.append('action', 'mark_read');
        formData.append('id_conversation', convId);
        await fetch('../penjual/chat_handler.php', { method: 'POST', body: formData });
    } catch (err) {}
}

// ============ Close Chat ============
function closeChat() {
    document.getElementById('leftPanel').classList.remove('hidden');
    document.getElementById('rightPanel').classList.add('hidden');
    document.getElementById('rightPanel').classList.remove('flex');
    document.getElementById('chatContent').classList.add('hidden');
    document.getElementById('emptyChat').classList.remove('hidden');
    currentConvId = null;
    if (pollingInterval) { clearInterval(pollingInterval); pollingInterval = null; }
}

// ============ Utilities ============
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatTimeAgo(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    const now = new Date();
    const diffMs = now - d;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Baru';
    if (diffMins < 60) return diffMins + 'm';
    if (diffHours < 24) return diffHours + 'j';
    if (diffDays === 1) return 'Kemarin';
    if (diffDays < 7) return diffDays + 'h';
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
}
</script>

<?php include(__DIR__ . '/../../includes/navbar.php'); ?>
</body>
</html>
