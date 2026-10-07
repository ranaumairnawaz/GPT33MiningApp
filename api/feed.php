<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    // Fetch the 50 latest announcements and withdrawals
    $stmt = $pdo->query("SELECT * FROM live_feed ORDER BY created_at DESC LIMIT 50");
    $feed = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format times user-friendly
    foreach ($feed as &$item) {
        $time = strtotime($item['created_at']);
        $diff = time() - $time;
        
        if ($diff < 60) {
            $item['time_formatted'] = 'Just now';
        } elseif ($diff < 3600) {
            $item['time_formatted'] = floor($diff / 60) . 'm ago';
        } elseif ($diff < 86400) {
            $item['time_formatted'] = floor($diff / 3600) . 'h ago';
        } else {
            $item['time_formatted'] = date('M d, H:i', $time);
        }
        
        // Ensure message content has proper HTML line breaks and styling
        $item['message'] = nl2br($item['message']);
    }

    echo json_encode(['status' => 'success', 'feed' => $feed]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
