<?php
if (session_status() === PHP_SESSION_NONE);
require_once __DIR__ . '/../../config/config.php';

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];

    // 1. Check for basic upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error_msg'] = "Error uploading file. Please try again.";
        header("Location: /profile.php");
        exit;
    }

    // 2. Validate it is actually an image
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        $_SESSION['error_msg'] = "Only JPG, PNG, WEBP, and GIF files are allowed.";
        header("Location: /profile.php");
        exit;
    }

    // 3. Set up the directory (create it if it doesn't exist)
    $uploadDir = __DIR__ . '/../../../assets/img/avatars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // 4. Generate a safe, unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFilename = 'admin_' . $userId . '_' . time() . '.' . $ext;
    $destination = $uploadDir . $newFilename;

    // 5. Move the file and update the database
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $avatarPath = '/assets/img/avatars/' . $newFilename;
        
        $stmt = $conn->prepare("UPDATE admin_users SET avatar = ? WHERE id = ?");
        $stmt->bind_param("si", $avatarPath, $userId);
        
        if ($stmt->execute()) {
            $_SESSION['avatar'] = $avatarPath;
            
            $_SESSION['success_msg'] = "Profile photo updated successfully!";
        } else {
            $_SESSION['error_msg'] = "Failed to update database.";
        }
        $stmt->close();
    } else {
        $_SESSION['error_msg'] = "Failed to save the uploaded file to the server.";
    }
}

// Redirect back to profile page
header("Location: /profile.php");
exit;