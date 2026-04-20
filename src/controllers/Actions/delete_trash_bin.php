<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
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
  if (session_status() === PHP_SESSION_NONE) session_start();
  $admin_id = $_SESSION['user_id'] ?? 0;
  $conn->query("INSERT INTO activity_logs (user_id, action, platform) VALUES ($admin_id, 'Deleted trash bin ID: $id', 'Web')");

  echo json_encode(["success" => true, "message" => "Bin deleted successfully"]);
} else {
  echo json_encode(["success" => false, "message" => "Delete failed"]);
}

$stmt->close();

?>
