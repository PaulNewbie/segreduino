<?php
session_start();
header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . "/config.php";
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => $conn->connect_error]);
    exit;
}

$sql = "SELECT n.id, n.message, n.type, n.status, n.created_at,
               t.task_description,
               u.full_name AS staff_name
        FROM notifications n
        LEFT JOIN tasks t ON n.task_id = t.id
        LEFT JOIN users u ON t.staff_id = u.user_id
        WHERE n.status = 'unread'
        ORDER BY n.created_at DESC
        LIMIT 10";

$result = $conn->query($sql);
$notifications = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $created = new DateTime($row['created_at']);
        $now     = new DateTime();
        $diff    = $created->diff($now);

        if ($diff->d > 0)      $time = $diff->d.' days ago';
        elseif ($diff->h > 0)  $time = $diff->h.' hours ago';
        elseif ($diff->i > 0)  $time = $diff->i.' minutes ago';
        else                   $time = 'Just now';

        switch ($row['type']) {
            case 'task_completed':
                $message = "{$row['staff_name']} completed: {$row['task_description']}";
                break;
            case 'bin_full':
                $message = "Bin full: {$row['message']}";
                break;
            case 'maintenance_required':
                $message = "Maintenance required: {$row['message']}";
                break;
            default:
                $message = $row['message'];
        }

        $notifications[] = [
            'id'   => $row['id'],
            'msg'  => $message,
            'time' => $time
        ];
    }
}

echo json_encode(['success'=>true,'count'=>count($notifications),'data'=>$notifications]);

