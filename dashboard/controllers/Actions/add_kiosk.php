<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';

if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed."]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle adding a new kiosk
    $machine_name = trim($_POST['machine_name'] ?? '');
    $location = trim($_POST['location'] ?? '');

    if ($machine_name === '' || $location === '') {
        echo json_encode(["success" => false, "message" => "All fields are required."]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO machines (machine_name, location) VALUES (?, ?)");
    $stmt->bind_param("ss", $machine_name, $location);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Kiosk added successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Database insert error."]);
    }

    $stmt->close();
} else {
    // Handle fetching all kiosks
    $sql = "SELECT machine_id, machine_name, location FROM machines ORDER BY machine_id DESC";
    $result = $conn->query($sql);

    $machines = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $machines[] = $row;
        }
    }

    echo json_encode([
        "success" => true,
        "data" => $machines
    ]);
}


?>
