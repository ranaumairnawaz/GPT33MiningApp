<?php
/**
 * GPT33 Security Hardening Framework
 * Implements OWASP-compliant Rate Limiting, Input Validation, Sanitization, 
 * Rejection of Unexpected Fields, and Secure Environment Configuration.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Safe IP Resolver (incorporates reverse proxies, Cloudflare, etc.)
function getClientIp() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($parts[0]);
    } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    
    // Fallback if IP resolved incorrectly or contains invalid syntax
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return '0.0.0.0';
    }
    return $ip;
}

// 2. Zero-Dependency Environment Variable Loader
function loadEnv($envPath) {
    if (!file_exists($envPath)) {
        return;
    }
    
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Skip comments and empty lines
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        
        // Split on first '='
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $val = trim($parts[1]);
            
            // Strip surrounding quotes
            if (preg_match('/^"(.*)"$/', $val, $matches) || preg_match("/^'(.*)'$/", $val, $matches)) {
                $val = $matches[1];
            }
            
            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("{$key}={$val}");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}

// 3. Database-Backed Rate Limiting System (IP & User-based)
function checkRateLimit($pdo) {
    if (php_sapi_name() === 'cli' && !defined('BYPASS_CLI_CHECK')) {
        return;
    }
    $ip = getClientIp();
    $userId = $_SESSION['user_id'] ?? null;
    
    // Identify current endpoint/route to specify rate limits
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $route = $_GET['route'] ?? '';
    
    $isSensitive = false;
    // Auth endpoints (login/register) and sensitive admin actions are heavily restricted
    if (
        strpos($route, 'login') !== false || 
        strpos($route, 'register') !== false || 
        strpos($requestUri, 'api/auth') !== false ||
        strpos($requestUri, 'api/admin_actions') !== false
    ) {
        $isSensitive = true;
    }
    
    $endpoint = $isSensitive ? 'sensitive' : 'general';
    $window = 60; // 1 minute fixed window
    
    // Rate limit thresholds (Sensible limits following OWASP standard guidelines)
    if ($isSensitive) {
        $ipLimit = 15;   // Max 15 sensitive requests per minute per IP
        $userLimit = 10; // Max 10 sensitive requests per minute per User (if logged in)
    } else {
        $ipLimit = 100;  // Max 100 general requests per minute per IP
        $userLimit = 200;// Max 200 general requests per minute per User
    }
    
    $now = time();
    
    // Occasionally purge expired rate limits from database (1 in 50 chance)
    if (rand(1, 50) === 1) {
        try {
            $stmt = $pdo->prepare("DELETE FROM rate_limits WHERE window_end < ?");
            $stmt->execute([$now]);
        } catch (Exception $e) {
            // Fail silently to prevent database exceptions from crashing client
        }
    }
    
    // Check IP rate limit
    $ipHits = recordAndCheckLimit($pdo, "ip:" . $ip, $endpoint, $ipLimit, $window, $now);
    if ($ipHits > $ipLimit) {
        triggerRateLimitResponse($window);
    }
    
    // Check User rate limit (if authenticated)
    if ($userId) {
        $userHits = recordAndCheckLimit($pdo, "user:" . $userId, $endpoint, $userLimit, $window, $now);
        if ($userHits > $userLimit) {
            triggerRateLimitResponse($window);
        }
    }
}

function recordAndCheckLimit($pdo, $identifier, $endpoint, $limit, $window, $now) {
    try {
        // Atomic insert or update with conditional hits reset if window expired
        $windowEnd = $now + $window;
        $stmt = $pdo->prepare("
            INSERT INTO rate_limits (identifier, endpoint, hits, window_end)
            VALUES (?, ?, 1, ?)
            ON DUPLICATE KEY UPDATE
                hits = IF(window_end < ?, 1, hits + 1),
                window_end = IF(window_end < ?, ?, window_end)
        ");
        
        $stmt->execute([
            $identifier,
            $endpoint,
            $windowEnd,
            $now, // Check if current time is past window_end to reset hits
            $now, // Check if current time is past window_end to reset window_end
            $windowEnd
        ]);
        
        // Fetch current hit count
        $stmt = $pdo->prepare("SELECT hits FROM rate_limits WHERE identifier = ? AND endpoint = ?");
        $stmt->execute([$identifier, $endpoint]);
        return $stmt->fetchColumn() ?: 1;
    } catch (Exception $e) {
        // Fallback to allow request in case of rate limit storage issues (failsafe)
        return 1;
    }
}

function triggerRateLimitResponse($window) {
    header('HTTP/1.1 429 Too Many Requests');
    header('Retry-After: ' . $window);
    
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') 
              || isset($_GET['ajax']) 
              || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Too many requests. Please slow down and try again in ' . $window . ' seconds.'
        ]);
        exit;
    }
    
    // Elegant, custom client-facing 429 HTML Page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <title>Too Many Requests - GPT33</title>
        <style>
            body {
                background: #0f172a;
                color: #f1f5f9;
                font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100vh;
                margin: 0;
            }
            .card {
                text-align: center;
                padding: 45px;
                background: rgba(30, 41, 59, 0.7);
                border-radius: 28px;
                border: 1px solid rgba(255, 255, 255, 0.08);
                max-width: 440px;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(15px);
            }
            h1 {
                color: #ef4444;
                font-size: 2.5rem;
                margin: 0 0 10px 0;
                font-weight: 800;
                letter-spacing: -0.05em;
            }
            h2 {
                font-size: 1.25rem;
                margin: 0 0 15px 0;
                color: #f8fafc;
            }
            p {
                color: #94a3b8;
                font-size: 0.95rem;
                line-height: 1.6;
                margin: 0 0 25px 0;
            }
            .btn {
                display: inline-block;
                padding: 12px 30px;
                background: #6366f1;
                color: white;
                text-decoration: none;
                border-radius: 14px;
                font-weight: 600;
                font-size: 0.9rem;
                transition: all 0.2s ease;
                box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);
            }
            .btn:hover {
                background: #4f46e5;
                transform: translateY(-1px);
            }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>429</h1>
            <h2>Rate Limit Exceeded</h2>
            <p>Your connection has transmitted an excessive amount of requests. Our automated security protocols have temporarily locked further access.</p>
            <a href="javascript:location.reload()" class="btn">Retry Access</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 4. Strict Schema-Based Request Inputs Validator & Sanitizer
// Strictly type-checks, validates length and bounds, and rejects unexpected inputs to prevent injection & parameter pollution.
$schemas = [
    'api/auth' => [
        'login' => [
            'email'    => ['type' => 'email', 'required' => true, 'max' => 255],
            'password' => ['type' => 'string', 'required' => true, 'min' => 6, 'max' => 100],
        ],
        'register' => [
            'full_name'        => ['type' => 'string', 'required' => true, 'min' => 2, 'max' => 100, 'sanitize' => 'string'],
            'email'            => ['type' => 'email', 'required' => true, 'max' => 255],
            'password'         => ['type' => 'string', 'required' => true, 'min' => 6, 'max' => 100],
            'confirm_password' => ['type' => 'string', 'required' => true, 'min' => 6, 'max' => 100],
            'referral_code'    => ['type' => 'string', 'required' => false, 'max' => 20, 'sanitize' => 'alphanumeric'],
        ]
    ],
    'api/deposit' => [
        'default' => [
            'method_id'      => ['type' => 'int', 'required' => true, 'min' => 1],
            'amount'         => ['type' => 'float', 'required' => true, 'min' => 0.01],
            'transaction_id' => ['type' => 'string', 'required' => false, 'max' => 255, 'sanitize' => 'alphanumeric_spaced'],
            'tid'            => ['type' => 'string', 'required' => false, 'max' => 255, 'sanitize' => 'alphanumeric_spaced'],
        ]
    ],
    'api/withdraw' => [
        'default' => [
            'method_id'      => ['type' => 'int', 'required' => true, 'min' => 1],
            'amount'         => ['type' => 'float', 'required' => true, 'min' => 0.01],
            'account_name'   => ['type' => 'string', 'required' => true, 'min' => 2, 'max' => 100, 'sanitize' => 'string'],
            'account_number' => ['type' => 'string', 'required' => true, 'min' => 2, 'max' => 100, 'sanitize' => 'alphanumeric_spaced'],
        ]
    ],
    'api/invest' => [
        'default' => [
            'plan_id' => ['type' => 'int', 'required' => true, 'min' => 1],
            'amount'  => ['type' => 'float', 'required' => true, 'min' => 0.01],
        ]
    ],
    'api/profile_update' => [
        'default' => [
            'full_name' => ['type' => 'string', 'required' => true, 'min' => 2, 'max' => 100, 'sanitize' => 'string'],
            'email'     => ['type' => 'email', 'required' => true, 'max' => 255],
        ]
    ],
    'api/chat' => [
        'send' => [
            'message' => ['type' => 'string', 'required' => true, 'min' => 1, 'max' => 2000, 'sanitize' => 'textarea'],
        ],
        'admin_send' => [
            'receiver_id' => ['type' => 'int', 'required' => true, 'min' => 1],
            'message'     => ['type' => 'string', 'required' => true, 'min' => 1, 'max' => 2000, 'sanitize' => 'textarea'],
        ]
    ],
    'api/admin_actions' => [
        'authorize_admin' => [
            'email' => ['type' => 'email', 'required' => true, 'max' => 255]
        ],
        'adjust_balance' => [
            'user_id'      => ['type' => 'int', 'required' => true, 'min' => 1],
            'balance_type' => ['type' => 'string', 'required' => true, 'max' => 50, 'sanitize' => 'alphanumeric'],
            'adj_type'     => ['type' => 'string', 'required' => true, 'max' => 50, 'sanitize' => 'alphanumeric'],
            'amount'       => ['type' => 'float', 'required' => true, 'min' => 0.01],
            'note'         => ['type' => 'string', 'required' => false, 'max' => 255, 'sanitize' => 'string']
        ],
        'save_method' => [
            'id'              => ['type' => 'int', 'required' => false],
            'type'            => ['type' => 'string', 'required' => true, 'max' => 50, 'sanitize' => 'alphanumeric'],
            'name'            => ['type' => 'string', 'required' => true, 'max' => 100, 'sanitize' => 'string'],
            'details'         => ['type' => 'string', 'required' => true, 'max' => 5000, 'sanitize' => 'string'],
            'min_amount'      => ['type' => 'float', 'required' => true, 'min' => 0.00],
            'max_amount'      => ['type' => 'float', 'required' => true, 'min' => 0.00],
            'is_active'       => ['type' => 'int', 'required' => true, 'min' => 0, 'max' => 1],
            'currency'        => ['type' => 'string', 'required' => false, 'max' => 20, 'sanitize' => 'alphanumeric'],
            'rate'            => ['type' => 'float', 'required' => false, 'min' => 0.00],
            'copyable_fields' => ['type' => 'string', 'required' => false, 'max' => 2000, 'sanitize' => 'json'],
            'fixed_fee'       => ['type' => 'float', 'required' => false, 'min' => 0.00],
            'percent_fee'     => ['type' => 'float', 'required' => false, 'min' => 0.00]
        ],
        'save_plan' => [
            'id'          => ['type' => 'int', 'required' => false],
            'name'        => ['type' => 'string', 'required' => true, 'max' => 100, 'sanitize' => 'string'],
            'hourly_rate' => ['type' => 'float', 'required' => true, 'min' => 0.00],
            'min_invest'  => ['type' => 'float', 'required' => true, 'min' => 0.00],
            'max_invest'  => ['type' => 'float', 'required' => true, 'min' => 0.00],
            'is_active'   => ['type' => 'int', 'required' => true, 'min' => 0, 'max' => 1]
        ],
        'save_tier' => [
            'id'             => ['type' => 'int', 'required' => false],
            'name'           => ['type' => 'string', 'required' => true, 'max' => 100, 'sanitize' => 'string'],
            'min_investment' => ['type' => 'float', 'required' => true, 'min' => 0.00],
            'icon'           => ['type' => 'string', 'required' => true, 'max' => 100, 'sanitize' => 'string'],
            'color'          => ['type' => 'string', 'required' => true, 'max' => 20, 'sanitize' => 'string']
        ],
        'post_to_feed' => [
            'feed_message' => ['type' => 'string', 'required' => true, 'max' => 5000, 'sanitize' => 'textarea']
        ],
        'save_settings' => [
            'site_name'           => ['type' => 'string', 'required' => true, 'max' => 255, 'sanitize' => 'string'],
            'currency_rate'       => ['type' => 'float', 'required' => true, 'min' => 0.0001, 'max' => 1000000.0],
            'whatsapp_link'       => ['type' => 'string', 'required' => false, 'max' => 255, 'sanitize' => 'string'],
            'telegram_link'       => ['type' => 'string', 'required' => false, 'max' => 255, 'sanitize' => 'string'],
            'ref_level_1'         => ['type' => 'float', 'required' => true, 'min' => 0.00, 'max' => 100.00],
            'ref_level_2'         => ['type' => 'float', 'required' => true, 'min' => 0.00, 'max' => 100.00],
            'ref_level_3'         => ['type' => 'float', 'required' => true, 'min' => 0.00, 'max' => 100.00],
            'ref_level_4'         => ['type' => 'float', 'required' => true, 'min' => 0.00, 'max' => 100.00],
            'ref_level_5'         => ['type' => 'float', 'required' => true, 'min' => 0.00, 'max' => 100.00],
            'is_maintenance'       => ['type' => 'int', 'required' => false, 'min' => 0, 'max' => 1],
            'announcement_text'   => ['type' => 'string', 'required' => false, 'max' => 5000, 'sanitize' => 'textarea'],
            'announcement_active' => ['type' => 'int', 'required' => false, 'min' => 0, 'max' => 1],
            'deposit_bonus'       => ['type' => 'float', 'required' => true, 'min' => 0.00, 'max' => 100.00],
            'broadcast_followers_base' => ['type' => 'int', 'required' => true, 'min' => 0, 'max' => 1000000]
        ]
    ]
];
$GLOBALS['schemas'] = $schemas;

function validateRequestInputs() {
    if (php_sapi_name() === 'cli' && !defined('BYPASS_CLI_CHECK')) {
        return;
    }
    global $schemas;
    
    // Resolve current endpoint file path
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $endpoint = '';
    
    if (strpos($scriptName, 'api/auth.php') !== false) {
        $endpoint = 'api/auth';
    } elseif (strpos($scriptName, 'api/deposit.php') !== false) {
        $endpoint = 'api/deposit';
    } elseif (strpos($scriptName, 'api/withdraw.php') !== false) {
        $endpoint = 'api/withdraw';
    } elseif (strpos($scriptName, 'api/invest.php') !== false) {
        $endpoint = 'api/invest';
    } elseif (strpos($scriptName, 'api/profile_update.php') !== false) {
        $endpoint = 'api/profile_update';
    } elseif (strpos($scriptName, 'api/chat.php') !== false) {
        $endpoint = 'api/chat';
    } elseif (strpos($scriptName, 'api/admin_actions.php') !== false) {
        $endpoint = 'api/admin_actions';
    } else {
        // Check router parameter fallback for index routing
        $route = $_GET['route'] ?? '';
        if (isset($schemas[$route])) {
            $endpoint = $route;
        }
    }
    
    // If no schema is defined for this endpoint, bypass validation
    if (empty($schemas[$endpoint])) {
        return;
    }
    
    $action = $_GET['action'] ?? 'default';
    if (!isset($schemas[$endpoint][$action])) {
        // Fallback to default action
        $action = 'default';
    }
    
    if (!isset($schemas[$endpoint][$action])) {
        return; // Bypass if action not mapped
    }
    
    $schema = $schemas[$endpoint][$action];
    $inputData = $_POST;
    
    // Rule: REJECT unexpected fields in POST data (Defense against Parameter Pollution/HTTP Injection)
    foreach ($inputData as $key => $value) {
        if (!isset($schema[$key])) {
            triggerValidationError("Strict validation rejected parameter: unexpected key '" . htmlspecialchars($key) . "' detected.");
        }
    }
    
    // Rule: Validate type, required presence, bounds, and sanitize input values
    foreach ($schema as $key => $rules) {
        $exists = isset($inputData[$key]) && $inputData[$key] !== '';
        
        // Verify required presence
        if (!empty($rules['required']) && !$exists) {
            triggerValidationError("Validation failed: missing required parameter '" . htmlspecialchars($key) . "'.");
        }
        
        if ($exists) {
            $val = $inputData[$key];
            
            // 1. Strict Type Validation
            if ($rules['type'] === 'int') {
                if (!filter_var($val, FILTER_VALIDATE_INT) && !is_numeric($val)) {
                    triggerValidationError("Validation failed: field '" . htmlspecialchars($key) . "' must be a valid integer.");
                }
                $val = (int)$val;
            } elseif ($rules['type'] === 'float') {
                if (!filter_var($val, FILTER_VALIDATE_FLOAT) && !is_numeric($val)) {
                    triggerValidationError("Validation failed: field '" . htmlspecialchars($key) . "' must be a valid floating-point number.");
                }
                $val = (float)$val;
            } elseif ($rules['type'] === 'email') {
                if (!filter_var($val, FILTER_VALIDATE_EMAIL)) {
                    triggerValidationError("Validation failed: email format for field '" . htmlspecialchars($key) . "' is invalid.");
                }
            }
            
            // 2. Strict Length Limits & Bounds Check
            if (isset($rules['min'])) {
                if (is_numeric($val) && ($rules['type'] === 'int' || $rules['type'] === 'float')) {
                    if ($val < $rules['min']) {
                        triggerValidationError("Validation failed: field '" . htmlspecialchars($key) . "' cannot be less than " . $rules['min'] . ".");
                    }
                } else {
                    if (strlen((string)$val) < $rules['min']) {
                        triggerValidationError("Validation failed: field '" . htmlspecialchars($key) . "' must be at least " . $rules['min'] . " characters long.");
                    }
                }
            }
            
            if (isset($rules['max'])) {
                if (is_numeric($val) && ($rules['type'] === 'int' || $rules['type'] === 'float')) {
                    if ($val > $rules['max']) {
                        triggerValidationError("Validation failed: field '" . htmlspecialchars($key) . "' cannot exceed " . $rules['max'] . ".");
                    }
                } else {
                    if (strlen((string)$val) > $rules['max']) {
                        triggerValidationError("Validation failed: field '" . htmlspecialchars($key) . "' exceeds maximum allowed length of " . $rules['max'] . " characters.");
                    }
                }
            }
            
            // 3. XSS and SQL Injection Defense (Sanitize $_POST values)
            if (isset($rules['sanitize'])) {
                if ($rules['sanitize'] === 'string') {
                    $_POST[$key] = htmlspecialchars(strip_tags((string)$val), ENT_QUOTES, 'UTF-8');
                } elseif ($rules['sanitize'] === 'alphanumeric') {
                    $_POST[$key] = preg_replace('/[^a-zA-Z0-9]/', '', (string)$val);
                } elseif ($rules['sanitize'] === 'alphanumeric_spaced') {
                    $_POST[$key] = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', (string)$val);
                } elseif ($rules['sanitize'] === 'textarea') {
                    // Retain basic structure but escape dangerous tags
                    $_POST[$key] = htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
                } elseif ($rules['sanitize'] === 'json') {
                    $decoded = json_decode((string)$val, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        triggerValidationError("Validation failed: field '" . htmlspecialchars($key) . "' must be a valid JSON string.");
                    }
                    $_POST[$key] = (string)$val;
                }
            }
        }
    }
}

function triggerValidationError($message) {
    header('HTTP/1.1 400 Bad Request');
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => $message
    ]);
    exit;
}
?>
