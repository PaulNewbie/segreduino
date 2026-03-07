<?php
$conn = new mysqli("localhost","u303252282_root","Forall.24","u303252282_smart_waste");
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
$conn->close();
