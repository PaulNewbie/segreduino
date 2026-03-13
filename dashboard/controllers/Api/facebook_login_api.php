<?php
// filepath: /segreduino/dashboard/facebook_login_api.php

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
require_once __DIR__ . '/../../config/config.php';
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

// Read JSON input from Flutter app
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

$facebook_id = trim($data['facebook_id'] ?? '');
$full_name   = trim($data['full_name'] ?? '');
$email       = trim($data['email'] ?? '');

if (empty($facebook_id) || empty($full_name) || empty($email)) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
    exit;
}

// 1. Check if user exists by facebook_id
$stmt = $conn->prepare("SELECT * FROM users WHERE facebook_id = ?");
$stmt->bind_param("s", $facebook_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // ✅ Facebook account already linked
    $user = $result->fetch_assoc();
} else {
    // 2. Check if user exists by email
    $stmt2 = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt2->bind_param("s", $email);
    $stmt2->execute();
    $result2 = $stmt2->get_result();

    if ($result2->num_rows > 0) {
        // ✅ Existing email account → link Facebook ID + upgrade role to staff
        $user = $result2->fetch_assoc();
        $update = $conn->prepare("UPDATE users SET facebook_id = ?, role = 'staff' WHERE user_id = ?");
        $update->bind_param("si", $facebook_id, $user['user_id']);
        $update->execute();

        $user['facebook_id'] = $facebook_id;
        $user['role'] = 'staff';
    } else {
        // ✅ New user → insert as staff
        $stmt3 = $conn->prepare("INSERT INTO users (facebook_id, full_name, email, role) VALUES (?, ?, ?, 'staff')");
        $stmt3->bind_param("sss", $facebook_id, $full_name, $email);

        if ($stmt3->execute()) {
            $user_id = $stmt3->insert_id;
            $user = [
                'user_id'     => $user_id,
                'facebook_id' => $facebook_id,
                'full_name'   => $full_name,
                'email'       => $email,
                'role'        => 'staff'
            ];
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to register new user']);
            exit;
        }
    }
}

// ✅ Always return role as staff
$user['role'] = 'staff';

// Remove sensitive fields before returning (just in case)
unset($user['password']);

echo json_encode([
    'success' => true,
    'user'    => $user
]);

$stmt->close();

?>
