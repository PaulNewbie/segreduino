<?php



if (!isset($_SESSION['reset_user_id'])) {
    header('Location: /forgot_password.php');
    exit;
}

$error = '';
$success = '';

// DB connection
require_once __DIR__ . '/../../config/config.php';
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (isset($_POST['new_password'], $_POST['confirm_password'])) {
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Password checks
    $lengthCheck  = strlen($new_password) >= 8;
    $upperCheck   = preg_match('/[A-Z]/', $new_password);
    $lowerCheck   = preg_match('/[a-z]/', $new_password);
    $numberCheck  = preg_match('/[0-9]/', $new_password);
    $specialCheck = preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_password);

    if ($new_password === '' || $confirm_password === '') {
        $error = 'Please fill in all fields.';
    } elseif (!$lengthCheck || !$upperCheck || !$lowerCheck || !$numberCheck || !$specialCheck) {
        $error = 'Password must be 8+ chars, include uppercase, lowercase, number & special char.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admin_users 
                                SET password=?, verification_code=NULL, reset_code_expiry=NULL 
                                WHERE id=?");
        $stmt->bind_param("si", $hashed_password, $_SESSION['reset_user_id']);
        if ($stmt->execute()) {
            unset($_SESSION['reset_user_id'], $_SESSION['reset_email']);
            $success = '✅ Password successfully updated! Redirecting to login...';
            header("Refresh:3; url=/login.php");
        } else {
            $error = 'Failed to update password: ' . $stmt->error;
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
<title>Reset Password | SegreDuino Admin</title>
<style>
body {
    background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)),
                url('../../img/PDM-Facade.png');
    background-size: cover;
    background-position: center;
    font-family: 'Poppins', sans-serif;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    margin: 0;
}

