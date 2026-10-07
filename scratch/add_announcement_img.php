<?php
require_once 'includes/db.php';

try {
    $pdo->exec("ALTER TABLE settings ADD COLUMN announcement_image VARCHAR(255) DEFAULT NULL");
    echo "Announcement image column added successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
