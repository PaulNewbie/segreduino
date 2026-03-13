<?php
// filepath: /segreduino/dashboard/add_task.php

session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize form input
    $user_id = trim($_POST['user_id']);
    $bin_id = trim($_POST['bin_id']);
    $machine_id = trim($_POST['machine_id']);
    $task_description = trim($_POST['task_description']);
    $task_status = trim($_POST['status']);
    $created_at = trim($_POST['created_at']);

    // Validate required fields
    if (empty($user_id) || empty($bin_id) || empty($machine_id) || empty($task_description) || empty($task_status) || empty($created_at)) {
        echo "All fields are required.";
        exit;
    }

    require_once __DIR__ . '/../../config/config.php';
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Adjust bind_param types if needed (assuming IDs are integers)
    $stmt = $conn->prepare("INSERT INTO tasks (user_id, bin_id, machine_id, task_description, task_status, created_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisss", $user_id, $bin_id, $machine_id, $task_description, $task_status, $created_at);

    if ($stmt->execute()) {
        header("Location: index.php?status=success");
        exit;
    } else {
        error_log("Insert Error: " . $stmt->error);
        echo "Something went wrong while saving the task.";
    }
    $stmt->close();
    
} else {
    echo "Invalid request.";
}
?>