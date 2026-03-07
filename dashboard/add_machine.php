<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $machine_name = trim($_POST['machine_name'] ?? '');
    $location = trim($_POST['location'] ?? '');

    if ($machine_name === '' || $location === '') {
        echo "All fields required";
        exit;
    }

    require_once __DIR__ . "/config.php";
    if ($conn->connect_error) {
        echo "DB connection error";
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO machines (machine_name, location) VALUES (?, ?)");
    $stmt->bind_param("ss", $machine_name, $location);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "DB insert error";
    }

    $stmt->close();
    
} else {
    echo "Invalid request";
}