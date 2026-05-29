<?php
/**
 * Chat Handler API - Handles all chat AJAX requests
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include(__DIR__ . '/../../config/config.php');

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['id_user'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$id_user = (int)$_SESSION['id_user'];
$role = $_SESSION['role'] ?? '';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$response = ['success' => false];

switch ($action) {
    case 'get_conversations':
        $response = getConversations($koneksi, $id_user, $role);
        break;

    case 'get_messages':
        $id_conversation = (int)($_GET['id_conversation'] ?? $_POST['id_conversation'] ?? 0);
        $last_fetch = $_GET['last_fetch'] ?? $_POST['last_fetch'] ?? '';
        $response = getMessages($koneksi, $id_conversation, $id_user, $role);
        break;

    case 'send_message':
        $id_conversation = (int)($_POST['id_conversation'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        $response = sendMessage($koneksi, $id_conversation, $id_user, $role, $message);
        break;

    case 'create_conversation':
        $id_buyer = (int)($_POST['id_buyer'] ?? 0);
        $id_order = (int)($_POST['id_order'] ?? 0);
        $id_kantin = (int)($_POST['id_kantin'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        $response = createConversation($koneksi, $id_user, $role, $id_buyer, $id_order, $id_kantin, $message);
        break;

    case 'start_conversation':
        // Buyer starts a new conversation with a kantin
        $id_kantin = (int)($_POST['id_kantin'] ?? 0);
        $response = startBuyerConversation($koneksi, $id_user, $id_kantin);
        break;

    case 'mark_read':
        $id_conversation = (int)($_POST['id_conversation'] ?? 0);
        $response = markRead($koneksi, $id_conversation, $id_user, $role);
        break;

    case 'get_unread_count':
        $response = getUnreadCount($koneksi, $id_user, $role);
        break;

    default:
        $response = ['success' => false, 'error' => 'Invalid action'];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;

/**
 * Get all conversations for current user
 */
function getConversations($koneksi, $id_user, $role) {
    if ($role === 'penjual') {
        $id_kantin = (int)($_SESSION['id_kantin'] ?? 0);
        $sql = "SELECT c.*,
                       u.username as buyer_name, u.email as buyer_email,
                       k.nama_kantin,
                       (SELECT COUNT(*) FROM chat_messages cm
                        WHERE cm.id_conversation = c.id_conversation
                        AND cm.id_sender != $id_user AND cm.is_read = 0) as unread_count
                FROM chat_conversations c
                JOIN users u ON c.id_buyer = u.id_user
                JOIN kantin k ON c.id_kantin = k.id_kantin
                WHERE c.id_seller = $id_user
                AND EXISTS (
                    SELECT 1 FROM chat_messages cmx
                    WHERE cmx.id_conversation = c.id_conversation
                )
                ORDER BY COALESCE(c.last_message_at, c.created_at) DESC, c.updated_at DESC";
    } else {
        $sql = "SELECT c.*,
                       u.username as seller_name,
                       (SELECT COUNT(*) FROM chat_messages cm
                        WHERE cm.id_conversation = c.id_conversation
                        AND cm.id_sender != $id_user AND cm.is_read = 0) as unread_count
                FROM chat_conversations c
                JOIN users u ON c.id_seller = u.id_user
                WHERE c.id_buyer = $id_user
                ORDER BY COALESCE(c.last_message_at, c.created_at) DESC, c.updated_at DESC";
    }

    $result = mysqli_query($koneksi, $sql);
    $conversations = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $conversations[] = $row;
        }
    }

    return ['success' => true, 'conversations' => $conversations];
}

/**
 * Get messages for a conversation
 */
