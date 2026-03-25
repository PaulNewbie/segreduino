<?php
// test_view.php
header("Refresh: 5"); // Auto-refresh the page every 5 seconds
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ESP32 Connection Test</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin-top: 50px; }
        .status-box { display: inline-block; padding: 20px; border-radius: 10px; background-color: #f4f4f4; border: 2px solid #ccc; }
        h1 { color: #333; }
        .log { font-size: 20px; color: #0066cc; font-weight: bold; }
    </style>
</head>
<body>
    <div class="status-box">
        <h1>ESP32 Connection Status</h1>
        <p>This page updates every 5 seconds.</p>
        <p class="log">
            <?php
            // Read and display the log file if it exists
            if (file_exists("esp32_test_log.txt")) {
                echo file_get_contents("esp32_test_log.txt");
            } else {
                echo "Waiting for ESP32 connection...";
            }
            ?>
        </p>
    </div>
</body>
</html>