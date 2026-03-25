<?php
// File: src/controllers/Api/schedules_api.php

// --- CORS Headers ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header("Content-Type: application/json; charset=UTF-8");

// --- Handle OPTIONS request ---
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --- Allow only GET method ---
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'success' => false,
        'message' => 'Only GET method is allowed'
    ]);
    exit;
}

try {
    // Connect DB
    require_once __DIR__ . '/../../config/config.php';
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    // Get user_id from query param
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

    // Assuming your table is named 'schedules'. Change if it's different in your DB.
    if ($user_id > 0) {
        // Staff → only their schedules
        $stmt = $conn->prepare("SELECT * FROM schedules WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $user_id);
    } else {
        // Admin → all schedules
        $stmt = $conn->prepare("SELECT * FROM schedules ORDER BY created_at DESC");
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }

    // Note: returning as 'tasks' key because your Flutter app's fetchSchedules 
    // method is currently expecting data['tasks']
    echo json_encode([
        'success' => true,
        'tasks' => $schedules 
    ]);

    $stmt->close();
    

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server Error: ' . $e->getMessage()
    ]);
}