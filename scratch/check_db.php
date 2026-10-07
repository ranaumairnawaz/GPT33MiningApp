<?php
require_once 'includes/db.php';
$stmt = $pdo->query("DESCRIBE user_investments");
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
