<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

if (!isset($data['task_id']) || empty($data['task_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid or missing task_id']);
    exit;
}

$task_id = intval($data['task_id']);

require_once __DIR__ . '/../../config/config.php';
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// ✅ Force Philippine Timezone
date_default_timezone_set('Asia/Manila');
$current_time = date('Y-m-d H:i:s');

// ✅ Update the status and insert the exact PHP-generated time
$stmt = $conn->prepare("UPDATE tasks SET task_status = 'Completed', completed_at = ? WHERE task_id = ? AND task_status != 'Completed'");
$stmt->bind_param("si", $current_time, $task_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Task marked as completed']);
} else {
    echo json_encode(['success' => false, 'message' => 'Task already completed or update failed']);
}

$stmt->close();
?>