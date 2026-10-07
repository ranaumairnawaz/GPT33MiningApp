<?php
require_once 'includes/db.php';
try {
    $pdo->exec("ALTER TABLE payment_methods ADD COLUMN copyable_fields TEXT AFTER details");
    echo "SUCCESS: payment_methods table updated.\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
