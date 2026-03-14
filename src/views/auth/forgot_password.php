<?php
// views/auth/forgot_password.php
session_start();

// Make sure PHPMailer paths are correct relative to the new structure
require_once __DIR__ . '/../../vendor/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../vendor/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../../vendor/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../config/config.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = '';
$success = '';

if (isset($_POST['forgot_email'])) {
    $email = trim($_POST['forgot_email']);

    if ($email === '') {
        $error = 'Please enter your email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        $stmt = $conn->prepare("SELECT id, username FROM admin_users WHERE LOWER(email) = LOWER(?) LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $verification_code = rand(100000, 999999);
            $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            $update = $conn->prepare("UPDATE admin_users SET verification_code=?, reset_code_expiry=? WHERE id=?");
            $update->bind_param("ssi", $verification_code, $expiry, $user['id']);
            $update->execute();
            $update->close();

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'sierabelbbarasan@gmail.com'; // Remember to move this to an env file eventually!
                $mail->Password = 'nhpzgjwgunogjtwv';           // Remember to move this to an env file eventually!
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('sierabelbbarasan@gmail.com', 'SegreDuino Admin');
                $mail->addAddress($email, $user['username']);
                $mail->isHTML(true);
                $mail->Subject = 'SegreDuino Password Reset Verification Code';
                $mail->Body = "Hello {$user['username']},<br><br>
                    Your verification code is: <b>{$verification_code}</b><br>
                    It expires in 10 minutes.<br><br>
                    If you did not request this, please ignore this email.";

                $mail->send();
                $_SESSION['reset_email'] = $email;
                header("Location: /verify-code.php"); 
                exit;

            } catch (Exception $e) {
                $error = 'Mailer Error: ' . $mail->ErrorInfo;
            }
        } else {
            $error = 'Email not found.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/auth/auth.css" />
  <title>Forgot Password | SegreDuino Admin</title>
</head>
<body>
  <div class="auth-container">
    <div class="brand"><i class="bx bxs-chip"></i> SegreDuino</div>
    <h2><i class="bx bx-help-circle"></i>Forgot Password</h2>

    <?php if($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if($success): ?>
        <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <p style="text-align:center; font-size:14px; margin-bottom:20px; color:#ccc;">
        Enter your email address and we'll send you a verification code to reset your password.
    </p>

    <form method="post" autocomplete="off">
        <div class="input-icon-group">
            <i class="bx bx-envelope"></i>
            <input type="email" name="forgot_email" placeholder="Enter your email" required autofocus>
        </div>
        <button type="submit"><i class="bx bx-mail-send"></i>Send Code</button>
    </form>

    <a href="/login.php" class="auth-link"><i class="bx bx-arrow-back"></i>Back to Login</a>
  </div>
</body>
</html>