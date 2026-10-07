<?php
// Load security utility first
require_once __DIR__ . '/security.php';
loadEnv(__DIR__ . '/../.env');

// Connect using Environment Variables
$host    = getenv('DB_HOST') ?: '127.0.0.1';
$port    = getenv('DB_PORT') ?: '3306';
$db      = getenv('DB_NAME') ?: 'gpt33_db';
$user    = getenv('DB_USER') ?: 'root';
$pass    = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Secure endpoints: Apply rate limits and strict schema validation
    checkRateLimit($pdo);
    validateRequestInputs();
} catch (\PDOException $e) {
    // Return JSON error if AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database connection failed.']);
        exit;
    }
    // Otherwise show simple error
    die("Database connection failed.");
}

function accrueUserEarnings($pdo, $user_id) {
    if (!$user_id) return;
    try {
        // Fetch active investments for this user
        $stmt = $pdo->prepare("
            SELECT ui.*, p.name as plan_name, p.hourly_rate 
            FROM user_investments ui
            JOIN plans p ON ui.plan_id = p.id
            WHERE ui.user_id = ? AND ui.status = 'active'
        ");
        $stmt->execute([$user_id]);
        $investments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($investments)) return;
        
        $now = new DateTime();
        foreach ($investments as $inv) {
            $last_calc = new DateTime($inv['last_profit_calc'] ?: $inv['created_at']);
            $diff = $now->diff($last_calc);
            $hours = ($diff->days * 24) + $diff->h;
            
            if ($hours > 0) {
                $hourly_profit = (float)$inv['invested_amount'] * ((float)$inv['hourly_rate'] / 100);
                $total_profit = $hourly_profit * $hours;
                
                if ($total_profit > 0) {
                    $pdo->beginTransaction();
                    try {
                        // 1. Credit user's earnings balance
                        $stmt = $pdo->prepare("UPDATE users SET earnings_balance = earnings_balance + ? WHERE id = ?");
                        $stmt->execute([$total_profit, $user_id]);
                        
                        // 2. Update user_investments record
                        $new_last_calc = clone $last_calc;
                        $new_last_calc->modify("+$hours hours");
                        $new_last_calc_str = $new_last_calc->format('Y-m-d H:i:s');
                        
                        $stmt = $pdo->prepare("UPDATE user_investments SET total_profit = total_profit + ?, last_profit_calc = ? WHERE id = ?");
                        $stmt->execute([$total_profit, $new_last_calc_str, $inv['id']]);
                        
                        // 3. Log transaction
                        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, amount, type, status, method_name) VALUES (?, ?, 'hourly_profit', 'completed', ?)");
                        $stmt->execute([$user_id, $total_profit, "Accrued Yield ($hours hrs)"]);
                        
                        // 4. Send notification
                        $notif_msg = "Your AI cluster '{$inv['plan_name']}' generated $" . number_format($total_profit, 4) . " yield over the last $hours hours.";
                        $notif_stmt = $pdo->prepare("INSERT INTO user_notifications (user_id, type, message, link) VALUES (?, 'yield', ?, '/investments')");
                        $notif_stmt->execute([$user_id, $notif_msg]);
                        
                        $pdo->commit();
                    } catch (Exception $innerEx) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Fail silently to prevent breaking page load
    }
}

// Automatically trigger accrual if a user is logged in
if (session_status() !== PHP_SESSION_NONE && isset($_SESSION['user_id'])) {
    accrueUserEarnings($pdo, $_SESSION['user_id']);
}
?>
