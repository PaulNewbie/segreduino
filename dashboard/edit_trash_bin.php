<?php
header('Content-Type: application/json');
require_once __DIR__ . "/config.php";
if ($conn->connect_error) {
  echo json_encode(["success" => false, "message" => "Database connection failed"]);
  exit;
}

$id = intval($_POST['bin_id'] ?? 0);
$floor = $_POST['floor_level'] ?? '';
$type = $_POST['bin_type'] ?? '';

if (!$id || !$floor || !$type) {
  echo json_encode(["success" => false, "message" => "Invalid input"]);
  exit;
}

$stmt = $conn->prepare("UPDATE trash_bins SET floor_level=?, bin_type=? WHERE bin_id=?");
$stmt->bind_param("ssi", $floor, $type, $id);

if ($stmt->execute()) {
  echo json_encode(["success" => true, "message" => "Bin updated successfully"]);
} else {
  echo json_encode(["success" => false, "message" => "Update failed"]);
}

$stmt->close();
$conn->close();
?>
