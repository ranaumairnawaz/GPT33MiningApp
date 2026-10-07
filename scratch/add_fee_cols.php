<?php
require_once 'includes/db.php';

try {
    $pdo->exec("ALTER TABLE payment_methods ADD COLUMN fixed_fee DECIMAL(10, 4) DEFAULT 0");
    $pdo->exec("ALTER TABLE payment_methods ADD COLUMN percent_fee DECIMAL(10, 4) DEFAULT 0");
    
    $pdo->exec("ALTER TABLE withdrawal_methods ADD COLUMN fixed_fee DECIMAL(10, 4) DEFAULT 0");
    $pdo->exec("ALTER TABLE withdrawal_methods ADD COLUMN percent_fee DECIMAL(10, 4) DEFAULT 0");
    
    echo "Fee columns added successfully to both tables.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
