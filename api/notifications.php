<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($action === 'get_count') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    $count = $stmt->fetchColumn();
    echo json_encode(['status' => 'success', 'count' => (int)$count]);
}

elseif ($action === 'get_unread') {
    $stmt = $pdo->prepare("SELECT * FROM user_notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'notifications' => $notifications]);
}

elseif ($action === 'mark_read') {
    $id = $_GET['id'] ?? 0;
    if ($id) {
        $stmt = $pdo->prepare("UPDATE user_notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
    } else {
        $type = $_GET['type'] ?? '';
        if ($type) {
            $stmt = $pdo->prepare("UPDATE user_notifications SET is_read = 1 WHERE user_id = ? AND type = ?");
            $stmt->execute([$user_id, $type]);
        }
    }
    echo json_encode(['status' => 'success']);
}
?>
