<?php
// filepath: /segreduino/dashboard/login_api.php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=UTF-8');

// Handle preflight OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Connect to your hosting MySQL database
$conn = new mysqli("localhost", "u303252282_root", "Forall.24", "u303252282_smart_waste");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

// Read JSON input from Flutter app
$rawInput = file_get_contents("php://input");
$input = json_decode($rawInput, true);
$username = trim($input['username'] ?? '');
$password = trim($input['password'] ?? '');

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Incorrect username or password']);
    exit;
}

// Fetch user by username (or email if you want to allow email login)
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user   = $result->fetch_assoc();

$ip = $_SERVER['REMOTE_ADDR'] ?? '';   // ✅ capture client IP

if ($user && password_verify($password, $user['password'])) {

// ✅ UPDATE user status to active
$upd = $conn->prepare("UPDATE users SET status = 'active' WHERE user_id = ?");
$upd->bind_param("i", $user['user_id']);
$upd->execute();
$upd->close();


    // ✅ Record SUCCESS login
    $log = $conn->prepare("INSERT INTO login_history (user_id, login_time, status, ip_address)
                           VALUES (?, NOW(), 'success', ?)");
    $log->bind_param("is", $user['user_id'], $ip);
    $log->execute();
    $log->close();

    unset($user['password']); // Remove password from response
    echo json_encode(['success' => true, 'user' => $user]);

} else {

    // ✅ Record FAILED login (user_id = 0 if unknown)
    $uid = $user['user_id'] ?? 0;
    $log = $conn->prepare("INSERT INTO login_history (user_id, login_time, status, ip_address)
                           VALUES (?, NOW(), 'failed', ?)");
    $log->bind_param("is", $uid, $ip);
    $log->execute();
    $log->close();

    echo json_encode(['success' => false, 'message' => 'Incorrect username or password']);
}

$stmt->close();
$conn->close();
?>
