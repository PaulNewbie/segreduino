<?php
session_start();
$error = '';
$success = '';

// Handle login form
if (isset($_POST['username'], $_POST['password'])) {
    $input_username = trim($_POST['username']);
    $input_email = $input_username; // Fix for the bind_param bug
    $input_password = trim($_POST['password']);

    // Import the database connection
    require_once __DIR__ . '/../../config/config.php';

    $stmt = $conn->prepare("SELECT id, username, password FROM admin_users WHERE username = ? OR email = ? LIMIT 1");
    // Use two separate variables here to prevent PHP reference bugs
    $stmt->bind_param("ss", $input_username, $input_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Match password
        if (password_verify($input_password, $user['password'])) {
            $_SESSION['username'] = $user['username'];
            header('Location: dashboard.php');
            exit;
        } else {
            // DEBUG: Shows if password verify failed
            $error = 'DEBUG: Password incorrect. Hash in DB starts with: ' . substr($user['password'], 0, 10) . '...';
        }
    } else {
        // DEBUG: Shows if it couldn't find the username/email
        $error = 'DEBUG: Username/Email not found in the admin_users table.';
    }

    $stmt->close();
    
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet" />
  <title>Login | SegreDuino Admin</title>
  <style>
    body {
        background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), 
                    url('../img/PDM-Facade.png');
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
      background: rgba(28, 31, 38, 0.25);
      padding: 60px 50px;
      border-radius: 20px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
      width: 100%;
      max-width: 480px;
      color: #ffffff;
      display: flex;
      flex-direction: column;
      align-items: center;
      backdrop-filter: blur(12px);
      border: 1px solid rgba(255, 255, 255, 0.1);
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
    .login-container .brand .bx {
      font-size: 32px;
    }
    .login-container h2 {
      margin-bottom: 24px;
      font-weight: 600;
      color: #FBFBFB;
    }
    .login-container form {
      width: 100%;
      display: flex;
      flex-direction: column;
      gap: 20px;
    }
    .input-icon-group {
      position: relative;
      width: 100%;
      margin-bottom: 10px;
    }
    .input-icon-group i {
      left: 16px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 20px;
      color: #00ff44;
      opacity: 0.9;
    }
    .input-icon-group input {
      width: 100%;
      background: rgba(0, 0, 0, 0.25);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      color: #ffffff;
      font-size: 15px;
      transition: all 0.3s ease;
      box-sizing: border-box;
    }
    .login-container input[type="text"],
    .login-container input[type="password"] {
      padding: 10px 14px;
      border-radius: 8px;
      border: none;
      background: #232823;
      color: #FBFBFB;
      font-size: 16px;
      outline: none;
      width: 100%;
      box-sizing: border-box;
    }
    .login-container input[type="text"]:focus,
    .login-container input[type="password"]:focus {
      background: #2e332e;
    }
    .login-container button {
      background: #1abc3a;
      color: #fff;
      border: none;
      padding: 12px 0;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
    }
    .login-container button:hover {
      background: #169c2f;
    }
    .login-container .error {
      color: #DB504A;
      margin-bottom: 10px;
      font-size: 15px;
      text-align: center;
    }
    .login-container .success {
      color: #1abc3a;
      margin-bottom: 10px;
      font-size: 15px;
      text-align: center;
    }
    .register-link {
      margin-top: 18px;
      color: #1abc3a;
      font-size: 15px;
      text-align: center;
      text-decoration: none;
      display: block;
      transition: color 0.2s;
    }
    .register-link:hover {
      color: #169c2f;
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="brand">
      <i class="bx bxs-chip"></i> SegreDuino
    </div>
    <h2><i class="bx bxs-lock-alt" style="vertical-align:middle;margin-right:6px;"></i>Admin Login</h2>
    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="success"><?= $success ?></div>
    <?php else: ?>
      <form method="post" autocomplete="off">
        <div class="input-icon-group">
          <i class="bx bx-user"></i>
          <input type="text" name="username" placeholder="Username" required autofocus />
        </div>
        <div class="input-icon-group">
          <i class="bx bx-lock"></i>
          <input type="password" name="password" placeholder="Password" required />
        </div>
        <button type="submit"><i class="bx bx-log-in" style="vertical-align:middle;margin-right:4px;"></i>Login</button>
      </form>
      
  <a href="Admin/forgot_password.php" class="register-link" style="margin-top:8px;">
  <i class="bx bx-help-circle" style="vertical-align:middle;margin-right:4px;"></i>Forgot Password?
</a>

      <a href="register.php" class="register-link"><i class="bx bx-user-plus" style="vertical-align:middle;margin-right:4px;"></i>Don't have an account? Register</a>
    <?php endif; ?>
  </div>
</body>
</html>