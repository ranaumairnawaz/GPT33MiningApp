<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/db.php';
error_reporting(0);
ini_set('display_errors', 0);


header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method_id = $_POST['method_id'] ?? 0;
$amount = (float)($_POST['amount'] ?? 0);
$account_name = $_POST['account_name'] ?? '';
$account_number = $_POST['account_number'] ?? '';

// Fetch method
$stmt = $pdo->prepare("SELECT name, min_amount FROM withdrawal_methods WHERE id = ? AND is_active = 1");
$stmt->execute([$method_id]);
$method = $stmt->fetch();

if (!$method) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid withdrawal method']);
    exit;
}

if ($amount < $method['min_amount']) {
    echo json_encode(['status' => 'error', 'message' => 'Minimum withdrawal is $' . $method['min_amount']]);
    exit;
}

// Check user balance
$stmt = $pdo->prepare("SELECT deposit_balance, earnings_balance FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$total_balance = $user['deposit_balance'] + $user['earnings_balance'];

if ($total_balance < $amount) {
    echo json_encode(['status' => 'error', 'message' => 'Insufficient balance']);
    exit;
}

// Start transaction
$pdo->beginTransaction();

try {
    // Deduct from balances (Prioritize earnings_balance)
    $remaining_to_deduct = $amount;
    
    // Calculate how much to take from earnings
    $deduct_from_earnings = min($user['earnings_balance'], $remaining_to_deduct);
    $remaining_to_deduct -= $deduct_from_earnings;
    
    // Calculate how much to take from deposit
    $deduct_from_deposit = $remaining_to_deduct;

    // Update balances
    $stmt = $pdo->prepare("UPDATE users SET earnings_balance = earnings_balance - ?, deposit_balance = deposit_balance - ? WHERE id = ?");
    $stmt->execute([$deduct_from_earnings, $deduct_from_deposit, $user_id]);

    // Log pending transaction
    $details = "Name: $account_name, Account: $account_number";
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, method_name, account_details, status) VALUES (?, 'withdrawal', ?, ?, ?, 'pending')");
    $stmt->execute([$user_id, $amount, $method['name'], $details]);

    // Notify Admin
    $notif_stmt = $pdo->prepare("INSERT INTO admin_notifications (type, user_id, message, link) VALUES ('withdrawal', ?, ?, 'index.php?route=withdrawals')");
    $notif_stmt->execute([$user_id, "New withdrawal request of $" . number_format($amount, 2) . " via " . $method['name']]);

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Withdrawal request submitted successfully!']);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>
