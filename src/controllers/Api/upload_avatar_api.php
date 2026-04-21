<?php
// File: src/controllers/Api/upload_avatar_api.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/config.php';
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// 1. Check if user_id and file are provided
if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID is missing']);
    exit;
}

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No image uploaded or upload error']);
    exit;
}

$user_id = intval($_POST['user_id']);
$file = $_FILES['avatar'];

// 2. Validate File Type (Security)
// Instead of trusting the Flutter header, we let PHP check the real file signature.
$actualMimeType = mime_content_type($file['tmp_name']);
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

if (!in_array($actualMimeType, $allowedTypes)) {
    // I added the detected type to the error message so you can see exactly what it thinks it is!
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Detected: ' . $actualMimeType]);
    exit;
}

// 3. Define Upload Path (Going up directories to reach the assets/img/avatars folder)
$uploadDir = __DIR__ . '/../../../assets/img/avatars/';

// Create directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 4. Generate unique filename
$fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
$newFileName = 'user_' . $user_id . '_' . time() . '.' . $fileExtension;
$destination = $uploadDir . $newFileName;

// 5. Move the file
if (move_uploaded_file($file['tmp_name'], $destination)) {
    
    // Create the public URL to send back to Flutter
    // Adjust this base URL if your domain/folder structure is different!
    $publicUrl = '/assets/img/avatars/' . $newFileName;

    // 6. Update the users table
    $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE user_id = ?");
    $stmt->bind_param("si", $publicUrl, $user_id); 
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Avatar updated successfully',
            'avatar_url' => $publicUrl // Send the URL back so Flutter can display it immediately
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update database']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file to server']);
}
?>