<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
if ($conn->connect_error) {
  echo json_encode(["success" => false, "message" => "Database connection failed"]);
  exit;
}

$id = intval($_GET['id'] ?? 0);
$res = $conn->query("SELECT * FROM machines WHERE machine_id=$id");
if ($res && $res->num_rows > 0) {
  echo json_encode(["success" => true, "machine" => $res->fetch_assoc()]);
} else {
  echo json_encode(["success" => false, "message" => "Machine not found"]);
}

?>
