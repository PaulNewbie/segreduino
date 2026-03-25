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
$avatar = '';

$sql = "SELECT id, first_name, last_name, email, role, avatar FROM admin_users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($user_id, $first_name, $last_name, $email, $role, $avatar);
$stmt->fetch();
$stmt->close();

// PAGE SETUP
$page_title = "My Profile - Admin";
$current_page = "profile"; 
$extra_css = '<link rel="stylesheet" href="/assets/css/pages/profile.css" />';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<style>
  /* --- Premium Contained Dashboard Layout --- */
  .profile-wrapper {
      /* This max-width ensures beautiful, large margins on desktop screens */
      max-width: 950px; 
      margin: 40px auto; 
      display: grid;
      grid-template-columns: 300px 1fr;
      gap: 30px;
      align-items: start;
  }

  .glass-card {
      background: #ffffff;
      border-radius: 16px;
      padding: 30px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.04);
      border: 1px solid rgba(0,0,0,0.02);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
  }
  .glass-card:hover {
      box-shadow: 0 12px 32px rgba(0,0,0,0.06);
  }

  /* --- Left Side: Profile Identity --- */
  .profile-identity {
      text-align: center;
      position: sticky;
      top: 20px; /* Keeps the card in view when scrolling the right side */
  }
  .profile-avatar-wrapper {
      position: relative;
      width: 140px;
      height: 140px;
      margin: 0 auto 20px;
  }
  .profile-avatar {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 50%;
      border: 4px solid #fff;
      box-shadow: 0 4px 14px rgba(0,0,0,0.1);
  }
  .avatar-upload-btn {
      position: absolute;
      bottom: 0px;
      right: 5px;
      background: #4CAF50;
      color: white;
      width: 42px;
      height: 42px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      cursor: pointer;
      border: 3px solid #fff;
      transition: all 0.2s ease;
      box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
  }
  .avatar-upload-btn:hover {
      background: #45a049;
      transform: scale(1.08);
  }
  .profile-identity h3 {
      margin: 0 0 5px;
      color: #2c3e50;
      font-size: 22px;
      font-weight: 700;
  }
  .profile-identity p {
      color: #7f8c8d;
      margin-bottom: 15px;
      font-size: 14px;
  }
  .role-badge {
      display: inline-block;
      background: rgba(76, 175, 80, 0.1);
      color: #27ae60;
      padding: 6px 16px;
      border-radius: 20px;
      font-size: 13px;
      font-weight: 700;
      letter-spacing: 0.5px;
      text-transform: uppercase;
  }

  /* --- Right Side: Settings Forms --- */
  .settings-section h2 {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 18px;
      color: #2c3e50;
      margin-bottom: 24px;
      font-weight: 600;
  }
  .settings-section h2 i {
      color: #4CAF50;
      font-size: 22px;
      background: rgba(76, 175, 80, 0.1);
      padding: 6px;
      border-radius: 8px;
  }
  
  .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 20px;
  }
  .form-group.full-width {
      grid-column: 1 / -1;
  }
  .form-group label {
      display: block;
      margin-bottom: 8px;
      color: #555;
      font-weight: 600;
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
  }
  .form-control {
      width: 100%;
      padding: 14px 16px;
      border: 1px solid #e0e6ed;
      border-radius: 10px;
      font-size: 15px;
      color: #2c3e50;
      transition: all 0.2s ease;
      background: #f8fafc;
  }
  .form-control:focus {
      outline: none;
      border-color: #4CAF50;
      background: #fff;
      box-shadow: 0 0 0 4px rgba(76, 175, 80, 0.1);
  }
  .form-control:disabled {
      background: #f1f4f8;
      cursor: not-allowed;
      color: #95a5a6;
      border-color: #e0e6ed;
  }

  .btn-save {
      background: #4CAF50;
      color: #fff;
      border: none;
      padding: 14px 28px;
      border-radius: 10px;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.2s ease;
  }
  .btn-save:hover {
      background: #45a049;
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(76, 175, 80, 0.25);
  }

  .section-divider {
      border: none;
      border-top: 1px solid #eef2f5;
      margin: 40px 0;
  }

  /* Alerts */
  .custom-alert {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 16px;
      border-radius: 10px;
      margin-bottom: 24px;
      font-weight: 500;
      font-size: 14px;
  }
  .custom-alert i { font-size: 24px; }
  .alert-danger {
      background: #fdf3f4;
      border: 1px solid #faccd0;
      color: #e74c3c;
  }
  .alert-success {
      background: #f0fdf4;
      border: 1px solid #bbf7d0;
      color: #16a34a;
  }

  /* Responsive Design */
  @media (max-width: 850px) {
      .profile-wrapper {
          grid-template-columns: 1fr;
          max-width: 600px;
          margin: 20px auto;
          padding: 0 15px;
      }
      .profile-identity {
          position: relative;
          top: 0;
      }
  }
  @media (max-width: 500px) {
      .form-grid { grid-template-columns: 1fr; }
  }
