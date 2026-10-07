<?php
/**
 * Automated Security Hardening Verification Suite for GPT33
 * Tests rate limiting, schema validation, parameter rejection, and secure uploads.
 */

// If requested to run a specific test case in isolation:
if (isset($argv[1]) && strpos($argv[1], '--run-case=') === 0) {
    define('BYPASS_CLI_CHECK', true);
    $caseName = substr($argv[1], 11);
    runTestCase($caseName);
    exit;
}

// Main Test Runner
$testCases = [
    'test_login_missing_fields' => [
        'desc' => 'Should reject request if email/password is missing',
        'assert' => function($output) {
            return strpos($output, 'missing required parameter') !== false && strpos($output, 'email') !== false;
        }
    ],
    'test_login_unexpected_field' => [
        'desc' => 'Should reject request containing HTTP parameter pollution/unexpected fields',
        'assert' => function($output) {
            return strpos($output, 'unexpected key') !== false && strpos($output, 'hacker_param') !== false;
        }
    ],
    'test_login_invalid_email' => [
        'desc' => 'Should reject invalid email formats',
        'assert' => function($output) {
            return strpos($output, 'email format') !== false && strpos($output, 'invalid') !== false;
        }
    ],
    'test_login_password_too_short' => [
        'desc' => 'Should reject passwords that do not meet min length bounds',
        'assert' => function($output) {
            return strpos($output, 'must be at least') !== false;
        }
    ],
    'test_login_rate_limiting' => [
        'desc' => 'Should trigger graceful 429 after exceeding request window thresholds',
        'assert' => function($output) {
            return strpos($output, '429') !== false && strpos($output, 'Rate Limit Exceeded') !== false;
        }
    ],
    'test_deposit_file_size' => [
        'desc' => 'Should reject screenshots larger than 5MB',
        'assert' => function($output) {
            return strpos($output, 'exceeds 5MB limit') !== false;
        }
    ],
    'test_deposit_invalid_ext' => [
        'desc' => 'Should reject dangerous file extensions (e.g. .php, .exe)',
        'assert' => function($output) {
            return strpos($output, 'Invalid file extension') !== false;
        }
    ],
    'test_deposit_invalid_mime' => [
        'desc' => 'Should detect and reject spoofed MIME-types (text/plain renamed to .png)',
        'assert' => function($output) {
            return strpos($output, 'Invalid file format') !== false;
        }
    ],
];

echo "========================================================\n";
echo "    GPT33 SECURITY HARDENING AUTOMATED VERIFICATION     \n";
echo "========================================================\n\n";

$passedCount = 0;
$totalCount = count($testCases);

foreach ($testCases as $case => $spec) {
    echo "Running [{$case}]...\n";
    echo "  Description: {$spec['desc']}\n";
    
    // Execute the case in an isolated PHP subprocess to handle exit/die statements gracefully
    $cmd = "php \"" . __FILE__ . "\" --run-case=" . $case;
    $output = shell_exec($cmd);
    
    if ($spec['assert']($output)) {
        echo "  \033[32m[PASS]\033[0m: Assertion satisfied.\n\n";
        $passedCount++;
    } else {
        echo "  \033[31m[FAIL]\033[0m: Assertion failed.\n";
        echo "  --- Captured Subprocess Output --- \n" . trim($output) . "\n  ---------------------------------- \n\n";
    }
}

echo "========================================================\n";
echo "Verification Results: {$passedCount} / {$totalCount} passed.\n";
echo "========================================================\n";
if ($passedCount === $totalCount) {
    exit(0);
} else {
    exit(1);
}

