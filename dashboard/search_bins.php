<?php
require_once __DIR__ . "/config.php";
if($conn->connect_error){die("DB Error");}

$q = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';

$sql = "SELECT bin_type, bin_status, last_updated 
        FROM trash_bins 
        WHERE bin_type LIKE '%$q%' 
           OR machine_id LIKE '%$q%' 
        ORDER BY last_updated DESC
        LIMIT 10";
$res = $conn->query($sql);

$data = [];
while($row = $res->fetch_assoc()){
    $data[] = $row;
}
header('Content-Type: application/json');
echo json_encode($data);

