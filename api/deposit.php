<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/db.php';
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Check if POST is empty (usually means upload limit exceeded)
if (empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'File too large. Maximum size is ' . ini_get('upload_max_filesize')]);
    exit;
}

$user_id = $_SESSION['user_id'];
$method_id = $_POST['method_id'] ?? 0;
$amount = (float)($_POST['amount'] ?? 0);
$tid = $_POST['transaction_id'] ?? $_POST['tid'] ?? '';

// Handle Screenshot Upload
$screenshot_path = null;
if (isset($_FILES['screenshot'])) {
    if ($_FILES['screenshot']['error'] !== UPLOAD_ERR_OK) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Upload failed with error code: ' . $_FILES['screenshot']['error']]);
        exit;
    }
    
    // Check file size (5MB limit)
    $max_size = 5 * 1024 * 1024;
    if ($_FILES['screenshot']['size'] > $max_size) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Screenshot file size exceeds 5MB limit.']);
        exit;
    }
    
    // Extract file extension and lowercase it
    $file_ext = strtolower(pathinfo($_FILES['screenshot']['name'], PATHINFO_EXTENSION));
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($file_ext, $allowed_exts)) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Invalid file extension. Only JPG, JPEG, PNG, and GIF are allowed.']);
        exit;
    }
    
    // Perform robust MIME-type verification using finfo (prevents spoofing)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $_FILES['screenshot']['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($mime_type, $allowed_mimes)) {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Invalid file format. The uploaded file is not a valid image.']);
        exit;
    }
    
    // Sanitize extension to ensure it aligns with the MIME type (prevent double extension / double dot attacks)
    $clean_ext = 'jpg';
    if ($mime_type === 'image/png') {
        $clean_ext = 'png';
    } elseif ($mime_type === 'image/gif') {
        $clean_ext = 'gif';
    } else {
        $clean_ext = 'jpg'; // default jpeg / jpg
    }
    
    $upload_dir = __DIR__ . '/../uploads/screenshots/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true); // Keep permissions secure
    }
    
    // Generate secure filename using a clean random slug (ignores original name to prevent path traversal/XSS)
    $file_name = 'dep_' . time() . '_' . bin2hex(random_bytes(16)) . '.' . $clean_ext;
    $target_path = $upload_dir . $file_name;
    
    if (move_uploaded_file($_FILES['screenshot']['tmp_name'], $target_path)) {
        $screenshot_path = 'uploads/screenshots/' . $file_name;
    } else {
        ob_end_clean();
        echo json_encode(['status' => 'error', 'message' => 'Failed to save the uploaded screenshot.']);
        exit;
    }
}

// Fetch method
$stmt = $pdo->prepare("SELECT name FROM payment_methods WHERE id = ? AND is_active = 1");
$stmt->execute([$method_id]);
$method = $stmt->fetch();

if (!$method) {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'Invalid payment method or session expired']);
    exit;
}

if ($amount <= 0) {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'Invalid amount']);
    exit;
}

if (!$screenshot_path) {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'Payment screenshot is required']);
    exit;
}

// Log pending transaction
$stmt = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, method_name, account_details, screenshot, status) VALUES (?, 'deposit', ?, ?, ?, ?, 'pending')");
if ($stmt->execute([$user_id, $amount, $method['name'], $tid, $screenshot_path])) {
    // Notify Admin
    $notif_stmt = $pdo->prepare("INSERT INTO admin_notifications (type, user_id, message, link) VALUES ('deposit', ?, ?, 'index.php?route=deposits')");
    $notif_stmt->execute([$user_id, "New deposit request of $" . number_format($amount, 2) . " via " . $method['name']]);
    
    ob_end_clean();
    echo json_encode(['status' => 'success', 'message' => 'Deposit request submitted!']);
} else {
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>
