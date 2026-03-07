<?php
header('Content-Type: application/json');
$mysqli = new mysqli("localhost", "u303252282_root", "Forall.24", "u303252282_smart_waste");
if ($mysqli->connect_error) {
    echo json_encode(["success"=>false,"message"=>"DB connection failed"]);
    exit;
}

$machine_id = isset($_POST['machine_id']) ? intval($_POST['machine_id']) : 0;
if ($machine_id <= 0) {
    echo json_encode(["success"=>false,"message"=>"Invalid id"]);
    exit;
}

// Optional: delete related trash_bins first (if you have FK constraints or want cascade)
$mysqli->begin_transaction();
try {
    // If you want to remove trash_bins associated to this machine:
    // $stmt0 = $mysqli->prepare("DELETE FROM trash_bins WHERE machine_id = ?");
    // $stmt0->bind_param("i", $machine_id);
    // $stmt0->execute();
    // $stmt0->close();

    $stmt = $mysqli->prepare("DELETE FROM machines WHERE machine_id = ?");
    $stmt->bind_param("i", $machine_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $mysqli->commit();
        echo json_encode(["success"=>true,"message"=>"Kiosk deleted successfully."]);
    } else {
        $mysqli->rollback();
        echo json_encode(["success"=>false,"message"=>"No kiosk deleted (maybe not found)."]);
    }

    $stmt->close();
} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(["success"=>false,"message"=>"Error: " . $e->getMessage()]);
}

$mysqli->close();
?>
