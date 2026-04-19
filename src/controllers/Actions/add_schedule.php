<?php
// src/controllers/Actions/add_schedule.php

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
    $machine_id = filter_input(INPUT_POST, 'machine_id', FILTER_VALIDATE_INT);
    $bin_id = filter_input(INPUT_POST, 'bin_id', FILTER_VALIDATE_INT);
    $floor_level = trim($_POST['floor_level'] ?? '');
    $task_description = trim($_POST['task_description'] ?? '');
    $schedule_date = trim($_POST['schedule_date'] ?? '');
    $created_at = date('Y-m-d H:i:s'); 
    $task_status = 'Pending';

    // Basic validation
    if (!$user_id || !$machine_id || !$bin_id || empty($floor_level) || empty($task_description) || empty($schedule_date)) {
        header("Location: $redirect_url?error=" . urlencode("All fields, including machine and bin, are required."));
        exit;
    }

    // Start Transaction to ensure BOTH inserts succeed, or neither do
    $conn->begin_transaction();

    try {
        // 1. Insert into schedules table
        $stmt1 = $conn->prepare("INSERT INTO schedules (user_id, floor_level, task_description, schedule_date, created_at) VALUES (?, ?, ?, ?, ?)");
        $stmt1->bind_param("issss", $user_id, $floor_level, $task_description, $schedule_date, $created_at);
        if (!$stmt1->execute()) {
            throw new Exception("Schedule Insert Error: " . $stmt1->error);
        }
        $stmt1->close();

        // 2. Insert into tasks table
        $stmt2 = $conn->prepare("INSERT INTO tasks (user_id, machine_id, bin_id, task_description, task_status, created_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt2->bind_param("iiisss", $user_id, $machine_id, $bin_id, $task_description, $task_status, $created_at);
        if (!$stmt2->execute()) {
            throw new Exception("Task Insert Error: " . $stmt2->error);
        }
        $stmt2->close();

        // If everything worked, commit the changes to the database
        $conn->commit();
        header("Location: $redirect_url?success=" . urlencode("Schedule and corresponding Task successfully added."));
        
    } catch (Exception $e) {
        // If anything failed, rollback the database to its previous state
        $conn->rollback();
        error_log($e->getMessage());
        header("Location: $redirect_url?error=" . urlencode("Database error: Could not save schedule and task."));
    }
    
    exit;
} else {
    header("Location: /dashboard.php");
    exit;
}
?>