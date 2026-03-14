<?php
// views/pages/user.php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['username'])) {
    header('Location: /login.php');
    exit;
}

// Connect to the database ONCE for the whole page
require_once __DIR__ . '/../../config/config.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ---------------------------------------------------------
// PAGE SETUP & LAYOUT INCLUSION
// ---------------------------------------------------------
$page_title = "User Management - Admin";
$current_page = "user"; 
$extra_css = '<link rel="stylesheet" href="/views/pages/css/user.css" />'; 

// Pull in the top layout (Head, Sidebar, Navbar)
require_once __DIR__ . '/../layouts/header.php'; 
?>

<main>
  <div class="head-title">
    <div class="left">
      <h1>User Management</h1>
      <ul class="breadcrumb">
        <li></li>
        <li><i class="bx bx-chevron-right"></i></li>
        <li><a class="active" href="/user.php">Users</a></li>
      </ul>
    </div>
  </div>

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
        $sql = "SELECT user_id, full_name, role, status, email FROM users WHERE role = 'staff'";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row["full_name"]) . '</td>';
                echo '<td class="role-staff">' . htmlspecialchars(ucfirst($row["role"])) . '</td>';
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
                $time = date("Y-m-d H:i:s", strtotime($row['login_time']));
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
            echo "<tr><td colspan='4'>No login history found</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>

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

<?php 
// ---------------------------------------------------------
// PAGE SPECIFIC SCRIPTS (Injected into footer)
// ---------------------------------------------------------
ob_start(); 
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Status update logic
    document.querySelectorAll('span[id^="status-"]').forEach(function(span) {
        var userId = span.id.replace('status-', '');
        
        // Note: I updated this path to point directly to the file since it's an API call
        fetch('/views/pages/user_status.php?user_id=' + encodeURIComponent(userId))
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
<?php 
$extra_js = ob_get_clean();

// Pull in the bottom layout
require_once __DIR__ . '/../layouts/footer.php'; 
?>