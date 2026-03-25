<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/config.php';

$isWeb = isset($_POST['source']) && $_POST['source'] === 'web';
$oldPassword = '';
$newPassword = '';
$email = '';

// 1. Determine the source and get inputs
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

if (!empty($input)) {
    // FLUTTER APP REQUEST
    $email = $input['email'] ?? '';
    $oldPassword = $input['old_password'] ?? '';
    $newPassword = $input['new_password'] ?? '';
    header('Content-Type: application/json');
} else {
    // WEB BROWSER REQUEST
    // Now this will pass because we added user_id to login.php
    if (!isset($_SESSION['user_id'])) {
        die("Unauthorized. Please log out and log back in."); 
    }
    $email = $_SESSION['email'] ?? ''; 
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newPassword !== $confirmPassword) {
        returnError("New passwords do not match.", $isWeb);
    }
}

// 2. Validate Inputs
if (empty($email) || empty($oldPassword) || empty($newPassword)) {
    returnError("All fields are required.", $isWeb);
}

// 3. Dynamically switch tables based on Web (admin_users) vs Flutter (users)
$tableName = $isWeb ? 'admin_users' : 'users';
$idColumn = $isWeb ? 'id' : 'user_id';

// 4. Check Current Password in Database
$stmt = $conn->prepare("SELECT $idColumn, password FROM $tableName WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    returnError("User not found.", $isWeb);
}

$user = $result->fetch_assoc();

if (!password_verify($oldPassword, $user['password'])) {
    returnError("Incorrect current password.", $isWeb);
}

// 5. Update to New Password
$newHash = password_hash($newPassword, PASSWORD_DEFAULT);
$updateStmt = $conn->prepare("UPDATE $tableName SET password = ? WHERE email = ?");
$updateStmt->bind_param("ss", $newHash, $email);

if ($updateStmt->execute()) {
    if ($isWeb) {
        $_SESSION['success_msg'] = "Password updated successfully.";
        header("Location: /profile.php");
        exit;
    } else {
        echo json_encode(["success" => true, "message" => "Password updated successfully."]);
        exit;
    }
} else {
    returnError("Failed to update password in database.", $isWeb);
}

// Helper function to handle sending errors back to Web vs Flutter
function returnError($message, $isWeb) {
    if ($isWeb) {
        $_SESSION['error_msg'] = $message;
        header("Location: /profile.php");
        exit;
    } else {
        echo json_encode(["success" => false, "message" => $message]);
        exit;
    }
}