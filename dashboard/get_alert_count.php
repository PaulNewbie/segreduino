<?php
require_once __DIR__ . "/config.php";
if($conn->connect_error){die("DB Error");}

$sql = "SELECT bin_type, machine_id, bin_status, last_updated
        FROM trash_bins
        WHERE bin_status >= 80
        ORDER BY last_updated DESC
        LIMIT 10";
$res = $conn->query($sql);

$alerts = [];
while($row = $res->fetch_assoc()){
    $alerts[] = $row;
}
header('Content-Type: application/json');
echo json_encode($alerts);
$conn->close();
