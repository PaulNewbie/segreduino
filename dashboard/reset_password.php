<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


// ✅ Accept only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit();
}

// ✅ Get and decode input
$input = json_decode(file_get_contents('php://input'), true);
$usernameOrEmail = $input['username'] ?? '';
$newPassword = $input['new_password'] ?? '';

// ✅ OPTIONAL: Log input for debugging
// file_put_contents('reset_password_debug.txt', json_encode($input));

if (empty($usernameOrEmail) || empty($newPassword)) {
    echo json_encode(["success" => false, "message" => "Missing data"]);
    exit();
}

// ✅ Connect to database
$conn = new mysqli("localhost", "u303252282_root", "Forall.24", "u303252282_smart_waste");

if ($conn->connect_errno) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit();
}

// ✅ Hash the new password
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// ✅ Prepare update query
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ? OR email = ?");
$stmt->bind_param("sss", $hashedPassword, $usernameOrEmail, $usernameOrEmail);

// ✅ Execute and respond
if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(["success" => true, "message" => "Password reset successful"]);
} else {
    echo json_encode(["success" => false, "message" => "User not found or password not changed"]);
}

// ✅ Clean up
$stmt->close();
$conn->close();
?>
