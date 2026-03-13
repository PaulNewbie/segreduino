<?php
// filepath: /dashboard/user_status.php

if (isset($_GET['user_id'])) {
    $user_id = intval($_GET['user_id']);

    require_once __DIR__ . '/../../config/config.php';
    if ($conn->connect_error) {
        http_response_code(500);
        echo "db_error";
        exit;
    }

    $status = null;

    // 1️⃣ Check regular users table
    $stmt = $conn->prepare("SELECT status FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($status);
    if (!$stmt->fetch()) {
        // 2️⃣ If not found, check admin_users table
        $stmt->close();
        $stmt = $conn->prepare("SELECT status FROM admin_users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($status);
        $stmt->fetch();
    }
    $stmt->close();
    

    if ($status !== null) {
        echo (strtolower($status) === 'active') ? 'active' : 'inactive';
    } else {
        echo 'not_found';
    }

} else {
    echo 'invalid';
}
