<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");

$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'] ?? '';
$code = $data['code'] ?? '';
$new_password = $data['new_password'] ?? '';

if (!$email || !$code || !$new_password) {
    echo json_encode(['success' => false, 'message' => 'Missing fields.']);
    exit;
}

require_once __DIR__ . "/config.php";

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

// Check code
$stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND reset_code=?");
$stmt->bind_param("ss", $email, $code);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid code or email.']);
    exit;
}

// Update password and clear code
$hashed = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE users SET password=?, reset_code=NULL WHERE email=? AND reset_code=?");
$stmt->bind_param("sss", $hashed, $email, $code);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update password.']);
}
$conn->close();