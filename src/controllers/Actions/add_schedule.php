<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get user_id directly from POST
    $user_id = trim($_POST['user_id']);
    $floor_level = trim($_POST['floor_level']);
    $task_description = trim($_POST['task_description']);
    $schedule_date = $_POST['schedule_date'];
    $created_at = date('Y-m-d H:i:s'); // current time

    // Basic validation
    if (empty($user_id) || empty($floor_level) || empty($task_description) || empty($schedule_date)) {
        die("All fields are required.");
    }

    // Database connection
    require_once __DIR__ . '/../../config/config.php';
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Insert into schedules table
    $stmt = $conn->prepare("INSERT INTO schedules (user_id, floor_level, task_description, schedule_date, created_at) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("issss", $user_id, $floor_level, $task_description, $schedule_date, $created_at);

    if ($stmt->execute()) {
        $stmt->close();
        
        header("Location: /dashboard.php?success=schedule_added");
        exit;
    } else {
        echo "Error inserting schedule: " . $stmt->error;
        $stmt->close();
        
        exit;
    }
} else {
    // Not a POST request
    header("Location: dashboard.php");
    exit;
}
?>