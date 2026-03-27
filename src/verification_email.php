<?php
// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- CORS & Headers ---
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Accept");
    http_response_code(200);
    exit();
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header("Content-Type: application/json; charset=UTF-8");

// --- Load PHPMailer ---
require_once __DIR__ . '/vendor/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/vendor/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/vendor/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- Read JSON input ---
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);
$email = $data['email'] ?? '';

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Email is required.']);
    exit;
}

// --- DB connection ---
require_once __DIR__ . "/config/config.php";
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

// --- Check if email exists ---
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Email not found.']);
    $stmt->close();
    
    exit;
}
$stmt->close();

// --- Generate code and expiry ---
$code = strval(rand(100000, 999999));
$expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// --- Update DB ---
$stmt = $conn->prepare("UPDATE users SET reset_code=?, reset_code_expiry=? WHERE email=?");
$stmt->bind_param("sss", $code, $expiry, $email);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to save code.']);
    $stmt->close();
    
    exit;
}
$stmt->close();

// --- Send email ---
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'sierabelbbarasan@gmail.com';
    $mail->Password = 'nhpzgjwgunogjtwv'; // <-- App Password
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('sierabelbbarasan@gmail.com', 'SegreDuino');
    $mail->addAddress($email);
    $mail->Subject = 'Your Password Reset Code';
    $mail->Body = "Hello,\n\nYour verification code is: $code\nThis code will expire in 10 minutes.";

    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Verification code sent successfully.']);
} catch (Exception $e) {
    // Always return JSON even on error
    echo json_encode(['success' => false, 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
}


exit;