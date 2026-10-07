<?php
require_once __DIR__ . '/../includes/db.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rate_limits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            identifier VARCHAR(255) NOT NULL,
            endpoint VARCHAR(255) NOT NULL,
            hits INT DEFAULT 1,
            window_end INT NOT NULL,
            UNIQUE KEY idx_identifier_endpoint (identifier, endpoint)
        )
    ");
    echo "Migration Success: rate_limits table verified/created successfully.\n";
} catch (Exception $e) {
    echo "Migration Failure: " . $e->getMessage() . "\n";
}
?>
