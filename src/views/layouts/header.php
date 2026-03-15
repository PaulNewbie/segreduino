<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="/assets/css/style.css" />
  <?php if (isset($extra_css)) echo $extra_css; ?>
  <title><?= $page_title ?? 'SegreDuino Admin' ?></title>
  
  <style>
    /* Dropdown Animations and Styles */
    .profile-dropdown { display: none; position: absolute; right: 0; top: calc(100% + 10px); background: #fff; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.1); width: 160px; padding: 8px 0; z-index: 1000; list-style: none; border: 1px solid #eee; }
    .profile-dropdown.show { display: block; animation: slideDown 0.2s ease forwards; }
    .profile-dropdown li a { display: flex; align-items: center; gap: 10px; padding: 10px 16px; color: #333; font-size: 14px; transition: background 0.2s; }
    .profile-dropdown li a:hover { background: #f9f9f9; color: #27ae60; }
    .profile-dropdown li hr { border: none; border-top: 1px solid #eee; margin: 4px 0; }
    .logout-btn { color: #e74c3c !important; }
    .logout-btn:hover { background: #feebeb !important; color: #c0392b !important; }
    
    .notification-dropdown { padding: 0; border: 1px solid #eee; }
    .notification-dropdown.show { display: block; animation: slideDown 0.2s ease forwards; }
    .notif-header { padding: 12px 16px; font-weight: 600; font-size: 14px; border-bottom: 1px solid #eee; background: #fdfdfd; border-radius: 10px 10px 0 0;}
    .notif-body { max-height: 250px; overflow-y: auto; padding: 16px; text-align: center; color: #888; font-size: 13px; }
    
    @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
  </style>
</head>
<body>
  
  <?php require_once __DIR__ . '/sidebar.php'; ?>

  <section id="content">
    
    <nav>
      <i class="bx bx-menu"></i>
      
      <span class="nav-link" id="liveClock" style="font-weight: 600; color: #64748b; cursor: default; letter-spacing: 0.5px;">
        --:--:--
      </span>
      
      <div style="flex-grow: 1;"></div>

      <div class="notification" id="notifWrapper" style="cursor: pointer; position: relative;">
        <i class="bx bxs-bell" id="notifIcon"></i>
        <span class="num">0</span>
        
        <div class="notification-dropdown" id="notifDropdown">
            <div class="notif-header">Recent Alerts</div>
            <div class="notif-body">
                <i class='bx bx-check-circle' style="font-size: 24px; color: #27ae60; margin-bottom: 8px;"></i><br>
                All systems operating normally.<br>No new alerts.
            </div>
        </div>
      </div>
      
      <div class="profile" id="profileWrapper" style="position: relative; cursor: pointer; display: flex; align-items: center; gap: 8px;">
        <img src="/assets/img/pdm logo.jfif" alt="profile" id="profileIcon" />
        
        <ul class="profile-dropdown" id="profileDropdown">
            <li><a href="#"><i class='bx bx-user'></i> My Profile</a></li>
            <li><a href="#"><i class='bx bx-cog'></i> Settings</a></li>
            <li><hr></li>
            <li><a href="/logout.php" class="logout-btn"><i class='bx bx-log-out'></i> Logout</a></li>
        </ul>
      </div>
    </nav>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
          // 1. Live Clock Logic
          function updateClock() {
              const now = new Date();
              const timeString = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
              const dateString = now.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
              document.getElementById('liveClock').textContent = `${dateString} | ${timeString}`;
          }
          setInterval(updateClock, 1000);
          updateClock();

          // 2. Dropdown Toggle Logic
          const profileWrapper = document.getElementById('profileWrapper');
          const profileDropdown = document.getElementById('profileDropdown');
          
          const notifWrapper = document.getElementById('notifWrapper');
          const notifDropdown = document.getElementById('notifDropdown');

          // Toggle Profile
          profileWrapper.addEventListener('click', function(e) {
              e.stopPropagation();
              notifDropdown.classList.remove('show'); // Close the other one
              profileDropdown.classList.toggle('show');
          });

          // Toggle Notifications
          notifWrapper.addEventListener('click', function(e) {
              e.stopPropagation();
              profileDropdown.classList.remove('show'); // Close the other one
              notifDropdown.classList.toggle('show');
          });

          // Close dropdowns if clicking anywhere else on the screen
          document.addEventListener('click', function(e) {
              if (!profileWrapper.contains(e.target)) {
                  profileDropdown.classList.remove('show');
              }
              if (!notifWrapper.contains(e.target)) {
                  notifDropdown.classList.remove('show');
              }
          });
      });
    </script>