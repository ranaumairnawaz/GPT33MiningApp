<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain');

echo "--- TRANSACTIONS SCHEMA ---\n";
try {
    $cols = $pdo->query("DESCRIBE transactions")->fetchAll(PDO::FETCH_ASSOC);
    print_r($cols);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
