<?php
/**
 * One-time database recovery utility for GPT33 payment gateway fields.
 * Decodes HTML-escaped entities (&quot;) in copyable_fields back into raw valid JSON format.
 */

// Define CLI bypass so rate limits don't block CLI script run
define('BYPASS_CLI_CHECK', true);

require_once __DIR__ . '/../includes/security.php';
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
    echo "Connected successfully to the database '{$db}' on '{$host}'.\n\n";
    
    // Fetch all payment methods
    $stmt = $pdo->query("SELECT id, name, copyable_fields FROM payment_methods");
    $methods = $stmt->fetchAll();
    
    $recoveredCount = 0;
    
    foreach ($methods as $m) {
        $id = $m['id'];
        $name = $m['name'];
        $rawFields = $m['copyable_fields'];
        
        if (empty($rawFields)) {
            echo "Gateway [#{$id}] {$name}: No fields configured.\n";
            continue;
        }
        
        // Check if copyable_fields has html escaped content (contains &quot;)
        if (strpos($rawFields, '&quot;') !== false || strpos($rawFields, '&amp;') !== false) {
            echo "Gateway [#{$id}] {$name}: Found escaped entities. Attempting recovery...\n";
            echo "  Raw: " . $rawFields . "\n";
            
            // Decode HTML entities (e.g. &quot; -> ", &amp; -> &)
            $decoded = htmlspecialchars_decode($rawFields, ENT_QUOTES);
            echo "  Decoded: " . $decoded . "\n";
            
            // Validate that the decoded string is valid JSON
            $jsonDecoded = json_decode($decoded, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // Update the database record with the clean JSON string
                $updateStmt = $pdo->prepare("UPDATE payment_methods SET copyable_fields = ? WHERE id = ?");
                $updateStmt->execute([$decoded, $id]);
                echo "  \033[32m[SUCCESS]\033[0m: Gateway fields recovered successfully!\n\n";
                $recoveredCount++;
            } else {
                echo "  \033[31m[ERROR]\033[0m: Decoded string is not valid JSON. Error: " . json_last_error_msg() . "\n\n";
            }
        } else {
            // Already clean or not escaped
            $jsonDecoded = json_decode($rawFields, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo "Gateway [#{$id}] {$name}: JSON is already valid and healthy.\n";
            } else {
                echo "Gateway [#{$id}] {$name}: Not escaped but contains invalid JSON structure.\n";
            }
        }
    }
    
    echo "\nTotal gateways successfully recovered: {$recoveredCount}\n";
    
} catch (\PDOException $e) {
    die("Database connection or query failed: " . $e->getMessage() . "\n");
}
