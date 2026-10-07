<?php
/**
 * Schema Migration: Add deposit_bonus column to settings table
 */
define('BYPASS_CLI_CHECK', true);
require_once __DIR__ . '/../includes/db.php';

try {
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM settings LIKE 'deposit_bonus'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        $pdo->exec("ALTER TABLE settings ADD COLUMN deposit_bonus DECIMAL(5,2) DEFAULT 0.00");
        echo "SUCCESS: 'deposit_bonus' column successfully added to 'settings' table.\n";
    } else {
        echo "INFO: 'deposit_bonus' column already exists in 'settings' table. Skipping.\n";
    }
} catch (PDOException $e) {
    echo "ERROR: Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
