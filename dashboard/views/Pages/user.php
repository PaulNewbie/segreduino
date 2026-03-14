<?php
session_start();
$_SESSION['username'] = 'Admin User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="../../assets/css/style.css" />
  <title>User Management</title>
  <style>
    /* Table and Card Styles */
    .user-table, .log-table, .activity-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }

    .user-table th, .user-table td, 
    .log-table th, .log-table td,
    .activity-table th, .activity-table td {
      padding: 12px 16px;
      text-align: left;
      border-bottom: 1px solid #eee;
      font-size: 16px;
    }

    .user-table th, .log-table th, .activity-table th {
      background-color: var(--light-green);
      color: green;
      font-weight: 600;
    }

    .user-table tr:hover, .log-table tr:hover {
      background-color: inherit;
    }

    /* Role and Status Styles */
    .role-admin { color: #e67e22; font-weight: bold; }
    .role-staff { color: #2980b9; font-weight: bold; }
    .status-active { color: #27ae60; font-weight: bold; }
    .status-inactive { color: #e74c3c; font-weight: bold; }

    /* Card Style */
    .card-custom {
      background: var(--light-grey);
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.05);
      margin-top: 20px;
      transition: background 0.3s;
    }

    /* Responsive Design */
    @media (max-width: 900px) {
      .card-custom { padding: 10px; }
      .user-table th, .user-table td, 
      .log-table th, .log-table td, 
      .activity-table th, .activity-table td {
        padding: 8px 6px;
        font-size: 14px;
      }
    }
  </style>
</head>
<body>

 <section id="sidebar">
    <a href="#" class="brand">
      <i class="bx bxs-chip"></i>
      <span class="text">SegreDuino</span>
      <span class="text">Admin</span>
    </a>
    <ul class="side-menu top">
      <li>
        <a href="dashboard.php" id="dashboard-link">
          <i class="bx bxs-dashboard"></i>
          <span class="text">Dashboard</span>
        </a>
      </li>
      <li class="active">
        <a href="user.php" id="users-link">
          <i class="bx bxs-user"></i>
          <span class="text">Users</span>
        </a>
      </li>
      <li>
        <a href="bin.php">
          <i class="bx bxs-shopping-bag-alt"></i>
          <span class="text">Bin Monitoring</span>
        </a>
      </li>
      <li>
        <a href="history.php" id="history-reports-link">
          <i class="bx bxs-message-dots"></i>
          <span class="text">History & Reports</span>
        </a>
      </li>
      <li>
        <a href="logout.php" class="logout">
          <i class="bx bxs-log-out-circle"></i>
          <span class="text">Logout</span>
        </a>
      </li>
    </ul>
  </section>

<!-- CONTENT -->
<section id="content">
  <!-- NAVBAR -->
  <nav>
    <i class="bx bx-menu"></i>
    <a href="#" class="nav-link">User Management</a>
    <form action="#">
      <div class="form-input">
        <input type="search" placeholder="Search..." />
        <button type="submit" class="search-btn">
          <i class="bx bx-search"></i>
        </button>
      </div>
    </form>
    <a href="#" class="notification">
      <i class="bx bxs-bell"></i>
      <span class="num">0</span>
      <div class="notification-dropdown">
        <!-- Notifications will be dynamically inserted here -->
      </div>
    </a>
    <a href="#" class="profile">
      <img src="../../assets/img/pdm logo.jfif" alt="profile" />
    </a>
  </nav>

  <!-- MAIN -->
  <main>
    <div class="head-title">
      <div class="left">
        <h1>User Management</h1>
        <ul class="breadcrumb">
          <li></li>
          <li><i class="bx bx-chevron-right"></i></li>
          <li><a class="active" href="#">Users</a></li>
        </ul>
      </div>
    </div>

    <!-- Admin Users Table -->
    <div class="card-custom">
      <h2>Admin</h2>
      <table class="user-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Role</th>
            <th>Status</th>
            <th>Email</th>
          </tr>
        </thead>
        <tbody>
          <?php
          require_once __DIR__ . '/../../config/config.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

   $sql = "SELECT id, first_name, last_name, role, status, email FROM admin_users WHERE role = 'admin'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row["first_name"] . " " . $row["last_name"]) . '</td>';
        echo '<td class="role-admin">' . htmlspecialchars(ucfirst($row["role"])) . '</td>';
        echo '<td><span id="status-' . htmlspecialchars($row["id"]) . '" class="status-loading">Checking...</span></td>';
        echo '<td>' . htmlspecialchars($row["email"]) . '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="4">No admin found</td></tr>';
}

          
          ?>
        </tbody>
      </table>
    </div>

    <!-- Staff Users Table -->
    <div class="card-custom">
      <h2>Staff</h2>
      <table class="user-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Role</th>
            <th>Status</th>
            <th>Email</th>
          </tr>
        </thead>
        <tbody>
          <?php
          // Database connection (update credentials as needed)
          require_once __DIR__ . '/../../config/config.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

          // Fetch staff users
          $sql = "SELECT user_id, full_name, role, status, email FROM users WHERE role = 'staff'";
          $result = $conn->query($sql);

          if ($result && $result->num_rows > 0) {
              while($row = $result->fetch_assoc()) {
                  echo '<tr>';
                  // Show name in the table
                  echo '<td>' . htmlspecialchars($row["full_name"]) . '</td>';
                  echo '<td class="role-staff">' . htmlspecialchars(ucfirst($row["role"])) . '</td>';
                  // Use user_id for AJAX status span
                  echo '<td><span id="status-' . htmlspecialchars($row["user_id"]) . '" class="status-loading">Checking...</span></td>';
                  echo '<td>' . htmlspecialchars($row["email"]) . '</td>';
                  echo '</tr>';
              }
          } else {
              echo '<tr><td colspan="4">No staff found</td></tr>';
          }
          
          ?>
        </tbody>
      </table>
    </div>

   

  <!-- Login History Table -->
<div class="card-custom">
  <h2>Login History</h2>
  <table class="log-table">
    <thead>
      <tr>
        <th>Date/Time</th>
        <th>User</th>
        <th>Role</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php
      require_once __DIR__ . '/../../config/config.php';
      if ($conn->connect_error) {
          die("Connection failed: " . $conn->connect_error);
      }

      // Kunin ang pinakabagong 20 login records kasama ang user name at role
      $sql = "
          SELECT lh.login_time,
                 lh.status AS login_status,
                 u.full_name,
                 u.role
          FROM login_history AS lh
          LEFT JOIN users AS u ON lh.user_id = u.user_id
          ORDER BY lh.login_time DESC
          LIMIT 20
      ";
      $result = $conn->query($sql);

      if ($result && $result->num_rows > 0) {
          while($row = $result->fetch_assoc()) {
              // format ng oras
              $time = date("Y-m-d H:i:s", strtotime($row['login_time']));
              // role at status class para sa kulay
              $roleClass  = ($row['role'] === 'admin') ? 'role-admin' : 'role-staff';
              $statusText = (strtolower($row['login_status']) === 'success') ? 'Success' : 'Failed';
              $statusClass= (strtolower($row['login_status']) === 'success') ? 'status-active' : 'status-inactive';

              echo "<tr>";
              echo "<td>".htmlspecialchars($time)."</td>";
              echo "<td>".htmlspecialchars($row['full_name'] ?: 'Unknown')."</td>";
              echo "<td class=\"$roleClass\">".htmlspecialchars(ucfirst($row['role'] ?: 'N/A'))."</td>";
              echo "<td class=\"$statusClass\">".htmlspecialchars($statusText)."</td>";
              echo "</tr>";
          }
      } else {
          echo "<tr><td colspan='5'>No login history found</td></tr>";
      }

      
      ?>
    </tbody>
  </table>
</div>


    <!-- Activity Logs Table -->
    <div class="card-custom">
      <h2>Activity Logs</h2>
      <table class="activity-table">
        <thead>
          <tr>
            <th>Date/Time</th>
            <th>User</th>
            <th>Action</th>
            <th>Details</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>2025-05-21 10:00</td>
            <td>Maria Santos</td>
            <td>Emptied Bin</td>
            <td>Biodegradable Bin (ID: 1)</td>
          </tr>
          <tr>
            <td>2025-05-21 09:30</td>
            <td>Juan Dela Cruz</td>
            <td>Emptied Bin</td>
            <td>Non-Biodegradable Bin (ID: 2)</td>
          </tr>
          <tr>
            <td>2025-05-20 16:45</td>
            <td>Jose Ramos</td>
            <td>Updated Profile</td>
            <td>Changed email address</td>
          </tr>
        </tbody>
      </table>
    </div>
  </main>
</section>

<!-- Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Status update logic
    document.querySelectorAll('span[id^="status-"]').forEach(function(span) {
        var userId = span.id.replace('status-', '');
        fetch('user_status.php?user_id=' + encodeURIComponent(userId))
            .then(response => response.text())
            .then(status => {
                if (status === 'active') {
                    span.textContent = 'Active';
                    span.className = 'status-active';
                } else if (status === 'inactive') {
                    span.textContent = 'Inactive';
                    span.className = 'status-inactive';
                } else {
                    span.textContent = 'Unknown';
                    span.className = '';
                }
            })
            .catch(() => {
                span.textContent = 'Error';
                span.className = '';
            });
    });
});
</script>
<script src="../../assets/js/script.js"></script>
</body>
</html>