<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $machine_name = trim($_POST['machine_name'] ?? '');
    $location = trim($_POST['location'] ?? '');

    if ($machine_name === '' || $location === '') {
        echo "All fields required";
        exit;
    }

    $conn = new mysqli("localhost", "root", "", "smart_waste_management");
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
    $conn->close();
} else {
    echo "Invalid request";
}