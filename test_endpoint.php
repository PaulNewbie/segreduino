<?php
// test_endpoint.php

// Check if the ESP32 sent the 'status' variable
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    
    // Get the current date and time
    date_default_timezone_set('Asia/Manila'); 
    $time = date("Y-m-d H:i:s");
    
    // Save the log to a text file
    $logMessage = "Last connected at: " . $time . " | Status: " . $status;
    file_put_contents("esp32_test_log.txt", $logMessage);
    
    echo "ESP32 Data Received Successfully!";
} else {
    echo "Waiting for data...";
}
?>