<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($action === 'send') {
    $message = $_POST['message'] ?? '';
    if (empty($message)) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Message cannot be empty']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO chat_messages (sender_id, message) VALUES (?, ?)");
    if ($stmt->execute([$user_id, $message])) {
        // Notify Admin if no unread chat notif from this user exists
        $check_stmt = $pdo->prepare("SELECT id FROM admin_notifications WHERE type = 'chat' AND user_id = ? AND is_read = 0");
        $check_stmt->execute([$user_id]);
        if (!$check_stmt->fetch()) {
            $notif_stmt = $pdo->prepare("INSERT INTO admin_notifications (type, user_id, message, link) VALUES ('chat', ?, 'New communication request from node', 'index.php?route=chat')");
            $notif_stmt->execute([$user_id]);
        }
        
        ob_end_clean();
        echo json_encode(['status' => 'success']);
    } else {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Failed to save message']);
    }
    exit;
} 

elseif ($action === 'get_messages') {
    // If admin is requesting specific user chat
    $target_id = isset($_GET['user_id']) && $_SESSION['is_admin'] ? $_GET['user_id'] : $user_id;

    $stmt = $pdo->prepare("
        SELECT m.*, u.is_admin
        FROM chat_messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.sender_id = ? OR m.receiver_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$target_id, $target_id]);
    $messages = $stmt->fetchAll();

    $formatted = array_map(function($m) {
        return [
            'message' => htmlspecialchars($m['message'], ENT_QUOTES),
            'sender_id' => $m['sender_id'],
            'is_admin' => $m['is_admin'],
            'time' => date('H:i', strtotime($m['created_at']))
        ];
    }, $messages);

    ob_end_clean();
    echo json_encode(['status' => 'success', 'messages' => $formatted]);
}

elseif ($action === 'admin_send' && $_SESSION['is_admin']) {
    $receiver_id = $_POST['receiver_id'] ?? 0;
    $message = $_POST['message'] ?? '';
    
    if (empty($message) || $receiver_id == 0) {
        ob_end_clean();
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO chat_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    if ($stmt->execute([$user_id, $receiver_id, $message])) {
        // Notify user if no unread chat notif from admin exists
        $check_stmt = $pdo->prepare("SELECT id FROM user_notifications WHERE type = 'chat' AND user_id = ? AND is_read = 0");
        $check_stmt->execute([$receiver_id]);
        if (!$check_stmt->fetch()) {
            $notif_stmt = $pdo->prepare("INSERT INTO user_notifications (user_id, type, message, link) VALUES (?, 'chat', 'New message from support staff', '/support')");
            $notif_stmt->execute([$receiver_id]);
        }
        ob_end_clean();
        echo json_encode(['status' => 'success']);
    } else {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Failed to transmit intelligence response']);
    }
    exit;
}

elseif ($action === 'mark_as_read' && $_SESSION['is_admin']) {
    $target_id = $_GET['user_id'] ?? 0;
    if ($target_id) {
        $stmt = $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE sender_id = ? AND receiver_id IS NULL");
        $stmt->execute([$target_id]);
    }
    ob_end_clean();
    echo json_encode(['status' => 'success']);
}

elseif ($action === 'get_chat_list' && $_SESSION['is_admin']) {
    $stmt = $pdo->query("
        SELECT DISTINCT u.id, u.full_name, u.email, 
        (SELECT message FROM chat_messages WHERE sender_id = u.id OR receiver_id = u.id ORDER BY created_at DESC LIMIT 1) as last_msg,
        (SELECT created_at FROM chat_messages WHERE sender_id = u.id OR receiver_id = u.id ORDER BY created_at DESC LIMIT 1) as last_time,
        (SELECT COUNT(*) FROM chat_messages WHERE sender_id = u.id AND is_read = 0) as unread_count
        FROM users u
        JOIN chat_messages m ON u.id = m.sender_id OR u.id = m.receiver_id
        WHERE u.is_admin = 0
        ORDER BY last_time DESC
    ");
    $chats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $formatted = array_map(function($c) {
        return [
            'id' => $c['id'],
            'full_name' => htmlspecialchars($c['full_name'], ENT_QUOTES),
            'last_msg' => htmlspecialchars($c['last_msg'], ENT_QUOTES),
            'unread_count' => (int)$c['unread_count'],
            'last_time' => $c['last_time']
        ];
    }, $chats);

    ob_end_clean();
    echo json_encode(['status' => 'success', 'chats' => $formatted]);
}
?>
