<?php
// Ensure user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: /login.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once __DIR__ . '/../../config/config.php';
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // 1. Get and sanitize form input safely (Ensuring IDs are integers)
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $machine_id = filter_input(INPUT_POST, 'machine_id', FILTER_VALIDATE_INT);
    $bin_id = filter_input(INPUT_POST, 'bin_id', FILTER_VALIDATE_INT);
    
    $task_description = trim($_POST['task_description'] ?? '');
    // $task_status = trim($_POST['status'] ?? '');
    // Status reworking
    $raw_status = trim($_POST['status'] ?? '');

    // Map the values from the HTML dropdown to your exact database ENUMs
    $status_mapping = [
        'Pending'     => 'pending',
        'In Progress' => 'in_progress',
        'Done'        => 'completed',
        // Fallbacks just in case the HTML was already changed:
        'pending'     => 'pending',
        'in_progress' => 'in_progress',
        'completed'   => 'completed'
    ];

    // Apply the mapped value, default to 'pending' if somehow an unknown value is sent
    $task_status = $status_mapping[$raw_status] ?? 'pending';
    $created_at = trim($_POST['created_at'] ?? '');

    // 2. Validate required fields
    if (!$user_id || !$machine_id || !$bin_id || empty($task_description) || empty($task_status) || empty($created_at)) {
        header("Location: /tasks.php?error=" . urlencode("All fields are required. Please select a valid Machine and Bin."));
        exit;
    }

    // 3. Database Validation: Ensure the selected Bin actually belongs to the selected Machine
    $checkQuery = $conn->prepare("SELECT bin_id FROM trash_bins WHERE bin_id = ? AND machine_id = ?");
    $checkQuery->bind_param("ii", $bin_id, $machine_id);
    $checkQuery->execute();
    $checkQuery->store_result();
    
    if ($checkQuery->num_rows === 0) {
        // Someone tampered with the HTML dropdown values
        $checkQuery->close();
        header("Location: /tasks.php?error=" . urlencode("Error: The selected Bin does not belong to the chosen Machine."));
        exit;
    }
    $checkQuery->close();

    // 4. Proceed with insertion 
    $stmt = $conn->prepare("INSERT INTO tasks (user_id, bin_id, machine_id, task_description, task_status, created_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisss", $user_id, $bin_id, $machine_id, $task_description, $task_status, $created_at);

    if ($stmt->execute()) {
        header("Location: /tasks.php?success=" . urlencode("Task successfully added to the directory."));
        exit;
    } else {
        error_log("Insert Error: " . $stmt->error);
        header("Location: /tasks.php?error=" . urlencode("A database error occurred while saving the task."));
        exit;
    }
    
    $stmt->close();
} else {
    // Kick out GET requests trying to access this file directly
    header("Location: /tasks.php");
    exit;
}
?>