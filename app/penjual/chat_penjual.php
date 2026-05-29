<?php
// SESSION + CONFIG
if (session_status() === PHP_SESSION_NONE) session_start();
include(__DIR__ . '/../../config/config.php');

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'penjual') {
    header("Location: ../auth/login.php"); exit();
}

$id_user = (int)$_SESSION['id_user'];
$id_kantin = (int)($_SESSION['id_kantin'] ?? 0);
$user_display = $_SESSION['username'] ?? 'Penjual';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= t('chat.title') ?> — Kantin Kita</title>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<script>
tailwind.config = { theme: { extend: { colors: { primary: '#f97316' } } } }
</script>

<style>
* { font-family: 'Plus Jakarta Sans', sans-serif; }
.chat-container { height: calc(100vh - 120px); }
.conversation-item { transition: all 0.2s; }
.conversation-item:hover, .conversation-item.active { background: #fff0ee; }
.conversation-item.active { border-left: 4px solid #f97316; }
.message-bubble { max-width: 75%; word-wrap: break-word; }
.message-sent {
    background: #dcf8c6;
    color: #111827;
    border-radius: 18px 18px 4px 18px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.08);
}

.message-received {
    background: white;
    color: #111827;
    border-radius: 18px 18px 18px 4px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 2px rgba(0,0,0,0.06);
}
.unread-dot { width: 10px; height: 10px; background: #ef4444; border-radius: 50%; animation: pulse 2s infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
.scrollbar-thin::-webkit-scrollbar { width: 6px; }
.scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
.scrollbar-thin::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
.time-label { font-size: 11px; color: #9ca3af; }
</style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-orange-50 min-h-screen">

<!-- SIDEBAR -->
<?php include(__DIR__ . '/../../includes/sidebar_penjual.php'); ?>

<!-- Toggle button mobile -->
<button id="sidebarToggle" class="lg:hidden fixed top-4 left-4 z-40 p-2.5 bg-white rounded-2xl shadow-lg text-gray-700">
    <i class="fa-solid fa-bars text-lg"></i>
</button>

<main class="lg:ml-72 p-4 lg:p-6 transition-all">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl lg:text-3xl font-black text-gray-900 flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-orange-100 flex items-center justify-center">
                <i class="fa-solid fa-comments text-orange-500 text-xl"></i>
            </div>
            <?= t('chat.title') ?>
        </h1>
        <p class="text-gray-400 mt-1"><?= t('chat.conversations') ?></p>
    </div>

    <!-- Chat Interface -->
    <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden chat-container flex">

        <!-- Conversations List -->
        <div id="conversationsList" class="w-full lg:w-80 border-r border-gray-100 flex flex-col">
            <div class="p-4 border-b border-gray-100">
                <div class="relative">
                    <input type="text" id="searchBuyer" placeholder="<?= t('general.search') ?> pembeli..."
                           class="w-full pl-10 pr-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:border-orange-300 focus:ring-2 focus:ring-orange-100 outline-none text-sm transition">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>

            <div id="conversationsListContent" class="flex-1 overflow-y-auto scrollbar-thin">
                <!-- Empty state -->
                <div id="emptyConversations" class="flex flex-col items-center justify-center h-full text-gray-400 p-6">
                    <div class="w-20 h-20 rounded-full bg-orange-50 flex items-center justify-center mb-4">
                        <i class="fa-solid fa-inbox text-3xl text-orange-300"></i>
                    </div>
                    <p class="font-semibold text-center"><?= t('chat.no_conversations') ?></p>
                    <p class="text-sm text-center mt-1 opacity-75"><?= t('msg.start_conversation') ?></p>
                </div>
            </div>
        </div>

        <!-- Chat Area (hidden on mobile by default) -->
        <div id="chatArea" class="hidden lg:flex flex-1 flex-col">
            <!-- No conversation selected -->
            <div id="noConversationSelected" class="flex-1 flex flex-col items-center justify-center text-gray-400">
                <div class="w-24 h-24 rounded-full bg-orange-50 flex items-center justify-center mb-4">
                    <i class="fa-solid fa-hand-point-left text-4xl text-orange-300"></i>
                </div>
                <p class="font-bold text-lg"><?= t('chat.select_conversation') ?></p>
                <p class="text-sm mt-1 opacity-75"><?= t('chat.select_sidebar') ?></p>
            </div>

            <!-- Chat content (hidden until conversation selected) -->
            <div id="chatContent" class="hidden flex-1 flex flex-col">
                <!-- Chat Header -->
                <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-orange-50 to-white">
                    <div class="flex items-center gap-3">
                        <div id="chatAvatar" class="w-12 h-12 rounded-full bg-orange-500 text-white flex items-center justify-center font-bold text-lg">
                            ?
                        </div>
                        <div>
                            <h3 id="chatBuyerName" class="font-bold text-gray-900">-</h3>
                        </div>
                    </div>
                    <button onclick="closeChat()" class="lg:hidden p-2 text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-arrow-left"></i>
                    </button>
                </div>

                <!-- Messages -->
                <div id="messagesContainer"
                class="flex-1 overflow-y-auto p-4 space-y-3 scrollbar-thin bg-[#efeae2]"
                 >
                    <!-- Messages will be loaded here -->
                </div>

                <!-- Message Input -->
                <div class="p-4 border-t border-gray-100 bg-gray-50">
                    <form id="messageForm" class="flex gap-3">
                        <input type="hidden" id="currentConversationId" value="">
                        <input type="text" id="messageInput" placeholder="<?= t('chat.type_placeholder') ?>"
                               class="flex-1 px-4 py-3 rounded-xl border border-gray-200 focus:border-orange-300 focus:ring-2 focus:ring-orange-100 outline-none text-sm">
                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold rounded-xl transition-all shadow-lg shadow-orange-200 hover:shadow-xl active:scale-95">
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// ========== STATE ==========
let currentConversationId = null;
let pollingInterval = null;
let lastFetchTime = null;
let conversationCache = {};

// ========== INIT ==========
document.addEventListener('DOMContentLoaded', () => {
    loadConversations();

    // Start polling
    pollingInterval = setInterval(loadConversations, 5000);

    // Message form submit
    document.getElementById('messageForm').addEventListener('submit', (e) => {
        e.preventDefault();
        sendMessage();
    });
});

// ========== LOAD CONVERSATIONS ==========
async function loadConversations() {
    try {
        const resp = await fetch('chat_handler.php?action=get_conversations');
        const data = await resp.json();

        if (data.success) {
            renderConversations(data.conversations);
        }
    } catch (err) {
        console.error('Failed to load conversations:', err);
    }
}

function renderConversations(conversations) {
    const container = document.getElementById('conversationsListContent');
    const emptyState = document.getElementById('emptyConversations');

    if (!conversations || conversations.length === 0) {
        container.innerHTML = '';
        container.appendChild(emptyState.cloneNode(true));
        emptyState.classList.add('hidden');
        return;
    }

    emptyState.classList.add('hidden');
    conversationCache = {};
    conversations.forEach(conv => {
        conversationCache[conv.id_conversation] = conv;
    });

    const html = conversations.map(conv => {
        const initials = (conv.buyer_name || '<?= t('chat.unknown') ?>').charAt(0).toUpperCase();
        const timeAgo = formatTimeAgo(conv.last_message_at);
        const unread = conv.unread_count > 0;
        const noMessagesText = '<?= t('msg.no_messages') ?>';
        const newBadgeText = '<?= t('chat.badge_new') ?>';

        return `
        <div class="conversation-item p-4 cursor-pointer border-b border-gray-50 ${currentConversationId == conv.id_conversation ? 'active' : ''}"
             onclick="selectConversation(event, ${conv.id_conversation})">
            <div class="flex items-center gap-3">
                <div class="relative">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 text-white flex items-center justify-center font-bold text-lg shadow-md">
                        ${initials}
                    </div>
                    ${unread ? '<div class="unread-dot absolute -top-1 -right-1"></div>' : ''}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <h4 class="font-bold text-gray-900 truncate">${escapeHtml(conv.buyer_name || '<?= t('chat.unknown') ?>')}</h4>
                        <span class="text-xs text-gray-400 flex-shrink-0">${timeAgo}</span>
                    </div>
                    <p class="text-sm text-gray-500 truncate">${escapeHtml(conv.last_message || noMessagesText)}</p>
                    ${conv.unread_count > 0 ? `<span class="inline-block mt-1 px-2 py-0.5 bg-orange-500 text-white text-xs font-bold rounded-full">${conv.unread_count} ${newBadgeText}</span>` : ''}
                </div>
            </div>
        </div>
        `;
    }).join('');

    container.innerHTML = html || emptyState.outerHTML;
}

// ========== SELECT CONVERSATION ==========
async function selectConversation(evt, convId) {
    currentConversationId = convId;
    const conv = conversationCache[convId] || {};
    const buyerName = conv.buyer_name || '<?= t('chat.buyer_default') ?>';
    document.getElementById('currentConversationId').value = convId;

    // Show chat area
document.getElementById('chatArea').classList.remove('hidden');
document.getElementById('chatArea').classList.add('flex');

// Khusus mobile baru sembunyikan list
if (window.innerWidth < 1024) {
    document.getElementById('conversationsList').classList.add('hidden');
}

    // Update header
    document.getElementById('chatBuyerName').textContent = buyerName;
    document.getElementById('chatAvatar').textContent = buyerName.charAt(0).toUpperCase();

    // Hide no-conversation state, show content
    document.getElementById('noConversationSelected').classList.add('hidden');
    document.getElementById('chatContent').classList.remove('hidden');

    // Load messages
    await loadMessages(convId);

    // Mark as read
    await markAsRead(convId);

    // Update active state in list
    document.querySelectorAll('.conversation-item').forEach(el => {
        el.classList.remove('active');
    });
    evt?.currentTarget?.classList.add('active');

    // Focus message input
    document.getElementById('messageInput').focus();
}

async function loadMessages(convId) {
    try {
        const resp = await fetch(`chat_handler.php?action=get_messages&id_conversation=${convId}`);
        const data = await resp.json();

        if (data.success) {
            renderMessages(data.messages);
            lastFetchTime = new Date().toISOString();
        }
    } catch (err) {
        console.error('Failed to load messages:', err);
    }
}

function renderMessages(messages) {
    const container = document.getElementById('messagesContainer');

    if (!messages || messages.length === 0) {
        container.innerHTML = `
            <div class="flex items-center justify-center h-full">
                <div class="text-center text-gray-400">
                    <i class="fa-solid fa-comment-dots text-4xl mb-2"></i>
                    <p class="font-semibold">${escapeHtml('<?= t('msg.no_messages') ?>')}</p>
                </div>
            </div>
        `;
        return;
    }

    let html = '';
    let lastDate = null;

    messages.forEach(msg => {
        const msgDate = new Date(msg.created_at).toDateString();
        if (msgDate !== lastDate) {
            html += `<div class="time-label text-center py-2">${formatDateHeader(msg.created_at)}</div>`;
            lastDate = msgDate;
        }

        const isMine = parseInt(msg.id_sender) === <?= (int)$id_user ?>;
        const time = formatTime(msg.created_at);

        html += `
        <div class="flex ${isMine ? 'justify-end' : 'justify-start'}">
            <div class="message-bubble ${isMine ? 'message-sent' : 'message-received'} px-4 py-2.5 ${isMine ? 'text-right' : ''}">
                <p class="text-sm leading-relaxed">${escapeHtml(msg.message)}</p>
                <p class="text-xs ${isMine ? 'text-orange-200' : 'text-gray-400'} mt-1">${time}</p>
            </div>
        </div>
        `;
    });

    container.innerHTML = html;
    container.scrollTop = container.scrollHeight;
}

// ========== SEND MESSAGE ==========
async function sendMessage() {
    const input = document.getElementById('messageInput');
    const message = input.value.trim();

    if (!message || !currentConversationId) return;

    try {
        const formData = new FormData();
        formData.append('action', 'send_message');
        formData.append('id_conversation', currentConversationId);
        formData.append('message', message);

        const resp = await fetch('chat_handler.php', {
            method: 'POST',
            body: formData
        });

        const data = await resp.json();

        if (data.success) {
            input.value = '';
            await loadMessages(currentConversationId);
            await loadConversations(); // Update list
        }
    } catch (err) {
        console.error('Failed to send message:', err);
    }
}

// ========== MARK AS READ ==========
async function markAsRead(convId) {
    try {
        const formData = new FormData();
        formData.append('action', 'mark_read');
        formData.append('id_conversation', convId);

        await fetch('chat_handler.php', {
            method: 'POST',
            body: formData
        });
    } catch (err) {
        console.error('Failed to mark as read:', err);
    }
}

// ========== CLOSE CHAT (Mobile) ==========
function closeChat() {
    document.getElementById('chatArea').classList.add('hidden');
    document.getElementById('chatArea').classList.remove('flex');

    document.getElementById('conversationsList').classList.remove('hidden');

    currentConversationId = null;
}

// ========== UTILITIES ==========
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatTime(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
}

function formatTimeAgo(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    const now = new Date();
    const diffMs = now - d;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    const justNow = '<?= t('time.just_now') ?>';
    const yesterday = '<?= t('time.yesterday') ?>';

    if (diffMins < 1) return justNow;
    if (diffMins < 60) return diffMins + 'm';
    if (diffHours < 24) return diffHours + 'j';
    if (diffDays === 1) return yesterday;
    if (diffDays < 7) return diffDays + 'd';
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
}

function formatDateHeader(dateStr) {
    const d = new Date(dateStr);
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);

    const todayText = '<?= t('time.today') ?>';
    const yesterdayText = '<?= t('time.yesterday') ?>';

    if (d.toDateString() === today.toDateString()) return todayText;
    if (d.toDateString() === yesterday.toDateString()) return yesterdayText;
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
}

// Sidebar toggle
const toggle = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
toggle?.addEventListener('click', () => {
    sidebar?.classList.toggle('-translate-x-full');
    overlay?.classList.toggle('hidden');
});
</script>
</body>
</html>
