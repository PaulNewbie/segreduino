<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // ✅ Connect to your hosting database
    require_once __DIR__ . '/../../config/config.php';

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Get raw POST data
    $input = json_decode(file_get_contents("php://input"), true);

    $email = $conn->real_escape_string($input['email'] ?? '');
    $full_name = $conn->real_escape_string($input['full_name'] ?? '');
    $phone = $conn->real_escape_string($input['phone'] ?? '');

    if (empty($email) || empty($full_name)) {
        echo json_encode(["success" => false, "message" => "Email and full name are required."]);
        exit();
    }

    // ✅ Use prepared statement (secure)
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone = ? WHERE email = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sss", $full_name, $phone, $email);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Profile updated successfully."]);
    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $stmt->close();
    

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
    exit();
}
