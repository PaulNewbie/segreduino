<?php
// filepath: c:\Users\Honey Dionisio\Desktop\SAMPLES\mobile\lib\screen\verify_code.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

$email = $data['email'] ?? '';
$code = $data['code'] ?? '';

if (!$email || !$code) {
    echo json_encode(['success' => false, 'message' => 'Email and code are required.']);
    exit;
}

// Database connection
require_once __DIR__ . "/config.php";

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

// Check code in users table (where reset_code is stored)
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email=? AND reset_code=?");
$stmt->bind_param("ss", $email, $code);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Code verified!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid verification code.']);
}

$stmt->close();

exit;