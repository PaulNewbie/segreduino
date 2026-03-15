<?php
// src/views/pages/profile.php

if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit;
}

require_once __DIR__ . '/../../config/config.php';
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Fetch current user data based on session username
$username = $_SESSION['username'];
$user_id = null;
$first_name = '';
$last_name = '';
$email = '';
$role = '';

// Assuming your admins are in the admin_users table
$sql = "SELECT id, first_name, last_name, email, role FROM admin_users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($user_id, $first_name, $last_name, $email, $role);
$stmt->fetch();
$stmt->close();

// PAGE SETUP
$page_title = "My Profile - Admin";
$current_page = "profile"; 
$extra_css = '<link rel="stylesheet" href="/assets/css/pages/profile.css" />';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<main id="main-content">
  <div class="head-title">
    <div class="left">
      <h1>My Profile</h1>
      <ul class="breadcrumb">
        <li><a href="/dashboard.php" style="color: #888; pointer-events: auto;">Dashboard</a></li>
        <li><i class="bx bx-chevron-right"></i></li>
        <li><a class="active" href="/profile.php">Profile</a></li>
      </ul>
    </div>
  </div>

  <div class="profile-container">
      
      <div class="profile-card">
          <div class="profile-avatar-wrapper">
              <img src="/assets/img/pdm logo.jfif" alt="Avatar" class="profile-avatar">
              <label class="avatar-upload-btn" title="Change Avatar">
                  <i class='bx bx-camera'></i>
                  <input type="file" style="display:none;" accept="image/*">
              </label>
          </div>
          <h3><?= htmlspecialchars($first_name . ' ' . $last_name) ?></h3>
          <p><?= htmlspecialchars($email) ?></p>
          <span class="role-badge"><?= htmlspecialchars(ucfirst($role)) ?> Administrator</span>
      </div>

      <div class="settings-card">
          <h2>Personal Information</h2>
          
          <form action="/controllers/Actions/update_profile.php" method="POST">
              <input type="hidden" name="user_id" value="<?= $user_id ?>">
              
              <div class="form-grid">
                  <div class="form-group">
                      <label>First Name</label>
                      <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($first_name) ?>" required>
                  </div>
                  <div class="form-group">
                      <label>Last Name</label>
                      <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($last_name) ?>" required>
                  </div>
                  <div class="form-group full-width">
                      <label>Email Address</label>
                      <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
                  </div>
                  <div class="form-group full-width">
                      <label>Username (Cannot be changed)</label>
                      <input type="text" class="form-control" value="<?= htmlspecialchars($username) ?>" disabled>
                  </div>
              </div>
              <button type="submit" class="btn-save">Save Changes</button>
          </form>

          <h2 style="margin-top: 40px;">Change Password</h2>
          <form action="/controllers/Actions/change_password.php" method="POST">
              <input type="hidden" name="user_id" value="<?= $user_id ?>">
              <div class="form-grid">
                  <div class="form-group full-width">
                      <label>Current Password</label>
                      <input type="password" name="current_password" class="form-control" required>
                  </div>
                  <div class="form-group">
                      <label>New Password</label>
                      <input type="password" name="new_password" class="form-control" required>
                  </div>
                  <div class="form-group">
                      <label>Confirm New Password</label>
                      <input type="password" name="confirm_password" class="form-control" required>
                  </div>
              </div>
              <button type="submit" class="btn-save">Update Password</button>
          </form>
      </div>

  </div>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>