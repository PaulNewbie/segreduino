<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_once __DIR__ . '/../../config/config.php';
    
    // Figure out where the user came from (Dashboard vs Schedules page)
    $referer = $_SERVER['HTTP_REFERER'] ?? '/dashboard.php';
    // Remove old ?success or ?error parameters from the URL
    $redirect_url = strtok($referer, '?'); 

    if ($conn->connect_error) {
        header("Location: $redirect_url?error=" . urlencode("Database connection failed."));
        exit;
    }

    // Get and sanitize inputs safely
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $floor_level = trim($_POST['floor_level'] ?? '');
    $task_description = trim($_POST['task_description'] ?? '');
    $schedule_date = trim($_POST['schedule_date'] ?? '');
    $created_at = date('Y-m-d H:i:s'); 

    // Basic validation
    if (!$user_id || empty($floor_level) || empty($task_description) || empty($schedule_date)) {
        header("Location: $redirect_url?error=" . urlencode("All fields are required."));
        exit;
    }

    // Insert into schedules table
    $stmt = $conn->prepare("INSERT INTO schedules (user_id, floor_level, task_description, schedule_date, created_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $floor_level, $task_description, $schedule_date, $created_at);

    if ($stmt->execute()) {
        header("Location: $redirect_url?success=" . urlencode("Schedule successfully added."));
    } else {
        error_log("Insert Error: " . $stmt->error);
        header("Location: $redirect_url?error=" . urlencode("Error saving schedule to the database."));
    }
    
    $stmt->close();
    exit;
} else {
    header("Location: /dashboard.php");
    exit;
}
?>