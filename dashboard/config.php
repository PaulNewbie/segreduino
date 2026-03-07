<?php
$servername = "localhost";  // Your host address from Hostinger
$username = "u303252282_root";                             // Your MySQL user
$password = "Forall.24";                          // The password you set for this user
$dbname = "u303252282_smart_waste";                                       // Your database name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
