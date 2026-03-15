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
                        $roleClass = (strtolower($row["role"]) === 'admin') ? 'role-admin' : 'role-staff';
                        echo '<tr>';
                        echo '<td><strong>' . htmlspecialchars($row["full_name"]) . '</strong></td>';
                        echo '<td><span class="badge ' . $roleClass . '">' . htmlspecialchars(ucfirst($row["role"])) . '</span></td>';
                        echo '<td>' . htmlspecialchars($row["email"]) . '</td>';
                        echo '<td><span id="status-' . htmlspecialchars($row["user_id"]) . '" class="badge status-loading">Checking...</span></td>';
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
                        $timestamp = strtotime($row['login_time']); // Crucial for JS sorting
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
        <div class="table-controls">
            <input type="text" id="searchAct" class="filter-input search-bar" placeholder="Search action or user..." onkeyup="filterActivity()">
            <div class="filter-group">
                <label style="font-size:14px; color:#555;">Date:</label>
                <input type="date" id="dateStartAct" class="filter-input" onchange="filterActivity()">
                <span style="color:#888;">to</span>
                <input type="date" id="dateEndAct" class="filter-input" onchange="filterActivity()">
            </div>
        </div>

        <div class="table-scroll-wrapper">
            <table class="activity-table" id="actTable">
            <thead>
                <tr>
                <th class="sortable" onclick="sortTable('actTable', 0, true)">Date/Time <i class='bx bx-sort sort-icon'></i></th>
                <th class="sortable" onclick="sortTable('actTable', 1)">User <i class='bx bx-sort sort-icon'></i></th>
                <th class="sortable" onclick="sortTable('actTable', 2)">Action <i class='bx bx-sort sort-icon'></i></th>
                <th class="sortable" onclick="sortTable('actTable', 3)">Details <i class='bx bx-sort sort-icon'></i></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                <td data-time="1747821600">May 21, 2025 - 10:00 AM</td>
                <td><strong>Maria Santos</strong></td><td>Emptied Bin</td><td>Biodegradable Bin (ID: 1)</td>
                </tr>
                <tr>
                <td data-time="1747819800">May 21, 2025 - 09:30 AM</td>
                <td><strong>Juan Dela Cruz</strong></td><td>Emptied Bin</td><td>Non-Biodegradable Bin (ID: 2)</td>
                </tr>
                <tr>
                <td data-time="1747759500">May 20, 2025 - 04:45 PM</td>
                <td><strong>Jose Ramos</strong></td><td>Updated Profile</td><td>Changed email address</td>
                </tr>
            </tbody>
            </table>
        </div>
    </div>

  </div>
</main>

<?php ob_start(); ?>
<script>
// --- 1. TAB SWITCHING ---
function switchTab(evt, tabId) {
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.nav-tab-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    evt.currentTarget.classList.add('active');
}

// --- 2. INSTANT FILTERING LOGIC ---
function filterDirectory() {
    let search = document.getElementById('searchDir').value.toLowerCase();
    let role = document.getElementById('roleDir').value.toLowerCase();
    let rows = document.querySelectorAll('#dirTable tbody tr');
    
    rows.forEach(row => {
        let textMatch = row.innerText.toLowerCase().includes(search);
        let roleMatch = role === 'all' || row.cells[1].innerText.toLowerCase().includes(role);
        row.style.display = (textMatch && roleMatch) ? '' : 'none';
    });
}

function filterLogs() {
    let search = document.getElementById('searchLog').value.toLowerCase();
    let status = document.getElementById('statusLog').value.toLowerCase();
    let start = document.getElementById('dateStartLog').value ? new Date(document.getElementById('dateStartLog').value).setHours(0,0,0,0) / 1000 : 0;
    let end = document.getElementById('dateEndLog').value ? new Date(document.getElementById('dateEndLog').value).setHours(23,59,59,999) / 1000 : Infinity;
    let rows = document.querySelectorAll('#logTable tbody tr');

    rows.forEach(row => {
        let rowTime = parseInt(row.cells[0].getAttribute('data-time'));
        let textMatch = row.innerText.toLowerCase().includes(search);
        let statusMatch = status === 'all' || row.cells[3].innerText.toLowerCase() === status;
        let dateMatch = rowTime >= start && rowTime <= end;
        
        row.style.display = (textMatch && statusMatch && dateMatch) ? '' : 'none';
    });
}

function filterActivity() {
    let search = document.getElementById('searchAct').value.toLowerCase();
    let start = document.getElementById('dateStartAct').value ? new Date(document.getElementById('dateStartAct').value).setHours(0,0,0,0) / 1000 : 0;
    let end = document.getElementById('dateEndAct').value ? new Date(document.getElementById('dateEndAct').value).setHours(23,59,59,999) / 1000 : Infinity;
    let rows = document.querySelectorAll('#actTable tbody tr');

    rows.forEach(row => {
        let rowTime = parseInt(row.cells[0].getAttribute('data-time'));
        let textMatch = row.innerText.toLowerCase().includes(search);
        let dateMatch = rowTime >= start && rowTime <= end;
        
        row.style.display = (textMatch && dateMatch) ? '' : 'none';
    });
}

// --- 3. INSTANT SORTING LOGIC ---
const sortDirections = {};
function sortTable(tableId, colIndex, isDate = false) {
    const table = document.getElementById(tableId);
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    // Toggle sort direction
    sortDirections[tableId] = sortDirections[tableId] || [];
    sortDirections[tableId][colIndex] = !sortDirections[tableId][colIndex];
    let isAsc = sortDirections[tableId][colIndex];

    // Reset icons
    table.querySelectorAll('th i').forEach(icon => icon.className = 'bx bx-sort sort-icon');
    let currentIcon = table.querySelectorAll('th')[colIndex].querySelector('i');
    currentIcon.className = isAsc ? 'bx bx-sort-up sort-icon' : 'bx bx-sort-down sort-icon';

    rows.sort((a, b) => {
        let cellA = a.cells[colIndex];
        let cellB = b.cells[colIndex];
        let valA, valB;

        if (isDate) {
            valA = parseInt(cellA.getAttribute('data-time'));
            valB = parseInt(cellB.getAttribute('data-time'));
        } else {
            valA = cellA.innerText.trim().toLowerCase();
            valB = cellB.innerText.trim().toLowerCase();
        }

        if (valA < valB) return isAsc ? -1 : 1;
        if (valA > valB) return isAsc ? 1 : -1;
        return 0;
    });

    rows.forEach(row => tbody.appendChild(row));
}

// --- 4. ASYNC STATUS CHECKING ---
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('span[id^="status-"]').forEach(function(span) {
        var userId = span.id.replace('status-', '');
        fetch('/views/pages/user_status.php?user_id=' + encodeURIComponent(userId))
            .then(response => response.text())
            .then(status => {
                if (status === 'active') { span.textContent = 'Active'; span.className = 'badge status-active'; }
                else if (status === 'inactive') { span.textContent = 'Inactive'; span.className = 'badge status-inactive'; }
                else { span.textContent = 'Unknown'; span.className = 'badge status-loading'; }
            }).catch(() => { span.textContent = 'Error'; span.className = 'badge status-loading'; });
    });
});
</script>
<?php 
$extra_js = ob_get_clean();
require_once __DIR__ . '/../layouts/footer.php'; 
?>