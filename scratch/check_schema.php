<?php
require 'includes/db.php';
function checkTable($pdo, $table) {
    echo "--- $table ---\n";
    try {
        $res = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
        foreach($res as $row) {
            echo "{$row['Field']} ({$row['Type']})\n";
        }
    } catch(Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

checkTable($pdo, 'users');
checkTable($pdo, 'payment_methods');
checkTable($pdo, 'withdrawal_methods');
checkTable($pdo, 'admin_logs');
checkTable($pdo, 'plans');
?>
