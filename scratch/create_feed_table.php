<?php
require_once __DIR__ . '/../includes/db.php';

echo "--- CREATING LIVE FEED TABLE ---\n";

try {
    // 1. Create live_feed table
    $sql = "CREATE TABLE IF NOT EXISTS live_feed (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type ENUM('admin', 'withdrawal') NOT NULL DEFAULT 'withdrawal',
        user_name VARCHAR(100) NULL,
        amount DECIMAL(15,2) NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Table 'live_feed' created successfully.\n";

    // 2. Populate some sample withdrawal successes from transactions if available
    $check = $pdo->query("SELECT COUNT(*) FROM live_feed")->fetchColumn();
    if ($check == 0) {
        $stmt = $pdo->query("
            SELECT t.amount, t.created_at, u.full_name 
            FROM transactions t 
            JOIN users u ON t.user_id = u.id 
            WHERE t.type = 'withdrawal' AND t.status = 'completed'
            ORDER BY t.created_at DESC LIMIT 10
        ");
        $payouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($payouts as $p) {
            $name = htmlspecialchars($p['full_name']);
            $amt = number_format($p['amount'], 2);
            
            // Generate the premium high-converting message
            $msg = "⚡ <b>Disbursement Approved & Dispatched!</b>\n\n";
            $msg .= "Congratulations to our premium investor <b>{$name}</b> for successfully cashing out <b>\${$amt}</b>! 💸\n\n";
            $msg .= "🔥 <i>Our high-powered AI computing clusters are operating at peak efficiency! Don't let your funds sit idle—reinvest or upgrade your hash rate now to maximize your passive yield!</i> 💻📈";
            
            $insert = $pdo->prepare("INSERT INTO live_feed (type, user_name, amount, message, created_at) VALUES ('withdrawal', ?, ?, ?, ?)");
            $insert->execute([$name, $p['amount'], $msg, $p['created_at']]);
        }
        echo "Populated " . count($payouts) . " historical payouts into the live feed.\n";
    }

    echo "Migration completed successfully.\n";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
