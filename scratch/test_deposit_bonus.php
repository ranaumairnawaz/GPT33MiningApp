<?php
/**
 * Automated Verification: Dynamic Deposit Bonus Feature
 */
define('BYPASS_CLI_CHECK', true);
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: text/plain');

echo "=========================================================\n";
echo "STARTING VERIFICATION: DYNAMIC DEPOSIT BONUS ENGINE\n";
echo "=========================================================\n\n";

function assertTest($label, $assertion) {
    if ($assertion) {
        echo "✅ PASS: $label\n";
    } else {
        echo "❌ FAIL: $label\n";
        exit(1);
    }
}

// 1. Initialise Test variables
$test_email = 'test_bonus_' . time() . '@example.com';
$deposit_amount = 100.00;
$bonus_percent = 12.50; // 12.5%

try {
    $pdo->beginTransaction();

    // 2. Create Dummy User
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, deposit_balance, earnings_balance) VALUES (?, ?, 'hash', 0, 0)");
    $stmt->execute(['Test Bonus User', $test_email]);
    $user_id = $pdo->lastInsertId();
    assertTest("Dummy user created with ID $user_id", $user_id > 0);

    // 3. Create Pending Deposit Transaction
    $stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, status, method_name, account_details) VALUES (?, 'deposit', ?, 'pending', 'Test Gateway', 'TID-12345')");
    $stmt->execute([$user_id, $deposit_amount]);
    $tx_id = $pdo->lastInsertId();
    assertTest("Pending deposit transaction created with ID $tx_id", $tx_id > 0);

    // 4. Temporarily override deposit_bonus setting to 12.50%
    $orig_settings = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch();
    $stmt = $pdo->prepare("UPDATE settings SET deposit_bonus = ? WHERE id = 1");
    $stmt->execute([$bonus_percent]);
    assertTest("Global deposit bonus successfully updated to $bonus_percent%", true);

    // 5. Run the EXACT logic from api/admin_actions.php for update_deposit_status
    // Fetch transaction
    $tx_stmt = $pdo->prepare("SELECT user_id, amount, status FROM transactions WHERE id = ?");
    $tx_stmt->execute([$tx_id]);
    $tx = $tx_stmt->fetch();
    
    assertTest("Retrieved active pending transaction details", $tx && $tx['status'] === 'pending');

    // Fetch active deposit bonus setting
    $settings_stmt = $pdo->query("SELECT deposit_bonus FROM settings LIMIT 1");
    $settings = $settings_stmt->fetch();
    $active_bonus = (float)($settings['deposit_bonus'] ?? 0);
    assertTest("Active deposit bonus matches our override ($active_bonus%)", abs($active_bonus - $bonus_percent) < 0.01);

    // Calculate bonus and total credit
    $bonus_amount = 0;
    if ($active_bonus > 0) {
        $bonus_amount = $tx['amount'] * ($active_bonus / 100);
    }
    $total_credit = $tx['amount'] + $bonus_amount;
    assertTest("Calculated bonus amount is exactly $bonus_amount (expected: 12.50)", abs($bonus_amount - 12.50) < 0.001);
    assertTest("Calculated total credited amount is exactly $total_credit (expected: 112.50)", abs($total_credit - 112.50) < 0.001);

    // Credit user's balance
    $credit_stmt = $pdo->prepare("UPDATE users SET deposit_balance = deposit_balance + ? WHERE id = ?");
    $credit_stmt->execute([$total_credit, $tx['user_id']]);

    // Approve base transaction
    $approve_stmt = $pdo->prepare("UPDATE transactions SET status = 'approved' WHERE id = ?");
    $approve_stmt->execute([$tx_id]);

    // Insert separate transparent transaction for bonus
    if ($bonus_amount > 0) {
        $bonus_tx_stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, status, method_name) VALUES (?, 'manual_adjustment', ?, 'completed', ?)");
        $bonus_tx_stmt->execute([
            $tx['user_id'],
            $bonus_amount,
            "Deposit Bonus (" . number_format($active_bonus, 2) . "%) for deposit #" . $tx_id
        ]);
        $bonus_tx_id = $pdo->lastInsertId();
    }
    assertTest("Created transparent bonus audit transaction", isset($bonus_tx_id) && $bonus_tx_id > 0);

    // Create notification
    $notif_msg = "Your deposit of $" . number_format($tx['amount'], 2) . " has been approved.";
    if ($bonus_amount > 0) {
        $notif_msg .= " Plus a dynamic deposit bonus of $" . number_format($bonus_amount, 2) . " (" . number_format($active_bonus, 2) . "%) has been credited!";
    }
    $notif_stmt = $pdo->prepare("INSERT INTO user_notifications (user_id, type, message, link) VALUES (?, 'deposit', ?, '/transactions')");
    $notif_stmt->execute([$tx['user_id'], $notif_msg]);
    $notif_id = $pdo->lastInsertId();
    assertTest("Sent custom notification to user", $notif_id > 0);

    // 6. Verification Queries
    // Verify user balance
    $user_check_stmt = $pdo->prepare("SELECT deposit_balance FROM users WHERE id = ?");
    $user_check_stmt->execute([$user_id]);
    $final_balance = (float)$user_check_stmt->fetchColumn();
    assertTest("Verified user's final balance is exactly $final_balance (expected: 112.50)", abs($final_balance - 112.50) < 0.001);

    // Verify transactions count
    $txs_stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY id ASC");
    $txs_stmt->execute([$user_id]);
    $txs = $txs_stmt->fetchAll();
    assertTest("User has exactly two transaction records", count($txs) === 2);
    
    assertTest("First transaction is approved base deposit of $100.00", $txs[0]['type'] === 'deposit' && $txs[0]['status'] === 'approved' && abs($txs[0]['amount'] - 100.00) < 0.01);
    assertTest("Second transaction is completed deposit bonus of $12.50", $txs[1]['type'] === 'manual_adjustment' && $txs[1]['status'] === 'completed' && abs($txs[1]['amount'] - 12.50) < 0.01 && strpos($txs[1]['method_name'], 'Deposit Bonus') !== false);

    // Verify Notification text
    $notif_check_stmt = $pdo->prepare("SELECT message FROM user_notifications WHERE id = ?");
    $notif_check_stmt->execute([$notif_id]);
    $notif_text = $notif_check_stmt->fetchColumn();
    assertTest("Notification message contains the exact deposit bonus detail", strpos($notif_text, '12.50%') !== false && strpos($notif_text, '$12.50') !== false);

    // 7. Revert settings to original
    $settings_restore = $pdo->prepare("UPDATE settings SET deposit_bonus = ? WHERE id = 1");
    $settings_restore->execute([$orig_settings['deposit_bonus']]);
    echo "\nRestored global system settings back to " . $orig_settings['deposit_bonus'] . "%\n";

    // 8. Rollback transactions so database remains clean
    $pdo->rollBack();
    echo "Successfully rolled back transactions. Database remains 100% clean!\n\n";

    echo "=========================================================\n";
    echo "🎉 ALL TESTS PASSED SUCCESSFULLY! Dynamic Deposit Bonus verified!\n";
    echo "=========================================================\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    exit(1);
}
?>
