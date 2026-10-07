<?php
require_once 'includes/db.php';
header('Content-Type: text/plain');

function describe($pdo, $table) {
    echo "\n--- $table ---\n";
    try {
        $cols = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
        print_r($cols);
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

describe($pdo, 'user_investments');
describe($pdo, 'transactions');
describe($pdo, 'users');
?>
