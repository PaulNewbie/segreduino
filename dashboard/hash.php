<?php
// filepath: /public_html/hash.php

// Connect to your hosting MySQL database
require_once __DIR__ . "/config.php";
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Password Hashing Report</h2>";

$result = $conn->query("SELECT user_id, username, password FROM users");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $user_id = $row['user_id'];
        $username = $row['username'];
        $password = $row['password'];

        // If not already hashed (doesn't start with $2y$)
        if (strpos($password, '$2y$') !== 0) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // Update the password in the database
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->bind_param("si", $hashed, $user_id);
            $stmt->execute();
            $stmt->close();

            echo "<span style='color:green;'>User <b>$username</b> (ID $user_id): password hashed and updated.</span><br>";
        } else {
            echo "<span style='color:blue;'>User <b>$username</b> (ID $user_id): password already hashed.</span><br>";
        }
    }
    $result->free();
} else {
    echo "<span style='color:red;'>Error fetching users: " . $conn->error . "</span>";
}

$conn->close();