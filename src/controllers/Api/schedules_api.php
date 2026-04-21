<?php
// File: src/controllers/Api/schedules_api.php

// --- CORS Headers ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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
        // Staff → join with tasks, machines, and trash_bins
        $stmt = $conn->prepare("
            SELECT 
                schedules.*, 
                users.full_name, 
                tasks.task_status,
                machines.machine_name,
                trash_bins.bin_type
            FROM schedules 
            JOIN users ON schedules.user_id = users.user_id
            LEFT JOIN tasks ON schedules.user_id = tasks.user_id 
                AND schedules.task_description = tasks.task_description 
                AND schedules.created_at = tasks.created_at
            LEFT JOIN machines ON tasks.machine_id = machines.machine_id
            LEFT JOIN trash_bins ON tasks.bin_id = trash_bins.bin_id
            WHERE schedules.user_id = ? 
            ORDER BY schedules.created_at DESC
        ");
        $stmt->bind_param("i", $user_id);
    } else {
        // Admin → all schedules
        $stmt = $conn->prepare("
            SELECT 
                schedules.*, 
                users.full_name, 
                tasks.task_status,
                machines.machine_name,
                trash_bins.bin_type
            FROM schedules 
            JOIN users ON schedules.user_id = users.user_id
            LEFT JOIN tasks ON schedules.user_id = tasks.user_id 
                AND schedules.task_description = tasks.task_description 
                AND schedules.created_at = tasks.created_at
            LEFT JOIN machines ON tasks.machine_id = machines.machine_id
            LEFT JOIN trash_bins ON tasks.bin_id = trash_bins.bin_id
            ORDER BY schedules.created_at DESC
        ");
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }

    echo json_encode([
        'success' => true,
        'tasks' => $schedules 
    ]);

    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}
?>