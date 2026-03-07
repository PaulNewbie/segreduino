<?php
header('Content-Type: application/json');
$mysqli = new mysqli("localhost", "u303252282_root", "Forall.24", "u303252282_smart_waste");
if ($mysqli->connect_error) {
    echo json_encode(["success"=>false,"message"=>"DB connection failed"]);
    exit;
}

$machine_id = isset($_POST['machine_id']) ? intval($_POST['machine_id']) : 0;
$machine_name = isset($_POST['machine_name']) ? trim($_POST['machine_name']) : '';
$location = isset($_POST['location']) ? trim($_POST['location']) : '';

if ($machine_id <= 0 || $machine_name === '' || $location === '') {
    echo json_encode(["success"=>false,"message"=>"All fields required"]);
    exit;
}

$stmt = $mysqli->prepare("UPDATE machines SET machine_name = ?, location = ? WHERE machine_id = ?");
$stmt->bind_param("ssi", $machine_name, $location, $machine_id);

if ($stmt->execute()) {
    echo json_encode(["success"=>true,"message"=>"Kiosk updated successfully."]);
} else {
    echo json_encode(["success"=>false,"message"=>"DB error: " . $stmt->error]);
}

$stmt->close();
$mysqli->close();
?>
