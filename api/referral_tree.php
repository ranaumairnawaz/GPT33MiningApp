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

function getDownline($pdo, $parentId, $level = 1) {
    if ($level > 5) return []; // Max 5 levels
    
    $stmt = $pdo->prepare("SELECT id, full_name, email, created_at FROM users WHERE referred_by = ?");
    $stmt->execute([$parentId]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as &$u) {
        $u['level'] = $level;
        // Obfuscate email for privacy
        $email_parts = explode('@', $u['email']);
        $u['email'] = substr($email_parts[0], 0, 3) . '***@' . ($email_parts[1] ?? '***');
        
        $u['children'] = getDownline($pdo, $u['id'], $level + 1);
        
        // Count active investments
        $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM user_investments WHERE user_id = ? AND status = 'active'");
        $stmt2->execute([$u['id']]);
        $u['is_active'] = $stmt2->fetchColumn() > 0;
    }
    return $users;
}

ob_end_clean();
echo json_encode([
    'status' => 'success', 
    'tree' => getDownline($pdo, $user_id)
]);
?>
