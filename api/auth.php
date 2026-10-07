<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../includes/db.php';
error_reporting(0);
ini_set('display_errors', 0);
error_reporting(0);
ini_set('display_errors', 0);


header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'];

// Check for IP ban
$stmt = $pdo->prepare("SELECT 1 FROM banned_ips WHERE ip_address = ?");
$stmt->execute([$ip]);
if ($stmt->fetch()) {
    echo json_encode(['status' => 'error', 'message' => 'Your IP address has been blocked from accessing this platform.']);
    exit;
}

if ($action === 'login') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['is_banned']) {
            echo json_encode(['status' => 'error', 'message' => 'Your account has been suspended. Please contact support.']);
            exit;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['is_admin'] = $user['is_admin'];

        // Update IP
        $stmt = $pdo->prepare("UPDATE users SET last_login_ip = ? WHERE id = ?");
        $stmt->execute([$ip, $user['id']]);

        echo json_encode(['status' => 'success', 'redirect' => $user['is_admin'] ? '/admin/dashboard' : '/dashboard']);

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
    }
} elseif ($action === 'register') {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $referral_code = $_POST['referral_code'] ?? '';

    if ($password !== $confirm_password) {
        echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
        exit;
    }

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Email address is already registered.']);
        exit;
    }

    $ref_id = null;
    if (!empty($referral_code)) {
        // Look up referrer by alphanumeric code
        $stmt = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
        $stmt->execute([$referral_code]);
        $ref_user = $stmt->fetch();
        if ($ref_user)
            $ref_id = $ref_user['id'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    // Generate unique 8-digit code for new user
    $new_ref_code = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);

    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, referral_code, referred_by, last_login_ip) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$full_name, $email, $hash, $new_ref_code, $ref_id, $ip])) {
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['full_name'] = $full_name;
        $_SESSION['is_admin'] = 0;
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Registration failed. Please try again.']);
    }
}
?>