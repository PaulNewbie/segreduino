<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

$error = '';
$success = '';

// DB connection
require_once __DIR__ . "/../config.php";
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Kung walang reset_email sa session, ibalik sa forgot_password.php
if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit;
}

$email = $_SESSION['reset_email'];

if (isset($_POST['verification_code'])) {
    $code = trim($_POST['verification_code']);

    $stmt = $conn->prepare("SELECT id, reset_code_expiry FROM admin_users 
                            WHERE LOWER(email) = LOWER(?) AND verification_code=? LIMIT 1");
    $stmt->bind_param("ss", $email, $code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $expiry = strtotime($user['reset_code_expiry']);

        if (time() > $expiry) {
            $error = 'Verification code expired. Request a new one.';
        } else {
            $_SESSION['reset_user_id'] = $user['id'];
            // Success → go to reset password page
            header('Location: reset_password.php');
            exit;
        }
    } else {
        $error = 'Invalid verification code.';
    }

    $stmt->close();
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
<title>Verify Code | SegreDuino Admin</title>
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
.login-container .brand { font-size: 28px; font-weight: 700; color: #1abc3a; margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }
.login-container .brand .bx { font-size: 32px; }
.login-container h2 { margin-bottom: 24px; font-weight: 600; color: #FBFBFB; }
.input-icon-group { position: relative; width: 100%; margin-bottom: 10px; }
.input-icon-group i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); font-size: 20px; color: #00ff44; opacity: 0.9; }
.input-icon-group input { width: 100%; background: #232823; border: none; border-radius: 8px; color: #FBFBFB; padding: 10px 36px; font-size: 16px; outline: none; box-sizing: border-box; }
.input-icon-group input:focus { background: #2e332e; }
button { background: #1abc3a; color: #fff; border: none; padding: 12px 0; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; width: 100%; }
button:hover { background: #169c2f; }
.error { color: #DB504A; margin-bottom: 10px; font-size: 15px; text-align: center; }
.success { color: #1abc3a; margin-bottom: 10px; font-size: 15px; text-align: center; }
.register-link { margin-top: 18px; color: #1abc3a; font-size: 15px; text-align: center; text-decoration: none; display: block; }
.register-link:hover { color: #169c2f; text-decoration: underline; }
</style>
</head>
<body>
<div class="login-container">
    <div class="brand"><i class="bx bxs-chip"></i> SegreDuino</div>
    <h2><i class="bx bx-key" style="vertical-align:middle;margin-right:6px;"></i>Enter Verification Code</h2>

    <?php if($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if($success): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <form method="post" autocomplete="off">
        <div class="input-icon-group">
            <i class="bx bx-key"></i>
            <input type="text" name="verification_code" placeholder="Enter 6-digit code" maxlength="6" required>
        </div>
        <button type="submit"><i class="bx bx-log-in" style="vertical-align:middle;margin-right:4px;"></i>Verify</button>
    </form>

    <a href="forgot_password.php" class="register-link"><i class="bx bx-arrow-back" style="vertical-align:middle;margin-right:4px;"></i>Back to Forgot Password</a>
</div>
</body>
</html>
