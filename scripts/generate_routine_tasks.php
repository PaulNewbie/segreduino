<?php
// /scripts/generate_routine_tasks.php
// NOTE: This file should be triggered automatically by a Server Cron Job once a day (e.g., at 1:00 AM)

require_once __DIR__ . '/../src/config/config.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$today_name = date('l'); // Outputs 'Monday', 'Tuesday', etc.
$current_date = date('Y-m-d');
$created_at_time = date('Y-m-d H:i:s');

echo "Running Routine Generation Engine for: $today_name ($current_date)\n";

// Find all schedules that match today's routine rules
$sql = "SELECT * FROM schedules 
        WHERE recurrence_pattern = 'daily' 
        OR (recurrence_pattern = 'weekly' AND day_of_week = ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $today_name);
$stmt->execute();
$schedules = $stmt->get_result();

$tasks_created = 0;

while ($schedule = $schedules->fetch_assoc()) {
    
    // Safety Check: Make sure we haven't already generated this exact task today
    // so we don't accidentally create duplicates if the script runs twice
    $check_sql = "SELECT task_id FROM tasks WHERE user_id = ? AND task_description = ? AND DATE(created_at) = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("iss", $schedule['user_id'], $schedule['task_description'], $current_date);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows == 0) {
        // Generate the Task!
        $task_status = 'pending';
        // You can combine the date with the specific time the admin requested
        $task_timestamp = $current_date . ' ' . $schedule['schedule_time']; 

        $insert_sql = "INSERT INTO tasks (user_id, machine_id, bin_id, task_description, task_status, created_at) VALUES (?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iiisss", $schedule['user_id'], $schedule['machine_id'], $schedule['bin_id'], $schedule['task_description'], $task_status, $task_timestamp);
        
        if ($insert_stmt->execute()) {
            $tasks_created++;
            echo "Created Task for User " . $schedule['user_id'] . ": " . $schedule['task_description'] . "\n";
        }
        $insert_stmt->close();
    }
    
    $check_stmt->close();
}

$stmt->close();
echo "Engine Complete. Generated $tasks_created new tasks for today.\n";
?>