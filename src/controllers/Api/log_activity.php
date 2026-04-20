<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';

// Get JSON input from Flutter
$data = json_decode(file_get_contents('php://input'), true);

$user_id = $data['user_id'] ?? 0;
$action = $data['action'] ?? '';
$platform = $data['platform'] ?? 'Mobile';

if ($user_id && $action) {
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, platform) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $action, $platform);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Activity logged successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
}