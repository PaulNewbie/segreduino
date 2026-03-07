<?php
// From Hostinger dashboard → Databases → MySQL Databases
// $servername = "localhost";           // Your host address from Hostinger
// $username = "u303252282_root";       // Your MySQL user
// $password = "Forall.24";             // The password you set for this user
// $dbname = "u303252282_smart_waste";  // Your database name

// From Localhost XAMPP setup MYSQL Database
$servername = "localhost";  
$username = "devuser";                             
$password = "DevPass123!";                   
$dbname = "smart_waste_management";  

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    // If it's an API expecting JSON, this prevents breaking the response
    if (in_array('application/json', headers_list())) {
        die(json_encode(["success" => false, "message" => "DB Connection failed."]));
    }
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>