</style>

<main id="main-content">
  <div class="head-title" style="max-width: 950px; margin: 0 auto; padding: 0 15px;">
    <div class="left">
      <h1>Account Settings</h1>
      <ul class="breadcrumb">
        <li><a href="/dashboard.php" style="color: #888; pointer-events: auto;">Dashboard</a></li>
        <li><i class="bx bx-chevron-right"></i></li>
        <li><a class="active" href="/profile.php">Profile</a></li>
      </ul>
    </div>
  </div>

  <div class="profile-wrapper">
      
      <div class="glass-card profile-identity">
          <?php 
            $displayAvatar = !empty($avatar) ? htmlspecialchars($avatar) : '/assets/img/pdm logo.jfif'; 
          ?>
          
          <div class="profile-avatar-wrapper">
              <img src="<?= $displayAvatar ?>" alt="Avatar" class="profile-avatar">
              <form action="/controllers/Actions/upload_avatar.php" method="POST" enctype="multipart/form-data" style="margin: 0;">
                  <label class="avatar-upload-btn" title="Change Avatar">
                      <i class='bx bx-camera'></i>
                      <input type="file" name="avatar" style="display:none;" accept="image/*" onchange="this.form.submit();">
                  </label>
              </form>
          </div>
          
          <h3><?= htmlspecialchars($first_name . ' ' . $last_name) ?></h3>
          <p><?= htmlspecialchars($email) ?></p>
          <span class="role-badge"><?= htmlspecialchars($role) ?></span>
      </div>

      <div class="glass-card settings-section">
          
          <?php if (isset($_SESSION['error_msg'])): ?>
              <div class="custom-alert alert-danger">
                  <i class='bx bxs-error-circle'></i>
                  <span><?= htmlspecialchars($_SESSION['error_msg']) ?></span>
              </div>
              <?php unset($_SESSION['error_msg']); ?>
          <?php endif; ?>

          <?php if (isset($_SESSION['success_msg'])): ?>
              <div class="custom-alert alert-success">
                  <i class='bx bxs-check-circle'></i>
                  <span><?= htmlspecialchars($_SESSION['success_msg']) ?></span>
              </div>
              <?php unset($_SESSION['success_msg']); ?>
          <?php endif; ?>

          <h2><i class='bx bx-id-card'></i> Personal Information</h2>
          <form action="/controllers/Actions/update_profile.php" method="POST">
              <input type="hidden" name="source" value="web">
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
                      <label>Username (Non-editable)</label>
                      <input type="text" class="form-control" value="<?= htmlspecialchars($username) ?>" disabled>
                  </div>
              </div>
              <button type="submit" class="btn-save"><i class='bx bx-check'></i> Save Changes</button>
          </form>

          <hr class="section-divider">

          <h2><i class='bx bx-shield-quarter'></i> Security & Password</h2>
          <form action="/controllers/Actions/change_password.php" method="POST">
              <input type="hidden" name="source" value="web">
              <input type="hidden" name="user_id" value="<?= $user_id ?>">
              
              <div class="form-grid">
                  <div class="form-group full-width">
                      <label>Current Password</label>
                      <input type="password" name="old_password" class="form-control" placeholder="Enter current password" required>
                  </div>
                  <div class="form-group">
                      <label>New Password</label>
                      <input type="password" name="new_password" class="form-control" placeholder="Create new password" required>
                  </div>
                  <div class="form-group">
                      <label>Confirm Password</label>
                      <input type="password" name="confirm_password" class="form-control" placeholder="Re-type new password" required>
                  </div>
              </div>
              <button type="submit" class="btn-save"><i class='bx bx-lock-alt'></i> Update Password</button>
          </form>

      </div>
  </div>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>