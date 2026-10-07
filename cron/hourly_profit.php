<?php
// This script should be run every hour via Cron
// Command: php c:\XAMPP\htdocs\GPT33\cron\hourly_profit.php

require_once __DIR__ . '/../includes/db.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting hourly profit distribution...\n";

try {
    // Fetch all active investments
    $stmt = $pdo->query("
        SELECT ui.*, p.hourly_rate 
        FROM user_investments ui
        JOIN plans p ON ui.plan_id = p.id
        WHERE ui.status = 'active'
    ");
    $investments = $stmt->fetchAll();

    $pdo->beginTransaction();

    $user_profits = [];
    foreach ($investments as $inv) {
        $profit = $inv['invested_amount'] * ($inv['hourly_rate'] / 100);

        if ($profit > 0) {
            // Update user balance
            $stmt = $pdo->prepare("UPDATE users SET earnings_balance = earnings_balance + ? WHERE id = ?");
            $stmt->execute([$profit, $inv['user_id']]);

            // Update investment record
            $stmt = $pdo->prepare("UPDATE user_investments SET total_profit = total_profit + ?, last_profit_calc = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$profit, $inv['id']]);

            // Record transaction
            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, status) VALUES (?, ?, 'hourly_profit', 'completed')");
            $stmt->execute([$inv['user_id'], $profit]);

            // Track for summary notification
            $user_profits[$inv['user_id']] = ($user_profits[$inv['user_id']] ?? 0) + $profit;

            echo "Distributed $" . number_format($profit, 8) . " to User ID: " . $inv['user_id'] . "\n";
        }
    }

    // Send summary notifications
    foreach ($user_profits as $uid => $total) {
        $msg = "Your AI clusters generated $" . number_format($total, 4) . " yield this hour.";
        $stmt = $pdo->prepare("INSERT INTO user_notifications (user_id, type, message, link) VALUES (?, 'yield', ?, '/investments')");
        $stmt->execute([$uid, $msg]);
    }

    $pdo->commit();
    echo "[" . date('Y-m-d H:i:s') . "] Success! Profit distribution completed.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction())
        $pdo->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>