// ----------------------------------------------------
// Isolated Test Execution Logics
// ----------------------------------------------------
function runTestCase($caseName) {
    try {
        switch ($caseName) {
            case 'test_login_missing_fields':
                setupMockRequest('/api/auth.php', 'login', []);
                require_once __DIR__ . '/../includes/db.php';
                break;
                
            case 'test_login_unexpected_field':
                setupMockRequest('/api/auth.php', 'login', [
                    'email' => 'test@example.com',
                    'password' => '123456',
                    'hacker_param' => 'pollution'
                ]);
                require_once __DIR__ . '/../includes/db.php';
                break;
                
            case 'test_login_invalid_email':
                setupMockRequest('/api/auth.php', 'login', [
                    'email' => 'not-an-email',
                    'password' => '123456'
                ]);
                require_once __DIR__ . '/../includes/db.php';
                break;
                
            case 'test_login_password_too_short':
                setupMockRequest('/api/auth.php', 'login', [
                    'email' => 'test@example.com',
                    'password' => '123'
                ]);
                require_once __DIR__ . '/../includes/db.php';
                break;
                
            case 'test_login_rate_limiting':
                // Reset IP limits for test IP
                setupMockRequest('/api/auth.php', 'login', [
                    'email' => 'test@example.com',
                    'password' => '123456'
                ]);
                require_once __DIR__ . '/../includes/db.php';
                
                $testIp = getClientIp();
                $pdo->prepare("DELETE FROM rate_limits WHERE identifier = ?")->execute(["ip:" . $testIp]);
                
                // Sensitive limit is 15. Hit it 16 times.
                for ($i = 1; $i <= 16; $i++) {
                    checkRateLimit($pdo);
                }
                break;
                
            case 'test_deposit_file_size':
                setupMockRequest('/api/deposit.php', 'default', [
                    'method_id' => '1',
                    'amount' => '100.00',
                    'transaction_id' => 'TX123'
                ]);
                $_SESSION['user_id'] = 1; // Mock authorized session
                $_FILES['screenshot'] = [
                    'name' => 'receipt.png',
                    'type' => 'image/png',
                    'tmp_name' => tempnam(sys_get_temp_dir(), 'test_img'),
                    'error' => UPLOAD_ERR_OK,
                    'size' => 6 * 1024 * 1024 // 6MB (limit is 5MB)
                ];
                file_put_contents($_FILES['screenshot']['tmp_name'], 'dummy');
                
                require_once __DIR__ . '/../api/deposit.php';
                @unlink($_FILES['screenshot']['tmp_name']);
                break;
                
            case 'test_deposit_invalid_ext':
                setupMockRequest('/api/deposit.php', 'default', [
                    'method_id' => '1',
                    'amount' => '100.00',
                    'transaction_id' => 'TX123'
                ]);
                $_SESSION['user_id'] = 1; // Mock authorized session
                $_FILES['screenshot'] = [
                    'name' => 'exploit.php',
                    'type' => 'application/x-httpd-php',
                    'tmp_name' => tempnam(sys_get_temp_dir(), 'test_img'),
                    'error' => UPLOAD_ERR_OK,
                    'size' => 1024
                ];
                file_put_contents($_FILES['screenshot']['tmp_name'], '<?php echo "exploit";');
                
                require_once __DIR__ . '/../api/deposit.php';
                @unlink($_FILES['screenshot']['tmp_name']);
                break;
                
            case 'test_deposit_invalid_mime':
                setupMockRequest('/api/deposit.php', 'default', [
                    'method_id' => '1',
                    'amount' => '100.00',
                    'transaction_id' => 'TX123'
                ]);
                $_SESSION['user_id'] = 1; // Mock authorized session
                
                $tempFile = tempnam(sys_get_temp_dir(), 'test_img') . '.png';
                file_put_contents($tempFile, 'Plain text that is not a valid image');
                
                $_FILES['screenshot'] = [
                    'name' => 'spoofed.png',
                    'type' => 'image/png',
                    'tmp_name' => $tempFile,
                    'error' => UPLOAD_ERR_OK,
                    'size' => 1024
                ];
                
                require_once __DIR__ . '/../api/deposit.php';
                @unlink($tempFile);
                break;
                
            default:
                echo "Unknown test case '{$caseName}'.\n";
        }
    } catch (Exception $e) {
        echo 'Exception occurred: ' . $e->getMessage() . "\n";
    }
}

function setupMockRequest($scriptPath, $action, $postData) {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    $_SERVER['REQUEST_URI'] = $scriptPath . '?action=' . $action;
    $_SERVER['SCRIPT_NAME'] = $scriptPath;
    $_SERVER['REQUEST_METHOD'] = 'POST';
    unset($_SERVER['HTTP_X_FORWARDED_FOR']);
    unset($_SERVER['HTTP_CLIENT_IP']);
    $_GET['action'] = $action;
    $_POST = $postData;
    $_SERVER['REMOTE_ADDR'] = '127.9.' . rand(1, 254) . '.' . rand(1, 254); // Prevent rate limit cross-contamination
}
