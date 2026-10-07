<?php
require_once 'includes/db.php';
try {
    $pdo->exec("ALTER TABLE transactions MODIFY COLUMN type ENUM('deposit', 'withdrawal', 'referral_commission', 'investment') NOT NULL");
    echo "SUCCESS: Transactions table updated.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
