<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain');

echo "--- USER INVESTMENTS ---\n";
try {
    $invs = $pdo->query("SELECT * FROM user_investments")->fetchAll(PDO::FETCH_ASSOC);
    print_r($invs);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
