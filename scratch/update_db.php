<?php
require_once 'includes/db.php';
try {
    $pdo->exec("ALTER TABLE transactions MODIFY COLUMN type ENUM('deposit','withdrawal','referral_commission','manual_adjustment','investment','profit','hourly_profit')");
    echo "Enum updated successfully";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>