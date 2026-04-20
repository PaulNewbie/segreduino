<?php
// src/controllers/Actions/add_schedule.php

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    require_once __DIR__ . '/../../config/config.php';
    
    $referer = $_SERVER['HTTP_REFERER'] ?? '/dashboard.php';
    $redirect_url = strtok($referer, '?'); 

    if ($conn->connect_error) {
        header("Location: $redirect_url?error=" . urlencode("Database connection failed."));
        exit;
    }

    // Safely grab the data from the form
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $machine_id = isset($_POST['machine_id']) ? (int)$_POST['machine_id'] : 0;
    $bin_id = isset($_POST['bin_id']) ? (int)$_POST['bin_id'] : 0;
    $floor_level = trim($_POST['floor_level'] ?? '');
    $task_description = trim($_POST['task_description'] ?? '');
    
    // Routine Variables
    $recurrence_pattern = trim($_POST['recurrence_pattern'] ?? 'weekly');
    $day_of_week = trim($_POST['day_of_week'] ?? '');
    $schedule_time = trim($_POST['schedule_time'] ?? '08:00:00');
    
    // Give the database a placeholder date so it doesn't crash!
    $schedule_date = date('Y-m-d'); 
    $created_at = date('Y-m-d H:i:s'); 

    // Detailed Validation: Find exactly what is missing
    $missing_fields = [];
    if ($user_id === 0) $missing_fields[] = "Assign Staff";
    if ($machine_id === 0) $missing_fields[] = "Machine/Kiosk";
    if ($bin_id === 0) $missing_fields[] = "Trash Bin";
    if (empty($task_description)) $missing_fields[] = "Task Description";

    if (!empty($missing_fields)) {
        $error_msg = "Please fill in: " . implode(", ", $missing_fields);
        header("Location: $redirect_url?error=" . urlencode($error_msg));
        exit;
    }

    // 1. Insert the Schedule template
    $stmt = $conn->prepare("INSERT INTO schedules (user_id, machine_id, bin_id, floor_level, task_description, recurrence_pattern, day_of_week, schedule_time, schedule_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("iiisssssss", $user_id, $machine_id, $bin_id, $floor_level, $task_description, $recurrence_pattern, $day_of_week, $schedule_time, $schedule_date, $created_at);
    
    if ($stmt->execute()) {
        
        // --- NEW CODE: AUTOMATICALLY CREATE A TASK ---
        $task_status = 'pending'; // Default status for new tasks
        
        $task_stmt = $conn->prepare("INSERT INTO tasks (user_id, bin_id, machine_id, task_description, task_status, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        $task_stmt->bind_param("iiisss", $user_id, $bin_id, $machine_id, $task_description, $task_status, $created_at);
        
        // Execute the task insertion, but we don't necessarily need to block the schedule success if it fails, 
        // though typically you'd log it if it failed.
        if (!$task_stmt->execute()) {
             error_log("Auto-Task Insert Error: " . $task_stmt->error);
        }
        $task_stmt->close();
        // ---------------------------------------------

        header("Location: $redirect_url?success=" . urlencode("Routine schedule created and added to Tasks."));
    } else {
        error_log("Schedule Insert Error: " . $stmt->error);
        header("Location: $redirect_url?error=" . urlencode("Database error: Could not save schedule."));
    }
    
    $stmt->close();
    exit;
} else {
    header("Location: /dashboard.php");
    exit;
}
?>