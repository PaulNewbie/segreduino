<?php
// views/auth/register.php

require_once __DIR__ . '/../../config/config.php';
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $username   = trim($_POST['username'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = trim($_POST['password'] ?? '');
    $confirm    = trim($_POST['confirm'] ?? '');

    if ($first_name === '' || $last_name === '') {
        $error = 'Please enter your first and last name.';
    } elseif (!preg_match("/^[a-zA-Z]+$/", $first_name) || !preg_match("/^[a-zA-Z]+$/", $last_name)) {
        $error = 'Names can only contain letters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{5,20}$/', $username)) {
        $error = 'Username must be 5-20 characters (letters, numbers, or underscores).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
        $error = 'Password must be 8+ chars (include uppercase, lowercase, number, special char).';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Username or Email already exists.";
        } else {
            $stmt->close();
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO admin_users (first_name, last_name, username, email, password, role) VALUES (?, ?, ?, ?, ?, 'admin')");
            $stmt->bind_param("sssss", $first_name, $last_name, $username, $email, $hashed_password);

            if ($stmt->execute()) {
                $_SESSION['registration_success'] = true;
                header("Location: /login.php");
                exit;
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
        if(isset($stmt)) $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="/assets/css/auth/auth.css" />
  <title>Register | SegreDuino Admin</title>
</head>
<body>
  <div class="auth-container">
    <div class="brand"><i class="bx bxs-chip"></i> SegreDuino</div>
    <h2><i class="bx bxs-user-plus"></i>Register</h2>
    
    <?php if ($error): ?>
      <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
        <div style="display:flex; gap:10px;">
            <div class="input-icon-group" style="flex:1;">
                <i class="bx bx-user"></i>
                <input type="text" name="first_name" placeholder="First Name" required />
            </div>
            <div class="input-icon-group" style="flex:1;">
                <i class="bx bx-user"></i>
                <input type="text" name="last_name" placeholder="Last Name" required />
            </div>
        </div>
        
        <div class="input-icon-group">
            <i class="bx bx-id-card"></i>
            <input type="text" name="username" placeholder="Username" required />
        </div>
        <div class="input-icon-group">
            <i class="bx bx-envelope"></i>
            <input type="email" name="email" placeholder="Email" required />
        </div>
        <div class="input-icon-group">
            <i class="bx bx-lock"></i>
            <input type="password" name="password" id="password" placeholder="Password" required />
            <i class="bx bx-show show-hide-icon" id="togglePassword"></i>
        </div>
        <div class="input-icon-group">
            <i class="bx bx-lock-alt"></i>
            <input type="password" name="confirm" id="confirm" placeholder="Confirm Password" required />
            <i class="bx bx-show show-hide-icon" id="toggleConfirm"></i>
        </div>
        
        <button type="submit"><i class="bx bx-user-plus"></i>Create Account</button>
    </form>

    <a href="/login.php" class="auth-link"><i class="bx bx-log-in"></i>Already have an account? Login</a>

    <script>
      // Show/hide password functionality
      function setupPasswordToggle(toggleId, inputId) {
          const toggle = document.getElementById(toggleId);
          const input = document.getElementById(inputId);
          if(toggle && input) {
              toggle.onclick = function() {
                  this.classList.toggle('bx-show');
                  this.classList.toggle('bx-hide');
                  input.type = input.type === 'password' ? 'text' : 'password';
              };
          }
      }
      setupPasswordToggle('togglePassword', 'password');
      setupPasswordToggle('toggleConfirm', 'confirm');
    </script>
  </div>
</body>
</html>