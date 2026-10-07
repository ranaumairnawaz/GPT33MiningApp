<?php
require_once 'includes/db.php';
header('Content-Type: text/plain');

echo "--- PLANS ---\n";
$plans = $pdo->query("SELECT * FROM plans")->fetchAll(PDO::FETCH_ASSOC);
print_r($plans);

echo "\n--- USERS (Sample) ---\n";
$users = $pdo->query("SELECT id, email, deposit_balance, earnings_balance FROM users LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
print_r($users);

echo "\n--- SETTINGS ---\n";
$settings = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
print_r($settings);
?>
