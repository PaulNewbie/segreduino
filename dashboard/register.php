<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// --- DB connection ---
require_once __DIR__ . "/config.php";
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name'] ?? '');
    $username   = trim($_POST['username'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = trim($_POST['password'] ?? '');
    $confirm    = trim($_POST['confirm'] ?? '');

    // --- Validation ---
    if ($first_name === '' || $last_name === '') {
        $error = 'Please enter your first and last name.';
    } elseif (!preg_match("/^[a-zA-Z]+$/", $first_name) || !preg_match("/^[a-zA-Z]+$/", $last_name)) {
        $error = 'Names can only contain letters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{5,20}$/', $username)) {
        $error = 'Username must be 5-20 characters and contain only letters, numbers, or underscores.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
        $error = 'Password must be at least 8 characters, include uppercase, lowercase, number, and special character.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // --- Check if username/email exists ---
        $stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Username or Email already exists.";
            $stmt->close();
        } else {
            $stmt->close();
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                INSERT INTO admin_users (first_name, last_name, username, email, password, role)
                VALUES (?, ?, ?, ?, ?, 'admin')
            ");
            $stmt->bind_param("sssss", $first_name, $last_name, $username, $email, $hashed_password);

            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();
                // Redirect to login page after successful registration
                header("Location: login.php");
                exit;
            } else {
                $error = "Registration failed. Please try again.";
                $stmt->close();
            }
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet" />
  <title>Register | SegreDuino Admin</title>
  <style>
    body {
        background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), 
                    url('../img/PDM-Facade.png'); /* Add your background image */
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

    .register-container {
        background: rgba(28, 31, 38, 0.25);
        padding: 60px 50px;  /* Increased padding */
        border-radius: 20px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        width: 100%;
        max-width: 480px;    /* Increased width */
        color: #ffffff;
        display: flex;
        flex-direction: column;
        align-items: center;
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* Update form width */
    .register-container form {
        width: 100%;
    }

    /* Make inputs larger */
    .register-container input[type="text"],
    .register-container input[type="password"],
    .register-container input[type="email"] {
        background: rgba(35, 40, 35, 0.5);
        color: #ffffff;
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 14px 18px;  /* Larger padding */
        border-radius: 10px;
        font-size: 15px;     /* Larger font */
        width: 100%;
        margin-bottom: 16px;
    }

    .register-container input:focus {
        background: rgba(35, 40, 35, 0.7);
        border-color: rgba(26, 188, 58, 0.5);
        outline: none;
    }

    /* Update text colors for better visibility */
    .register-container h2,
    .register-container .brand {
        color: #ffffff;
    }

    .register-container .error {
        background: rgba(219, 80, 74, 0.2);
        padding: 10px;
        border-radius: 8px;
    }

    .register-container .success {
        background: rgba(26, 188, 58, 0.2);
        padding: 10px;
        border-radius: 8px;
    }

    /* Improved button style */
    .register-container button {
        background: #00ff44;
        color: #000000;
        border: none;
        padding: 16px;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        width: 100%;
        cursor: pointer;
        margin-top: 10px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .register-container button i {
        font-size: 20px;
    }

    .register-container button:hover {
        background: #00cc36;
        transform: translateY(-2px);
    }

    /* Larger brand and heading */
    .register-container .brand {
        font-size: 32px;     /* Larger brand */
        margin-bottom: 30px;
    }

    .register-container h2 {
        font-size: 24px;     /* Larger heading */
        margin-bottom: 24px;
    }

    /* Update icon positions and input styling */
    .input-icon-group {
        position: relative;
        margin-bottom: 20px;
        width: 100%;
    }

    .input-icon-group input {
        width: 100%;
        padding: 10px 20px;  /* Adjusted padding for icon space */
        background: rgba(0, 0, 0, 0.35);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        color: #ffffff;
        font-size: 15px;
        transition: all 0.3s ease;
        box-sizing: border-box;
    }

    .input-icon-group input::placeholder {
        color: rgba(255, 255, 255, 0.6);
    }

    .input-icon-group i:not(.show-hide-icon) {
        position: relative;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 20px;
        color: #00ff44;
        opacity: 0.9;
    }

    .show-hide-icon {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #00ff44;
        font-size: 20px;
        cursor: pointer;
        opacity: 0.9;
    }

    /* Input focus effects */
    .input-icon-group input:focus {
        border-color: #00ff44;
        outline: none;
        background: rgba(0, 0, 0, 0.45);
    }

    .input-icon-group input:focus + i {
        opacity: 1;
    }

    /* Update input field colors */
    .input-icon-group i {
        color: #00ff44 !important;
        
    }

    /* Update the register button */
    .register-container button {
        background: #00ff44;
        color: #000000;
        border: none;
        padding: 16px;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        width: 100%;
        cursor: pointer;
        margin-top: 10px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .register-container button i {
        font-size: 20px;
    }

    .register-container button:hover {
        background: #00cc36;
        transform: translateY(-2px);
    }

    /* Update login link */
.login-link {
    margin-top: 20px;
    font-size: 15px;
    opacity: 0.9;
    color: #00ff44; /* bright green */
    text-decoration: none; /* optional: tanggalin underline */
    font-weight: 600; /* optional: mas makapal na text */
}

.login-link:hover {
    color: #00cc36; /* darker green kapag hover */
    text-decoration: underline; /* optional: dagdag effect */
}

  </style>
</head>
<body>
  <div class="register-container">
    <div class="brand">
      <i class="bx bxs-chip"></i> SegreDuino
    </div>
    <h2><i class="bx bxs-user-plus" style="vertical-align:middle;margin-right:6px;"></i>Register</h2>
    
  <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

<form method="post" autocomplete="off">
    
    <div class="input-icon-group">
        <i class="bx bx-user"></i>
        <input type="text" name="first_name" placeholder="First Name" required />
    </div>
    <div class="input-icon-group">
        <i class="bx bx-user"></i>
        <input type="text" name="last_name" placeholder="Last Name" required />
    </div>
    <div class="input-icon-group">
        <i class="bx bx-user"></i>
        <input type="text" name="username" placeholder="Username" required />
    </div>
    <div class="input-icon-group">
        <i class="bx bx-envelope"></i>
        <input type="email" name="email" placeholder="Email" required />
    </div>
    <div class="input-icon-group">
        <i class="bx bx-lock"></i>
        <input type="password" name="password" id="password" placeholder="Password" required />
        <i class="bx bx-show show-hide-icon" id="togglePassword" style="right:12px;left:auto;"></i>
    </div>
    <div class="input-icon-group">
        <i class="bx bx-lock-alt"></i>
        <input type="password" name="confirm" id="confirm" placeholder="Confirm Password" required />
        <i class="bx bx-show show-hide-icon" id="toggleConfirm" style="right:12px;left:auto;"></i>
    </div>
    <button type="submit"><i class="bx bx-user-plus"></i>Register</button>
 </form>

    <a href="login.php" class="login-link"><i class="bx bx-log-in" style="vertical-align:middle;margin-right:4px;"></i>Already have an account? Login</a>
   

    <script>
      // Show/hide password for password field
      document.getElementById('togglePassword').onclick = function() {
        const pwd = document.getElementById('password');
        this.classList.toggle('bx-show');
        this.classList.toggle('bx-hide');
        pwd.type = pwd.type === 'password' ? 'text' : 'password';
      };
      // Show/hide password for confirm password field
      document.getElementById('toggleConfirm').onclick = function() {
        const cpwd = document.getElementById('confirm');
        this.classList.toggle('bx-show');
        this.classList.toggle('bx-hide');
        cpwd.type = cpwd.type === 'password' ? 'text' : 'password';
      };
    </script>
  </div>
</body>
</html>