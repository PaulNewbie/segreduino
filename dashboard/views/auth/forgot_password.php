<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// PHPMailer
require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';
require __DIR__ . '/../PHPMailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
$success = '';

// DB connection
require_once __DIR__ . '/../../config/config.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


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
                $mail->Username = 'sierabelbbarasan@gmail.com';
                $mail->Password = 'nhpzgjwgunogjtwv';
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
                header("Location: verify_code.php");
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
<title>Forgot Password | SegreDuino Admin</title>
<style>
body {
    background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)),
                url('../../img/PDM-Facade.png');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    font-family: 'Poppins', sans-serif;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    margin: 0;
}

.login-container {
    background: rgba(28,31,38,0.25);
    padding: 60px 50px;
    border-radius: 20px;
    width: 100%;
    max-width: 480px;
    color: #fff;
    display: flex;
    flex-direction: column;
    align-items: center;
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,0.1);
}

.login-container .brand {
    font-size: 28px;
    font-weight: 700;
    color: #1abc3a;
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.login-container .brand .bx { font-size: 32px; }

.login-container h2 {
    margin-bottom: 24px;
    font-weight: 600;
    color: #FBFBFB;
}

.input-icon-group {
    position: relative;
    width: 100%;
    margin-bottom: 10px;
}
.input-icon-group i {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 20px;
    color: #00ff44;
    opacity: 0.9;
}
.input-icon-group input {
    width: 100%;
    background: #232823;
    border: none;
    border-radius: 8px;
    color: #FBFBFB;
    padding: 10px 36px;
    font-size: 16px;
    outline: none;
    box-sizing: border-box;
}
.input-icon-group input:focus { background: #2e332e; }

button {
    background: #1abc3a;
    color: #fff;
    border: none;
    padding: 12px 0;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    width: 100%;
}
button:hover { background: #169c2f; }

.error { color: #DB504A; margin-bottom: 10px; font-size: 15px; text-align: center; }
.success { color: #1abc3a; margin-bottom: 10px; font-size: 15px; text-align: center; }

.register-link {
    margin-top: 18px;
    color: #1abc3a;
    font-size: 15px;
    text-align: center;
    text-decoration: none;
    display: block;
}
.register-link:hover { color: #169c2f; text-decoration: underline; }
</style>
</head>
<body>
<div class="login-container">
    <div class="brand"><i class="bx bxs-chip"></i> SegreDuino</div>
    <h2><i class="bx bx-help-circle" style="vertical-align:middle;margin-right:6px;"></i>Forgot Password</h2>

    <?php if($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
        <div class="input-icon-group">
            <i class="bx bx-envelope"></i>
            <input type="email" name="forgot_email" placeholder="Enter your email" required>
        </div>
        <button type="submit"><i class="bx bx-mail-send" style="vertical-align:middle;margin-right:4px;"></i>Send Verification</button>
    </form>

    <a href="../login.php" class="register-link"><i class="bx bx-log-in" style="vertical-align:middle;margin-right:4px;"></i>Back to Login</a>
</div>
</body>
</html>
