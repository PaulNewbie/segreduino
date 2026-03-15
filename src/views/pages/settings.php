<?php
// src/views/pages/settings.php

if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit;
}

require_once __DIR__ . '/../../config/config.php';
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// PAGE SETUP
$page_title = "System Settings - Admin";
$current_page = "settings"; 
$extra_css = '<link rel="stylesheet" href="/assets/css/pages/settings.css" />';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<main id="main-content">
  <div class="head-title">
    <div class="left">
      <h1>System Settings</h1>
      <ul class="breadcrumb">
        <li><a href="/dashboard.php" style="color: #888; pointer-events: auto;">Dashboard</a></li>
        <li><i class="bx bx-chevron-right"></i></li>
        <li><a class="active" href="/settings.php">Settings</a></li>
      </ul>
    </div>
  </div>

  <div class="settings-container">
      
      <form action="#" method="POST" id="settingsForm">
          
          <div class="settings-card">
              <h2><i class='bx bx-slider-alt'></i> General Configuration</h2>
              <div class="form-grid">
                  <div class="form-group full-width">
                      <label>System Name</label>
                      <input type="text" name="system_name" class="form-control" value="SegreDuino Admin" required>
                  </div>
                  <div class="form-group">
                      <label>Timezone</label>
                      <select name="timezone" class="form-control">
                          <option value="Asia/Manila" selected>Asia/Manila (PHT)</option>
                          <option value="UTC">UTC / GMT</option>
                          <option value="America/New_York">America/New_York (EST)</option>
                      </select>
                  </div>
                  <div class="form-group">
                      <label>Date Format</label>
                      <select name="date_format" class="form-control">
                          <option value="M d, Y" selected>Dec 31, 2026</option>
                          <option value="Y-m-d">2026-12-31</option>
                          <option value="d/m/Y">31/12/2026</option>
                      </select>
                  </div>
              </div>
          </div>

          <div class="settings-card">
              <h2><i class='bx bx-tachometer'></i> Kiosk & Bin Thresholds</h2>
              <div class="form-grid">
                  <div class="form-group">
                      <label>Warning Threshold (%)</label>
                      <input type="number" name="warning_threshold" class="form-control" value="50" min="1" max="99" required>
                      <span style="font-size:12px; color:#888;">Turns progress circle orange.</span>
                  </div>
                  <div class="form-group">
                      <label>Critical Threshold (%)</label>
                      <input type="number" name="critical_threshold" class="form-control" value="80" min="2" max="100" required>
                      <span style="font-size:12px; color:#888;">Turns progress circle red and triggers alerts.</span>
                  </div>
              </div>
          </div>

          <div class="settings-card">
              <h2><i class='bx bx-bell'></i> System Preferences</h2>
              
              <div class="toggle-row">
                  <div class="toggle-info">
                      <h4>Email Notifications</h4>
                      <p>Receive email alerts when a bin hits the Critical Threshold.</p>
                  </div>
                  <label class="switch">
                      <input type="checkbox" name="email_alerts" checked>
                      <span class="slider"></span>
                  </label>
              </div>

              <div class="toggle-row">
                  <div class="toggle-info">
                      <h4>Maintenance Mode</h4>
                      <p>Disable Kiosk API access while performing physical repairs.</p>
                  </div>
                  <label class="switch">
                      <input type="checkbox" name="maintenance_mode">
                      <span class="slider"></span>
                  </label>
              </div>
          </div>

          <button type="submit" class="btn-save" onclick="alert('Settings feature ready for backend integration!'); return false;">
              <i class='bx bx-save'></i> Save Configuration
          </button>
      </form>

  </div>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>