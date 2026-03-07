<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "u303252282_root", "Forall.24", "u303252282_smart_waste");
if ($conn->connect_error) {
  echo json_encode(["success" => false, "message" => "Database connection failed"]);
  exit;
}

$id = intval($_POST['bin_id'] ?? 0);
if (!$id) {
  echo json_encode(["success" => false, "message" => "Missing ID"]);
  exit;
}

$stmt = $conn->prepare("DELETE FROM trash_bins WHERE bin_id=?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
  echo json_encode(["success" => true, "message" => "Bin deleted successfully"]);
} else {
  echo json_encode(["success" => false, "message" => "Delete failed"]);
}

$stmt->close();
$conn->close();
?>
