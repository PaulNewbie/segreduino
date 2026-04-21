<?php
// src/views/pages/user.php

if (!isset($_SESSION['username'])) {
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/../../config/config.php';
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$page_title = "User Management - Admin";
$current_page = "user"; 
$extra_css = '<link rel="stylesheet" href="/assets/css/pages/user.css" />'; 
require_once __DIR__ . '/../layouts/header.php'; 
?>

<main id="main-content">
  <div class="head-title">
    <div class="left">
      <h1>User Management</h1>
      <ul class="breadcrumb">
        <li><a href="/dashboard.php" style="color: #888; text-decoration: none; pointer-events: auto;">Dashboard</a></li>
        <li><i class="bx bx-chevron-right"></i></li>
        <li><a class="active" href="/user.php">Users</a></li>
      </ul>
    </div>
  </div>

  <div class="card-custom">
    <div class="nav-tabs">
        <button class="nav-tab-btn active" onclick="switchTab(event, 'directory-tab')">User Directory</button>
        <button class="nav-tab-btn" onclick="switchTab(event, 'login-history-tab')">Login History</button>
        <button class="nav-tab-btn" onclick="switchTab(event, 'activity-logs-tab')">Activity Logs</button>
    </div>

    <div class="tab-content active" id="directory-tab">
        <div class="table-controls">
            <input type="text" id="searchDir" class="filter-input search-bar" placeholder="Search name or email..." onkeyup="filterDirectory()">
            <div class="filter-group">
                <label style="font-size:14px; color:#555;">Role:</label>
                <select id="roleDir" class="filter-select" onchange="filterDirectory()">
                    <option value="all">All Roles</option>
                    <option value="admin">Admin</option>
                    <option value="staff">Staff</option>
                </select>
            </div>
        </div>

        <div class="table-scroll-wrapper">
            <table class="user-table" id="dirTable">
            <thead>
                <tr>
                <th class="sortable" onclick="sortTable('dirTable', 0)">Name <i class='bx bx-sort sort-icon'></i></th>
                <th class="sortable" onclick="sortTable('dirTable', 1)">Role <i class='bx bx-sort sort-icon'></i></th>
                <th class="sortable" onclick="sortTable('dirTable', 2)">Email <i class='bx bx-sort sort-icon'></i></th>
                <th class="sortable" onclick="sortTable('dirTable', 3)">Status <i class='bx bx-sort sort-icon'></i></th>
                </tr>
            </thead>
            <tbody>
              <?php
                $sql = "SELECT id as user_id, CONCAT(first_name, ' ', last_name) AS full_name, role, status, email 
                        FROM admin_users WHERE role = 'admin'
                        UNION 
                        SELECT user_id, full_name, role, status, email 
                        FROM users WHERE role = 'staff' ORDER BY role ASC, full_name ASC";
                $result = $conn->query($sql);
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        // 1. Role Badge
                        $roleClass = (strtolower($row["role"]) === 'admin') ? 'role-admin' : 'role-staff';
                        
                        // 2. Native PHP Status Badge
                        $rawStatus = strtolower(trim($row["status"] ?? ''));
                        $statusClass = 'status-loading'; // Default gray
                        $displayStatus = 'Unknown';
                        
                        if ($rawStatus === 'active' || $rawStatus === '1') {
                            $statusClass = 'status-active';
                            $displayStatus = 'Active';
                        } elseif ($rawStatus === 'inactive' || $rawStatus === '0') {
                            $statusClass = 'status-inactive';
                            $displayStatus = 'Inactive';
                        } elseif ($rawStatus !== '') {
                            // Catches any other statuses your database might use
                            $displayStatus = ucfirst($rawStatus);
                        }

                        echo '<tr>';
                        echo '<td><strong>' . htmlspecialchars($row["full_name"]) . '</strong></td>';
                        echo '<td><span class="badge ' . $roleClass . '">' . htmlspecialchars(ucfirst($row["role"])) . '</span></td>';
                        echo '<td>' . htmlspecialchars($row["email"]) . '</td>';
                        // Render the badge natively!
                        echo '<td><span class="badge ' . $statusClass . '">' . htmlspecialchars($displayStatus) . '</span></td>';
                        echo '</tr>';
                    }
                }
              ?>
            </tbody>
            </table>
        </div>
    </div>

    <div class="tab-content" id="login-history-tab">
        <div class="table-controls">
            <input type="text" id="searchLog" class="filter-input search-bar" placeholder="Search user..." onkeyup="filterLogs()">
            <div class="filter-group">
                <label style="font-size:14px; color:#555;">Date:</label>
                <input type="date" id="dateStartLog" class="filter-input" onchange="filterLogs()">
                <span style="color:#888;">to</span>
                <input type="date" id="dateEndLog" class="filter-input" onchange="filterLogs()">
            </div>
            <div class="filter-group">
                <label style="font-size:14px; color:#555;">Status:</label>
                <select id="statusLog" class="filter-select" onchange="filterLogs()">
                    <option value="all">All Status</option>
                    <option value="success">Success</option>
                    <option value="failed">Failed</option>
                </select>
            </div>
        </div>

        <div class="table-scroll-wrapper">
            <table class="log-table" id="logTable">
            <thead>
                <tr>
                <th class="sortable" onclick="sortTable('logTable', 0, true)">Date/Time <i class='bx bx-sort sort-icon'></i></th>
                <th class="sortable" onclick="sortTable('logTable', 1)">User <i class='bx bx-sort sort-icon'></i></th>
                <th class="sortable" onclick="sortTable('logTable', 2)">Role <i class='bx bx-sort sort-icon'></i></th>
                <th class="sortable" onclick="sortTable('logTable', 3)">Status <i class='bx bx-sort sort-icon'></i></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT lh.login_time, lh.status AS login_status, u.full_name, u.role FROM login_history AS lh LEFT JOIN users AS u ON lh.user_id = u.user_id ORDER BY lh.login_time DESC LIMIT 100";
                $result = $conn->query($sql);
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $timestamp = strtotime($row['login_time']); 
                        $timeStr = date("M d, Y - g:i A", $timestamp);
                        $roleClass  = ($row['role'] === 'admin') ? 'role-admin' : 'role-staff';
                        $statusClass= (strtolower($row['login_status']) === 'success') ? 'status-active' : 'status-inactive';
                        $statusText = (strtolower($row['login_status']) === 'success') ? 'Success' : 'Failed';

                        echo "<tr>";
                        echo "<td data-time='$timestamp'>".htmlspecialchars($timeStr)."</td>";
                        echo "<td><strong>".htmlspecialchars($row['full_name'] ?: 'Unknown')."</strong></td>";
                        echo "<td><span class=\"badge $roleClass\">".htmlspecialchars(ucfirst($row['role'] ?: 'N/A'))."</span></td>";
                        echo "<td><span class=\"badge $statusClass\">".htmlspecialchars($statusText)."</span></td>";
                        echo "</tr>";
                    }
                }
                ?>
            </tbody>
            </table>
        </div>
    </div>

    <div class="tab-content" id="activity-logs-tab">
        <div class="table-scroll-wrapper">
            <table class="log-table" id="activityTable">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Platform</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fix the ID overlap by joining based on the Platform
                    $log_sql = "SELECT a.*, 
                        CASE 
                            WHEN a.platform = 'Web' THEN CONCAT(au.first_name, ' ', au.last_name)
                            WHEN a.platform = 'Mobile' THEN u.full_name
                            ELSE 'Unknown User'
                        END as display_name,
                        CASE 
                            WHEN a.platform = 'Web' THEN 'Admin'
                            WHEN a.platform = 'Mobile' THEN 'Staff'
                            ELSE 'Unknown'
                        END as role
                        FROM activity_logs a 
                        LEFT JOIN users u ON a.user_id = u.user_id AND a.platform = 'Mobile'
                        LEFT JOIN admin_users au ON a.user_id = au.id AND a.platform = 'Web'
                        ORDER BY a.created_at DESC";
                                
                    $log_result = $conn->query($log_sql);
                    
                    if ($log_result && $log_result->num_rows > 0) {
                        while ($log = $log_result->fetch_assoc()) {
                            $platformClass = ($log['platform'] === 'Web') ? 'badge-web' : 'badge-mobile';
                            $displayName = $log['display_name'] ? $log['display_name'] : 'User #' . $log['user_id'];
                            
                            
                            echo "<tr>";
                            echo "<td>" . date('M d, Y h:i A', strtotime($log['created_at'])) . "</td>";
                            
                            // Display the name WITH a tiny badge indicating if they are Admin or Staff
                            $roleBadge = ($log['role'] === 'Admin') ? '<span class="badge role-admin" style="font-size:10px; padding:2px 4px; margin-left:5px;">Admin</span>' : '<span class="badge role-staff" style="font-size:10px; padding:2px 4px; margin-left:5px;">Staff</span>';
                            
                            echo "<td><strong>" . htmlspecialchars($displayName) . "</strong>" . $roleBadge . "</td>";
                            
                            echo "<td>" . htmlspecialchars($log['action']) . "</td>";
                            echo "<td><span style='padding:4px 8px; border-radius:4px; font-size:12px; background: " . ($log['platform'] == 'Web' ? '#e0f2fe; color: #0284c7;' : '#dcfce7; color: #166534;') . "'>" . htmlspecialchars($log['platform']) . "</span></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' style='text-align:center;'>No activity logs found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
  </div>
</main>

<?php ob_start(); ?>

<script src="/assets/js/table-utils.js"></script>

<script>
// --- 1. TAB SWITCHING ---
function switchTab(evt, tabId) {
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.nav-tab-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    evt.currentTarget.classList.add('active');
}

// --- 2. CLEAN UTILITY FILTER TRIGGERS ---
function filterDirectory() {
    filterGenericTable({
        tableId: 'dirTable',
        searchId: 'searchDir',
        statusId: 'roleDir',
        statusCol: 1 // Role is in column index 1
    });
}

function filterLogs() {
    filterGenericTable({
        tableId: 'logTable',
        searchId: 'searchLog',
        statusId: 'statusLog',
        statusCol: 3, // Status is in column index 3
        startId: 'dateStartLog',
        endId: 'dateEndLog',
        timeCol: 0 // Date is in column index 0
    });
}

function filterActivity() {
    filterGenericTable({
        tableId: 'actTable',
        searchId: 'searchAct',
        startId: 'dateStartAct',
        endId: 'dateEndAct',
        timeCol: 0 // Date is in column index 0
    });
}

// Note: sortTable() is completely removed here because it's handled by table-utils.js natively!

</script>
<?php 
$extra_js = ob_get_clean();
require_once __DIR__ . '/../layouts/footer.php'; 
?>