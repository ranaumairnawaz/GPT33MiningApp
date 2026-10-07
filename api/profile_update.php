<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/db.php';
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$full_name = $_POST['full_name'] ?? '';
$email = $_POST['email'] ?? '';

if (empty($full_name) || empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
    $stmt->execute([$full_name, $email, $user_id]);
    echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully!']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Email already in use or database error']);
}
?>
