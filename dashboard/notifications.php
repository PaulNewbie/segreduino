<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . "/config.php";

if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed']));
}

// Get unread notifications
$sql = "SELECT n.*, u.name as user_name 
        FROM notifications n 
        LEFT JOIN users u ON n.user_id = u.user_id 
        WHERE n.status = 'unread' 
        ORDER BY n.created_at DESC";

$result = $conn->query($sql);
$notifications = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $notifications[] = [
            'id' => $row['id'],
            'message' => $row['message'],
            'type' => $row['type'],
            'user_name' => $row['user_name'],
            'created_at' => $row['created_at']
        ];
    }
}

echo json_encode(['notifications' => $notifications]);
$conn->close();
?>