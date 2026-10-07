<?php
ob_start(); // Buffer output to prevent headers/warnings from corrupting JSON
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized session. Please login again.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$plan_id = (int)($_POST['plan_id'] ?? 0);

try {
    // 1. Fetch Plan
    $stmt = $pdo->prepare("SELECT * FROM plans WHERE id = ? AND is_active = 1");
    $stmt->execute([$plan_id]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$plan) {
        throw new Exception("The selected investment plan is currently unavailable.");
    }

    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0.0;
    $min_invest = (float)$plan['min_invest'];
    $max_invest = (float)$plan['max_invest'];

    if ($amount < $min_invest || $amount > $max_invest) {
        throw new Exception("Investment amount must be between $" . number_format($min_invest, 2) . " and $" . number_format($max_invest, 2) . ".");
    }

    // 2. Check Balance
    $stmt = $pdo->prepare("SELECT deposit_balance, earnings_balance FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User account not found.");
    }

    $dep_bal = (float)($user['deposit_balance'] ?? 0);
    $earn_bal = (float)($user['earnings_balance'] ?? 0);
    $total_bal = $dep_bal + $earn_bal;

    if ($total_bal < $amount) {
        throw new Exception("Insufficient balance. Total available: $" . number_format($total_bal, 2));
    }

    // 3. Process Transaction
    $pdo->beginTransaction();

    // Deduct balance (priority: deposit_balance then earnings_balance)
    if ($dep_bal >= $amount) {
        $stmt = $pdo->prepare("UPDATE users SET deposit_balance = deposit_balance - ? WHERE id = ?");
        $stmt->execute([$amount, $user_id]);
    } else {
        $remaining = $amount - $dep_bal;
        $stmt = $pdo->prepare("UPDATE users SET deposit_balance = 0, earnings_balance = earnings_balance - ? WHERE id = ?");
        $stmt->execute([$remaining, $user_id]);
    }

    // Create investment record
    $stmt = $pdo->prepare("INSERT INTO user_investments (user_id, plan_id, invested_amount, status, created_at) VALUES (?, ?, ?, 'active', NOW())");
    $stmt->execute([$user_id, $plan_id, $amount]);

    // Log transaction
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, status, method_name, created_at) VALUES (?, 'investment', ?, 'completed', ?, NOW())");
    $stmt->execute([$user_id, $amount, $plan['name']]);

    // 4. Referral Commission Logic (5 Levels)
    $settings_stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
    $settings = $settings_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($settings) {
        $commission_rates = [
            (float)($settings['ref_level_1'] ?? 0),
            (float)($settings['ref_level_2'] ?? 0),
            (float)($settings['ref_level_3'] ?? 0),
            (float)($settings['ref_level_4'] ?? 0),
            (float)($settings['ref_level_5'] ?? 0)
        ];

        $current_upline_id = $user_id;
        for ($i = 0; $i < 5; $i++) {
            $stmt = $pdo->prepare("SELECT referred_by FROM users WHERE id = ?");
            $stmt->execute([$current_upline_id]);
            $parent = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$parent || !$parent['referred_by']) break;
            
            $upline_id = (int)$parent['referred_by'];
            $rate = $commission_rates[$i];
            
            if ($rate > 0) {
                $commission = $amount * ($rate / 100);
                if ($commission > 0) {
                    $stmt = $pdo->prepare("UPDATE users SET earnings_balance = earnings_balance + ? WHERE id = ?");
                    $stmt->execute([$commission, $upline_id]);

                    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, status, method_name, created_at) VALUES (?, 'referral_commission', ?, 'completed', ?, NOW())");
                    $stmt->execute([$upline_id, $commission, "Level " . ($i+1) . " Commission"]);

                    // Notify upline
                    $notif_msg = "You received a $" . number_format($commission, 2) . " referral commission from level " . ($i+1) . ".";
                    $notif_stmt = $pdo->prepare("INSERT INTO user_notifications (user_id, type, message, link) VALUES (?, 'referral', ?, '/referrals')");
                    $notif_stmt->execute([$upline_id, $notif_msg]);
                }
            }
            $current_upline_id = $upline_id;
        }
    }

    $pdo->commit();
    ob_end_clean();
    echo json_encode(['status' => 'success', 'message' => 'Investment of $' . number_format($amount, 2) . ' successful!']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit;