/* Container */
.login-container {
    background: rgba(30, 30, 30, 0.6);
    padding: 50px 40px;
    border-radius: 16px;
    width: 100%;
    max-width: 440px;
    color: #fff;
    backdrop-filter: blur(12px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    text-align: center;
    border: 1px solid rgba(255,255,255,0.1);
}

/* Brand */
.brand {
    font-size: 26px;
    font-weight: 700;
    color: #1abc3a;
    margin-bottom: 18px;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
}
.brand .bx { font-size: 30px; }

/* Headings */
h2 {
    margin-bottom: 20px;
    font-weight: 600;
    color: #FBFBFB;
}

/* Input Fields */
.input-icon-group {
    display: flex;
    align-items: center;
    background: rgba(35, 40, 35, 0.9);
    border-radius: 8px;
    margin-bottom: 16px;
    overflow: hidden;
    transition: all 0.2s ease;
    position: relative;
}

.input-icon-group i {
    flex: 0 0 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: #00ff44;
    background: rgba(30, 35, 30, 0.9);
}

.input-icon-group input {
    flex: 1;
    border: none;
    background: transparent;
    color: #FBFBFB;
    padding: 12px;
    font-size: 15px;
    outline: none;
    box-sizing: border-box;
}

/* Eye toggle */
.toggle-password {
    position: absolute;
    right: 12px;
    font-size: 18px;
    color: #aaa;
    cursor: pointer;
    transition: color 0.2s;
}
.toggle-password:hover { color: #fff; }

/* Focus effect for the whole input group */
.input-icon-group:focus-within {
    box-shadow: 0 0 0 2px #1abc3a;
    background: rgba(50, 55, 50, 0.95);
}

/* Button */
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
    transition: background 0.3s ease;
}
button:hover { background: #169c2f; }

/* Alerts */
.error, .success {
    margin-bottom: 15px;
    font-size: 14px;
    padding: 10px;
    border-radius: 6px;
}
.error {
    color: #ff4d4f;
    background: rgba(255,77,79,0.1);
}
.success {
    color: #52c41a;
    background: rgba(82,196,26,0.1);
}

/* Password rules */
.password-rules {
    font-size: 12px;
    margin-top: 6px;
    text-align: left;
    color: #ccc;
}
.password-valid { color: #1abc3a; }
.password-invalid { color: #ff4d4f; }

/* Back link */
.register-link {
    margin-top: 20px;
    color: #1abc3a;
    font-size: 14px;
    text-decoration: none;
    display: inline-block;
}
.register-link:hover { color: #169c2f; text-decoration: underline; }
</style>
</head>
<body>
<div class="login-container">
    <div class="brand"><i class="bx bxs-chip"></i> SegreDuino</div>
    <h2><i class="bx bx-lock-alt" style="margin-right:6px;"></i>Reset Password</h2>

    <?php if($error): ?><div class="error">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if($success): ?><div class="success">✅ <?= $success ?></div><?php endif; ?>

    <form method="post" autocomplete="off" id="resetForm">
        <div class="input-icon-group">
            <i class="bx bx-lock"></i>
            <input type="password" name="new_password" id="new_password" placeholder="New Password" required>
            <i class="bx bx-show toggle-password" data-target="new_password"></i>
        </div>
        <div class="password-rules" id="passwordHelp">
            Password must be 8+ chars, include uppercase, lowercase, number & special char.
        </div>

        <div class="input-icon-group">
            <i class="bx bx-lock"></i>
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
            <i class="bx bx-show toggle-password" data-target="confirm_password"></i>
        </div>
        <div class="password-rules" id="confirmHelp"></div>

        <button type="submit"><i class="bx bx-check-circle" style="margin-right:6px;"></i>Reset Password</button>
    </form>

    <a href="/login.php" class="register-link"><i class="bx bx-log-in" style="margin-right:4px;"></i>Back to Login</a>
</div>

<script>
const newPassword = document.getElementById('new_password');
const confirmPassword = document.getElementById('confirm_password');
const passwordHelp = document.getElementById('passwordHelp');
const confirmHelp = document.getElementById('confirmHelp');
const toggles = document.querySelectorAll('.toggle-password');

// Password validation
function validatePassword(password) {
    return {
        lengthCheck: password.length >= 8,
        upperCheck: /[A-Z]/.test(password),
        lowerCheck: /[a-z]/.test(password),
        numberCheck: /[0-9]/.test(password),
        specialCheck: /[!@#$%^&*(),.?":{}|<>]/.test(password)
    };
}

function updatePasswordHelp() {
    const checks = validatePassword(newPassword.value);
    passwordHelp.innerHTML = `
        <span class="${checks.lengthCheck ? 'password-valid' : 'password-invalid'}">At least 8 characters</span><br>
        <span class="${checks.upperCheck ? 'password-valid' : 'password-invalid'}">At least 1 uppercase letter</span><br>
        <span class="${checks.lowerCheck ? 'password-valid' : 'password-invalid'}">At least 1 lowercase letter</span><br>
        <span class="${checks.numberCheck ? 'password-valid' : 'password-invalid'}">At least 1 number</span><br>
        <span class="${checks.specialCheck ? 'password-valid' : 'password-invalid'}">At least 1 special character (!@#$%^&*)</span>
    `;
}

function updateConfirmHelp() {
    if(confirmPassword.value !== newPassword.value){
        confirmHelp.innerHTML = `<span class="password-invalid">Passwords do not match</span>`;
    } else if(confirmPassword.value.length > 0) {
        confirmHelp.innerHTML = `<span class="password-valid">Passwords match</span>`;
    } else {
        confirmHelp.innerHTML = '';
    }
}

// Show/Hide password toggle
toggles.forEach(toggle => {
    toggle.addEventListener('click', () => {
        const targetId = toggle.getAttribute('data-target');
        const input = document.getElementById(targetId);
        if (input.type === "password") {
            input.type = "text";
            toggle.classList.remove('bx-show');
            toggle.classList.add('bx-hide');
        } else {
            input.type = "password";
            toggle.classList.remove('bx-hide');
            toggle.classList.add('bx-show');
        }
    });
});

newPassword.addEventListener('input', () => {
    updatePasswordHelp();
    updateConfirmHelp();
});
confirmPassword.addEventListener('input', updateConfirmHelp);
</script>
</body>
</html>
