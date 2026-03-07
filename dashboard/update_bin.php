<?php
header('Content-Type: application/json');
require_once __DIR__ . "/config.php";

if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed"]));
}

// ESP32 sends POST updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $machine_id = $_POST['machine_id'] ?? null;
    $bin_type = $_POST['bin_type'] ?? null;
    $percentage = $_POST['percentage'] ?? null;

    if ($machine_id && $bin_type && $percentage !== null) {
        $stmt = $conn->prepare("
            UPDATE trash_bins
            SET bin_status=?, last_updated=NOW()
            WHERE machine_id=? AND bin_type=?
        ");
        $stmt->bind_param("sis", $percentage, $machine_id, $bin_type);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            $insert = $conn->prepare("
                INSERT INTO trash_bins (machine_id, bin_type, bin_status, last_updated)
                VALUES (?, ?, ?, NOW())
            ");
            $insert->bind_param("isi", $machine_id, $bin_type, $percentage);
            $insert->execute();
            $insert->close();
        }

        $stmt->close();
        echo json_encode(["success" => true]);
        exit;
    } else {
        echo json_encode(["error" => "Missing parameters"]);
        exit;
    }
}

// For dashboard display
$data = [];
$result = $conn->query("SELECT * FROM trash_bins ORDER BY bin_type ASC");
while ($row = $result->fetch_assoc()) $data[] = $row;
echo json_encode($data);

