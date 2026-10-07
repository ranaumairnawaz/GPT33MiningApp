<?php
require 'includes/db.php';

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN referral_code VARCHAR(10) UNIQUE AFTER id");
    echo "Column added.\n";
} catch (Exception $e) {
    echo "Column might already exist.\n";
}

// Generate codes for existing users
$users = $pdo->query("SELECT id FROM users WHERE referral_code IS NULL OR referral_code = ''")->fetchAll();
foreach ($users as $user) {
    $code = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
    $pdo->prepare("UPDATE users SET referral_code = ? WHERE id = ?")->execute([$code, $user['id']]);
    echo "Generated $code for user {$user['id']}\n";
}
?>