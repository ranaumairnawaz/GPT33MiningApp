<?php
define('BYPASS_CLI_CHECK', true);
require_once __DIR__ . '/../includes/security.php';

echo "Testing valid JSON input...\n";
$_SERVER['SCRIPT_NAME'] = '/api/admin_actions.php';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['action'] = 'save_method';

$_POST = [
    'type' => 'deposit',
    'name' => 'Test Gateway',
    'details' => 'Test Details',
    'min_amount' => '10.00',
    'max_amount' => '1000.00',
    'is_active' => '1',
    'copyable_fields' => '[{"label":"Bitcoin Address","value":"1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa"}]'
];

validateRequestInputs();
echo "Parsed Output:\n" . $_POST['copyable_fields'] . "\n\n";

$decoded = json_decode($_POST['copyable_fields'], true);
if (json_last_error() === JSON_ERROR_NONE && $decoded[0]['label'] === 'Bitcoin Address') {
    echo "\033[32m[PASS]\033[0m: Valid JSON was successfully sanitized and retained original double quotes!\n\n";
} else {
    echo "\033[31m[FAIL]\033[0m: Valid JSON failed or quotes were escaped!\n\n";
}

echo "Testing invalid JSON input...\n";
$_POST['copyable_fields'] = '[{"label": "invalid json",}'; // Trailing comma/incomplete brackets

try {
    // Redefining standard triggerValidationError behavior for the test script
    // so we can catch it instead of calling exit
    function triggerValidationErrorMock($message) {
        throw new Exception($message);
    }
    
    // We modify includes/security.php behavior locally for testing
    // Or we just test it inside a try-catch knowing it prints header and exits
    echo "Running validation (should output Bad Request JSON and exit):\n";
    validateRequestInputs();
} catch (Exception $e) {
    echo "Caught exception: " . $e->getMessage() . "\n";
}
