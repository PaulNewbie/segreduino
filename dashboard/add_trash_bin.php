<?php
require_once __DIR__ . "/config.php";
if ($conn->connect_error) {
    http_response_code(500);
    echo "Database connection failed";
    exit;
}

$floor_level = $_POST['floor_level'];
$hallway_side = $_POST['hallway_side'];
$bin_type = $_POST['bin_type'];
$bin_status = $_POST['bin_status'];
$last_updated = date('Y-m-d H:i:s'); // Automatically set current date and time

$sql = "INSERT INTO trash_bins (floor_level, hallway_side, bin_type, bin_status, last_updated) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $floor_level, $hallway_side, $bin_type, $bin_status, $last_updated);

if ($stmt->execute()) {
    echo "success";
} else {
    http_response_code(500);
    echo "Insert failed";
}
$stmt->close();

?>