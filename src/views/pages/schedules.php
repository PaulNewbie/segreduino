<?php
// src/views/pages/schedules.php

if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit;
}

require_once __DIR__ . '/../../config/config.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ---------------------------------------------------------
// PAGINATION & FILTER LOGIC
// ---------------------------------------------------------
$limit = 10; // Rows per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$filter = $_GET['filter'] ?? 'all';
$whereClause = "";

if ($filter === 'today') {
    $today = date('Y-m-d');
    $whereClause = "WHERE schedules.schedule_date = '$today'";
} elseif ($filter === 'tomorrow') {
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    $whereClause = "WHERE schedules.schedule_date = '$tomorrow'";
} elseif ($filter === 'week') {
    $monday = date('Y-m-d', strtotime('monday this week'));
    $sunday = date('Y-m-d', strtotime('sunday this week'));
    $whereClause = "WHERE schedules.schedule_date BETWEEN '$monday' AND '$sunday'";
}

// Count total records for pagination
$countSql = "SELECT COUNT(*) as total FROM schedules $whereClause";
$countResult = $conn->query($countSql);
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// Fetch data
$sql = "SELECT schedules.schedule_id, users.full_name, schedules.floor_level, schedules.task_description, schedules.schedule_date, schedules.created_at 
        FROM schedules 
        JOIN users ON schedules.user_id = users.user_id 
        $whereClause 
        ORDER BY schedules.schedule_date ASC 
        LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Fetch Users for Modal
$users = [];
$userResult = $conn->query("SELECT user_id, full_name FROM users ORDER BY full_name ASC");
if ($userResult && $userResult->num_rows > 0) {
    while($row = $userResult->fetch_assoc()) {
        $users[] = $row;
    }
}

// Fetch Machines for Modal dropdown
$machines = [];
$machineResult = $conn->query("SELECT machine_id, machine_name FROM machines ORDER BY machine_name ASC");
if ($machineResult && $machineResult->num_rows > 0) {
    while($row = $machineResult->fetch_assoc()) {
        $machines[] = $row;
    }
}

// Fetch Bins to feed to our Javascript
$bins = [];
$binResult = $conn->query("SELECT bin_id, machine_id, bin_type FROM trash_bins ORDER BY bin_type ASC");
if ($binResult && $binResult->num_rows > 0) {
    while($row = $binResult->fetch_assoc()) {
        $bins[] = $row;
    }
}

// ---------------------------------------------------------
// PAGE SETUP
// ---------------------------------------------------------
$page_title = "Schedules - Admin";
$current_page = "schedules"; 
$extra_css = '<link rel="stylesheet" href="/assets/css/pages/schedules.css" />';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<main id="main-content">
  <div class="head-title">
    <div class="left">
      <h1>Schedules Directory</h1>
      <ul class="breadcrumb">
        <li><a href="/dashboard.php" style="color: #888; text-decoration: none; pointer-events: auto;">Dashboard</a></li>
        <li><i class="bx bx-chevron-right"></i></li>
        <li><a class="active" href="/schedules.php">Schedules</a></li>
      </ul>
    </div>
  </div>

  <div class="card-custom">
      
    <?php if (isset($_GET['success'])): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 5px;">
            <?= htmlspecialchars($_GET['success']) ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 5px;">
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <div class="table-controls">
        <input type="text" id="searchSched" class="filter-input search-bar" placeholder="Search user or task..." onkeyup="runSchedFilter()">
        
        <div class="filter-group">
            <label style="font-size:14px; color:#555;">Date:</label>
            <input type="date" id="dateStartSched" class="filter-input" onchange="runSchedFilter()">
            <span style="color:#888;">to</span>
            <input type="date" id="dateEndSched" class="filter-input" onchange="runSchedFilter()">
        </div>

        <div class="filter-group">
            <label for="dateFilter" style="font-weight: 500; color: #555;">View:</label>
            <select id="dateFilter" class="filter-select" onchange="window.location.href='?filter='+this.value">
                <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Upcoming</option>
                <option value="today" <?= $filter === 'today' ? 'selected' : '' ?>>Today</option>
                <option value="tomorrow" <?= $filter === 'tomorrow' ? 'selected' : '' ?>>Tomorrow</option>
                <option value="week" <?= $filter === 'week' ? 'selected' : '' ?>>This Week</option>
            </select>
        </div>

        <button class="btn-primary" id="openScheduleModalBtn">
            <i class="bx bx-plus"></i> Add New Schedule
        </button>
    </div>

    <table class="schedule-table" id="schedTable">
        <thead>
            <tr>
            <th class="sortable" onclick="sortTable('schedTable', 0)">Assigned Staff <i class='bx bx-sort sort-icon'></i></th>
            <th class="sortable" onclick="sortTable('schedTable', 1)">Floor Level <i class='bx bx-sort sort-icon'></i></th>
            <th class="sortable" onclick="sortTable('schedTable', 2)">Task <i class='bx bx-sort sort-icon'></i></th>
            <th class="sortable" onclick="sortTable('schedTable', 3, true)">Date <i class='bx bx-sort sort-icon'></i></th>
            <th>Status</th>
            </tr>
        </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): 
                // Create a timestamp variable for the JS script to read
                $timestamp = strtotime($row["schedule_date"]); 
                $isPast = $timestamp < strtotime('today');
                $statusClass = $isPast ? 'past' : 'upcoming';
                $statusText = $isPast ? 'Past Due' : 'Upcoming';
            ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row["full_name"]) ?></strong></td>
                    <td><?= htmlspecialchars($row["floor_level"]) ?></td>
                    <td><?= htmlspecialchars($row["task_description"]) ?></td>
                    
                    <td data-time="<?= $timestamp ?>"><?= htmlspecialchars(date('M d, Y', $timestamp)) ?></td>
                    
                    <td><span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5" style="text-align:center; padding: 40px 0; color: #888;">No schedules found for this filter.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?filter=<?= $filter ?>&page=<?= $i ?>" class="page-btn <?= ($i === $page) ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
  </div>
</main>

<?php include __DIR__ . '/../components/add_schedule_modal.php'; ?>

<?php ob_start(); ?>

<script src="/assets/js/table-utils.js"></script>

<script>
// Add the filter function pointing to column 3 (Date)
function runSchedFilter() {
    filterGenericTable({
        tableId: 'schedTable', 
        searchId: 'searchSched', 
        startId: 'dateStartSched', 
        endId: 'dateEndSched', 
        timeCol: 3 
    });
}
</script>

<?php include __DIR__ . '/../components/modal_scripts.php'; ?>

<?php 
$extra_js = ob_get_clean();
require_once __DIR__ . '/../layouts/footer.php'; 
?>