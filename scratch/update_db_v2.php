<?php
require 'includes/db.php';

echo "Updating database schema...\n";

// 1. Add fixed_price to plans
try {
    $pdo->exec("ALTER TABLE plans ADD COLUMN fixed_price DECIMAL(15,2) DEFAULT 0.00");
    echo "Added fixed_price column to plans\n";
    
    // Migrate min_invest to fixed_price
    $pdo->exec("UPDATE plans SET fixed_price = min_invest");
    echo "Migrated existing min_invest values to fixed_price\n";
} catch (Exception $e) {
    echo "Plans update: " . $e->getMessage() . "\n";
}

// 2. Add tier to users
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN tier VARCHAR(50) DEFAULT 'Bronze'");
    echo "Added tier column to users\n";
} catch (Exception $e) {
    echo "Users update: " . $e->getMessage() . "\n";
}

echo "Database update completed.\n";
?>
