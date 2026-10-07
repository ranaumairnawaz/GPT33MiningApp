<?php
require_once 'includes/db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS tiers (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        name VARCHAR(255), 
        min_investment DECIMAL(10, 2), 
        color VARCHAR(50), 
        icon VARCHAR(100)
    )");

    $stmt = $pdo->query("SELECT COUNT(*) FROM tiers");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO tiers (name, min_investment, color, icon) VALUES 
            ('Bronze', 0, '#cd7f32', 'fa-solid fa-award'), 
            ('Silver', 1000, '#c0c0c0', 'fa-solid fa-medal'), 
            ('Gold', 5000, '#ffd700', 'fa-solid fa-crown'), 
            ('Platinum', 10000, '#e5e4e2', 'fa-solid fa-gem')");
        echo "Tiers table created and populated.";
    } else {
        echo "Tiers table already exists.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
