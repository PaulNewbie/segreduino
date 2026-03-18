<?php
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

$machine_id = $_POST['machine_id'] ?? null;
$bin_type = $_POST['bin_type'] ?? '';

// Validate required fields coming from the form
if (!$machine_id || !$bin_type) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

// 1. Automatically fetch the machine's location to determine the floor_level
$locQuery = $conn->prepare("SELECT location FROM machines WHERE machine_id = ?");
$locQuery->bind_param("i", $machine_id);
$locQuery->execute();
$locQuery->bind_result($machine_location);

if (!$locQuery->fetch()) {
    $locQuery->close();
    echo json_encode(["success" => false, "message" => "Invalid Machine selected."]);
    exit;
}
$locQuery->close();

// 2. Safely parse the location string to match the database ENUM ('1st', '2nd', '3rd')
$floor_level = '1st'; // Default fallback
if (stripos($machine_location, '1st') !== false) {
    $floor_level = '1st';
} elseif (stripos($machine_location, '2nd') !== false) {
    $floor_level = '2nd';
} elseif (stripos($machine_location, '3rd') !== false) {
    $floor_level = '3rd';
}

// 3. Set remaining defaults
$bin_status = '0'; 
$last_updated = date('Y-m-d H:i:s');
$hallway_side = 'left'; // Default to satisfy NOT NULL

// 4. Insert into database
$sql = "INSERT INTO trash_bins (machine_id, floor_level, hallway_side, bin_type, bin_status, last_updated) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

$stmt->bind_param("isssss", $machine_id, $floor_level, $hallway_side, $bin_type, $bin_status, $last_updated);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Bin successfully added"]);
} else {
    echo json_encode(["success" => false, "message" => "Insert failed: " . $stmt->error]);
}

$stmt->close();
?>