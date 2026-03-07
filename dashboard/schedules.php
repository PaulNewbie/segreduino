<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    echo json_encode([
        'success' => false,
        'message' => 'Only GET method is allowed'
    ]);
    exit;
}

try {
    $conn = new mysqli("localhost", "u303252282_root", "Forall.24", "u303252282_smart_waste");
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    // --- Move due schedules to tasks ---
    $query = "
        SELECT * FROM schedules
        WHERE DATE(schedule_date) <= CURDATE()
        AND (moved_to_tasks = 0 OR moved_to_tasks IS NULL)
    ";
    $dueSchedules = $conn->query($query);

    $default_bin_id = 1;      // Update to real bin_id if available
    $default_machine_id = 1;  // Update to real machine_id if available

    if ($dueSchedules && $dueSchedules->num_rows > 0) {
        while ($schedule = $dueSchedules->fetch_assoc()) {
            // Log for debugging
            error_log("Moving schedule ID: " . $schedule['schedule_id']);

            $stmt = $conn->prepare("
                INSERT INTO tasks 
                (user_id, bin_id, machine_id, task_description, task_status, created_at) 
                VALUES (?, ?, ?, ?, 'pending', ?)
            ");
            if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

            $stmt->bind_param(
                "iiiss",
                $schedule['user_id'],
                $default_bin_id,
                $default_machine_id,
                $schedule['task_description'],
                $schedule['schedule_date']
            );

            if (!$stmt->execute()) throw new Exception("Insert failed: " . $stmt->error);
            $stmt->close();

            // Mark schedule as moved
            $conn->query("UPDATE schedules SET moved_to_tasks = 1 WHERE schedule_id = " . intval($schedule['schedule_id']));
        }
    }

    // --- Get user_id from query ---
    $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

    // --- Fetch schedules ---
    if ($userId > 0) {
        // Staff (filter by user_id)
        $stmt = $conn->prepare("SELECT * FROM schedules WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
    } else {
        // Admin (all schedules)
        $result = $conn->query("SELECT * FROM schedules ORDER BY created_at DESC");
    }

    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }

    echo json_encode([
        'success' => true,
        'tasks' => $schedules
    ]);

    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server Error: ' . $e->getMessage()
    ]);
}
?>
