<?php
require_once __DIR__ . '/../includes/db.php';

echo "--- RUNNING MIGRATION FOR INVESTMENT PLAN RANGE ---\n";

try {
    // 1. Add max_invest column to plans if not exists
    $check = $pdo->query("SHOW COLUMNS FROM plans LIKE 'max_invest'")->fetch();
    if (!$check) {
        $pdo->exec("ALTER TABLE plans ADD COLUMN max_invest DECIMAL(15,2) DEFAULT 10000.00 AFTER min_invest");
        echo "Added column max_invest to plans table.\n";
    } else {
        echo "max_invest column already exists.\n";
    }

    // 2. Make sure min_invest defaults to fixed_price for existing plans if min_invest is 10.00 (default)
    // and fixed_price is non-zero. Let's inspect the plans.
    $plans = $pdo->query("SELECT * FROM plans")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($plans as $plan) {
        $fixed = (float)$plan['fixed_price'];
        $min = (float)$plan['min_invest'];
        $max = (float)$plan['max_invest'];
        
        // If min_invest is 10.00 (default) and fixed_price is set, let's make a nice range:
        // min = fixed_price, max = fixed_price * 10 or default 10000.
        if ($fixed > 0 && ($min == 10.00 || $min == 0.00)) {
            $new_min = $fixed;
            $new_max = $fixed * 10;
            $stmt = $pdo->prepare("UPDATE plans SET min_invest = ?, max_invest = ? WHERE id = ?");
            $stmt->execute([$new_min, $new_max, $plan['id']]);
            echo "Updated Plan ID {$plan['id']} ({$plan['name']}) range: Min = $new_min, Max = $new_max.\n";
        }
    }

    echo "Migration completed successfully.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
