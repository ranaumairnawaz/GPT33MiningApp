<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain');

echo "--- TRANSACTION COUNTS ---\n";
try {
    $counts = $pdo->query("SELECT type, COUNT(*) as qty FROM transactions GROUP BY type")->fetchAll(PDO::FETCH_ASSOC);
    print_r($counts);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
