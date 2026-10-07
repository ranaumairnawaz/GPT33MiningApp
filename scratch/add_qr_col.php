<?php
require_once 'includes/db.php';

try {
    $pdo->exec("ALTER TABLE payment_methods ADD COLUMN qr_code VARCHAR(255) DEFAULT NULL");
    echo "Column qr_code added to payment_methods.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