function getMessages($koneksi, $id_conversation, $id_user, $role) {
    $id_conversation = (int)$id_conversation;

    // Verify user has access to this conversation
    if ($role === 'penjual') {
        $check = mysqli_query($koneksi, "SELECT id_conversation FROM chat_conversations
                                          WHERE id_conversation = $id_conversation AND id_seller = $id_user");
    } else {
        $check = mysqli_query($koneksi, "SELECT id_conversation FROM chat_conversations
                                          WHERE id_conversation = $id_conversation AND id_buyer = $id_user");
    }

    if (!$check || mysqli_num_rows($check) === 0) {
        return ['success' => false, 'error' => 'Access denied'];
    }

    $sql = "SELECT cm.*, u.username as sender_name
            FROM chat_messages cm
            JOIN users u ON cm.id_sender = u.id_user
            WHERE cm.id_conversation = $id_conversation
            ORDER BY cm.created_at ASC";

    $result = mysqli_query($koneksi, $sql);
    $messages = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $messages[] = $row;
        }
    }

    return ['success' => true, 'messages' => $messages];
}

/**
 * Send a message
 */
function sendMessage($koneksi, $id_conversation, $id_user, $role, $message) {
    $id_conversation = (int)$id_conversation;
    $message = mysqli_real_escape_string($koneksi, trim($message));

    if (!$message) {
        return ['success' => false, 'error' => 'Message is empty'];
    }

    // Verify access
    if ($role === 'penjual') {
        $check = mysqli_query($koneksi, "SELECT id_conversation, id_buyer FROM chat_conversations
                                          WHERE id_conversation = $id_conversation AND id_seller = $id_user");
    } else {
        $check = mysqli_query($koneksi, "SELECT id_conversation, id_seller FROM chat_conversations
                                          WHERE id_conversation = $id_conversation AND id_buyer = $id_user");
    }

    if (!$check || mysqli_num_rows($check) === 0) {
        return ['success' => false, 'error' => 'Access denied'];
    }

    // Insert message
    $sql = "INSERT INTO chat_messages (id_conversation, id_sender, message, created_at)
            VALUES ($id_conversation, $id_user, '$message', NOW())";

    if (!mysqli_query($koneksi, $sql)) {
        return ['success' => false, 'error' => 'Failed to send message'];
    }

    $id_message = mysqli_insert_id($koneksi);

    // Update conversation
    mysqli_query($koneksi, "UPDATE chat_conversations
                            SET last_message = '$message', last_message_at = NOW()
                            WHERE id_conversation = $id_conversation");

    // Get message details
    $msg_result = mysqli_query($koneksi, "SELECT cm.*, u.username as sender_name
                                          FROM chat_messages cm
                                          JOIN users u ON cm.id_sender = u.id_user
                                          WHERE cm.id_message = $id_message");

    $msg_data = mysqli_fetch_assoc($msg_result);

    return ['success' => true, 'message' => $msg_data];
}

/**
 * Create new conversation
 */
function createConversation($koneksi, $id_user, $role, $id_buyer, $id_order, $id_kantin, $message) {
    if ($role !== 'penjual') {
        return ['success' => false, 'error' => 'Only sellers can create conversations'];
    }

    $id_buyer = (int)$id_buyer;
    $id_order = (int)$id_order;
    $id_kantin = (int)($_SESSION['id_kantin'] ?? 0);
    $message = mysqli_real_escape_string($koneksi, trim($message));

    if (!$id_buyer) {
        return ['success' => false, 'error' => 'Buyer ID required'];
    }

    // Check if conversation already exists
    $check = mysqli_query($koneksi, "SELECT id_conversation FROM chat_conversations
                                     WHERE id_seller = $id_user AND id_buyer = $id_buyer AND id_kantin = $id_kantin");

    if ($check && mysqli_num_rows($check) > 0) {
        $existing = mysqli_fetch_assoc($check);
        $id_conversation = $existing['id_conversation'];
    } else {
        // Create new conversation
        $sql = "INSERT INTO chat_conversations (id_seller, id_buyer, id_kantin, id_order, created_at, updated_at)
                VALUES ($id_user, $id_buyer, $id_kantin, " . ($id_order ?: 'NULL') . ", NOW(), NOW())";

        if (!mysqli_query($koneksi, $sql)) {
            return ['success' => false, 'error' => 'Failed to create conversation'];
        }

        $id_conversation = mysqli_insert_id($koneksi);
    }

    // Send initial message if provided
    if ($message) {
        $msg_sql = "INSERT INTO chat_messages (id_conversation, id_sender, message, created_at)
                    VALUES ($id_conversation, $id_user, '$message', NOW())";
        mysqli_query($koneksi, $msg_sql);

        mysqli_query($koneksi, "UPDATE chat_conversations
                                SET last_message = '$message', last_message_at = NOW()
                                WHERE id_conversation = $id_conversation");
    }

    return ['success' => true, 'id_conversation' => $id_conversation];
}

/**
 * Start a new conversation from buyer's side
 */
function startBuyerConversation($koneksi, $id_user, $id_kantin) {
    $id_kantin = (int)$id_kantin;

    if (!$id_kantin) {
        return ['success' => false, 'error' => 'Kantin ID required'];
    }

    // Get seller id for this kantin from users table
    $seller_query = mysqli_query($koneksi, "SELECT id_user FROM users WHERE id_kantin = $id_kantin AND role = 'penjual' LIMIT 1");
    if (!$seller_query || mysqli_num_rows($seller_query) === 0) {
        return ['success' => false, 'error' => 'Seller not found for this kantin'];
    }
    $seller_data = mysqli_fetch_assoc($seller_query);
    $id_seller = (int)$seller_data['id_user'];

    if (!$id_seller) {
        return ['success' => false, 'error' => 'Seller not found'];
    }

    // Check if conversation already exists
    $check = mysqli_query($koneksi, "SELECT id_conversation FROM chat_conversations
                                     WHERE id_seller = $id_seller AND id_buyer = $id_user AND id_kantin = $id_kantin");

    if ($check && mysqli_num_rows($check) > 0) {
        $existing = mysqli_fetch_assoc($check);
        return ['success' => true, 'id_conversation' => (int)$existing['id_conversation']];
    }

    // Create new conversation
    $sql = "INSERT INTO chat_conversations (id_seller, id_buyer, id_kantin, created_at, updated_at)
            VALUES ($id_seller, $id_user, $id_kantin, NOW(), NOW())";

    if (!mysqli_query($koneksi, $sql)) {
        return ['success' => false, 'error' => 'Failed to create conversation: ' . mysqli_error($koneksi)];
    }

    return ['success' => true, 'id_conversation' => (int)mysqli_insert_id($koneksi)];
}

/**
 * Mark messages as read
 */
function markRead($koneksi, $id_conversation, $id_user, $role) {
    $id_conversation = (int)$id_conversation;

    // Update only messages not sent by current user
    $sql = "UPDATE chat_messages
            SET is_read = 1, read_at = NOW()
            WHERE id_conversation = $id_conversation
            AND id_sender != $id_user
            AND is_read = 0";

    mysqli_query($koneksi, $sql);

    return ['success' => true];
}

/**
 * Get total unread count for badge
 */
function getUnreadCount($koneksi, $id_user, $role) {
    if ($role === 'penjual') {
        $sql = "SELECT COUNT(*) as total
                FROM chat_messages cm
                JOIN chat_conversations c ON cm.id_conversation = c.id_conversation
                WHERE c.id_seller = $id_user
                AND cm.id_sender != $id_user
                AND cm.is_read = 0";
    } else {
        $sql = "SELECT COUNT(*) as total
                FROM chat_messages cm
                JOIN chat_conversations c ON cm.id_conversation = c.id_conversation
                WHERE c.id_buyer = $id_user
                AND cm.id_sender != $id_user
                AND cm.is_read = 0";
    }

    $result = mysqli_query($koneksi, $sql);
    $row = mysqli_fetch_assoc($result);

    return ['success' => true, 'count' => (int)($row['total'] ?? 0)];
}
?>
