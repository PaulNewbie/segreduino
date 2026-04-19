<?php
// File: /segreduino/dashboard/tasks_api.php

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
    http_response_code(405); 
    echo json_encode(['success' => false, 'message' => 'Only GET method is allowed']);
    exit;
}

try {
    require_once __DIR__ . '/../../config/config.php';
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

    if ($user_id > 0) {
        // Staff → only their tasks, joined with machines and bins
        $stmt = $conn->prepare("
            SELECT tasks.*, machines.machine_name, trash_bins.bin_type 
            FROM tasks 
            LEFT JOIN machines ON tasks.machine_id = machines.machine_id
            LEFT JOIN trash_bins ON tasks.bin_id = trash_bins.bin_id
            WHERE tasks.user_id = ? 
            ORDER BY tasks.created_at DESC
        ");
        $stmt->bind_param("i", $user_id);
    } else {
        // Admin → all tasks, joined with machines, bins, AND users
        $stmt = $conn->prepare("
            SELECT tasks.*, machines.machine_name, trash_bins.bin_type, users.full_name 
            FROM tasks 
            LEFT JOIN machines ON tasks.machine_id = machines.machine_id
            LEFT JOIN trash_bins ON tasks.bin_id = trash_bins.bin_id
            LEFT JOIN users ON tasks.user_id = users.user_id
            ORDER BY tasks.created_at DESC
        ");
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $tasks = [];
    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row;
    }

    echo json_encode([
        'success' => true,
        'tasks' => $tasks
    ]);

    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}
?>