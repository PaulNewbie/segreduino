<?php
if (session_status() === PHP_SESSION_NONE) ;
require_once __DIR__ . '/../../config/config.php';

// Check if it's a web form submission
$isWeb = isset($_POST['source']) && $_POST['source'] === 'web';

// ==========================================
// 1. HANDLE FLUTTER APP REQUEST (JSON)
// ==========================================
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

if (!empty($input)) {
    header("Content-Type: application/json; charset=UTF-8");
    
    $email = trim($input['email'] ?? '');
    $fullName = trim($input['full_name'] ?? '');
    $phone = trim($input['phone'] ?? '');

    if (empty($email) || empty($fullName)) {
        echo json_encode(["success" => false, "message" => "Email and full name are required."]);
        exit;
    }

    $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone = ? WHERE email = ?");
    $stmt->bind_param("sss", $fullName, $phone, $email);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Profile updated successfully."]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update profile."]);
    }
    $stmt->close();
    exit;
}

// ==========================================
// 2. HANDLE WEB DASHBOARD REQUEST (POST Form)
// ==========================================
if ($isWeb) {
    if (!isset($_SESSION['user_id'])) {
        die("Unauthorized. Please log in.");
    }
    
    $userId = $_SESSION['user_id'];
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($firstName) || empty($lastName) || empty($email)) {
        $_SESSION['error_msg'] = "First name, last name, and email are required.";
        header("Location: /profile.php");
        exit;
    }

    // Update the admin_users table for web users
    $stmt = $conn->prepare("UPDATE admin_users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
    $stmt->bind_param("sssi", $firstName, $lastName, $email, $userId);

    if ($stmt->execute()) {
        // Update the session email so the UI doesn't break if they use email to log in
        $_SESSION['email'] = $email; 
        
        $_SESSION['success_msg'] = "Personal information updated successfully.";
        header("Location: /profile.php");
        exit;
    } else {
        $_SESSION['error_msg'] = "Failed to update profile information.";
        header("Location: /profile.php");
        exit;
    }
}

// If neither format matches
http_response_code(400);
echo "Invalid request format.";