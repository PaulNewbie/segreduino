<?php
// views/auth/login.php
session_start();
$error = '';
$success = '';

// Handle login form
if (isset($_POST['username'], $_POST['password'])) {
    $input_username = trim($_POST['username']);
    $input_email = $input_username; 
    $input_password = trim($_POST['password']);

    // Import the database connection
    require_once __DIR__ . '/../../config/config.php';

    $stmt = $conn->prepare("SELECT id, username, password FROM admin_users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->bind_param("ss", $input_username, $input_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($input_password, $user['password'])) {
            $_SESSION['username'] = $user['username'];
            // Since we are routing through index.php now, redirect to the clean URL
            header('Location: /dashboard.php');
            exit;
        } else {
            $error = 'Incorrect password. Please try again.';
        }
    } else {
        $error = 'Username or email not found.';
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
  <link rel="stylesheet" href="/views/auth/css/auth.css" />
  <title>Login | SegreDuino Admin</title>
</head>
<body>
  <div class="auth-container">
    <div class="brand"><i class="bx bxs-chip"></i> SegreDuino</div>
    <h2><i class="bx bxs-lock-alt"></i>Admin Login</h2>
    
    <?php if ($error): ?>
      <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
      <div class="input-icon-group">
        <i class="bx bx-user"></i>
        <input type="text" name="username" placeholder="Username or Email" required autofocus />
      </div>
      <div class="input-icon-group">
        <i class="bx bx-lock"></i>
        <input type="password" name="password" placeholder="Password" required />
      </div>
      <button type="submit"><i class="bx bx-log-in"></i>Login</button>
    </form>
      
    <div style="display:flex; flex-direction:column; align-items:center; gap:8px; margin-top:16px;">
        <a href="/forgot-password.php" class="auth-link"><i class="bx bx-help-circle"></i>Forgot Password?</a>
        <a href="/register.php" class="auth-link"><i class="bx bx-user-plus"></i>Don't have an account? Register</a>
    </div>
  </div>
</body>
</html>