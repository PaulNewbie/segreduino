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

    <table class="schedule-table">
      <thead>
        <tr>
          <th>Assigned Staff</th>
          <th>Floor Level</th>
          <th>Task</th>
          <th>Date</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): 
                $isPast = strtotime($row["schedule_date"]) < strtotime('today');
                $statusClass = $isPast ? 'past' : 'upcoming';
                $statusText = $isPast ? 'Past Due' : 'Upcoming';
            ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row["full_name"]) ?></strong></td>
                    <td><?= htmlspecialchars($row["floor_level"]) ?></td>
                    <td><?= htmlspecialchars($row["task_description"]) ?></td>
                    <td><?= htmlspecialchars(date('M d, Y', strtotime($row["schedule_date"]))) ?></td>
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

<div id="scheduleModal" class="custom-modal">
  <form id="addScheduleForm" method="post" action="/controllers/Actions/add_schedule.php" class="modal-form" style="padding: 24px; border-radius: 12px; width: 100%; max-width: 420px; gap: 16px; display: flex; flex-direction: column;">
    <h2 style="margin: 0 0 10px 0; font-size: 22px; color: #333;">Add Schedule</h2>
    
    <div>
        <label style="display: block; font-size: 13px; color: #555; margin-bottom: 6px; font-weight: 600;">Assign Staff <span style="color:red;">*</span></label>
        <select name="user_id" required style="width: 100%; padding: 12px 14px; border-radius: 8px; border: 1px solid #ddd; outline: none; font-size: 14px; background: #fafafa; cursor: pointer;">
          <option value="">-- Select Staff Member --</option>
          <?php foreach($users as $user) { echo '<option value="' . htmlspecialchars($user["user_id"]) . '">' . htmlspecialchars($user["full_name"]) . '</option>'; } ?>
        </select>
    </div>
    
    <div>
        <label style="display: block; font-size: 13px; color: #555; margin-bottom: 6px; font-weight: 600;">Floor Level <span style="color:red;">*</span></label>
        <select name="floor_level" required style="width: 100%; padding: 12px 14px; border-radius: 8px; border: 1px solid #ddd; outline: none; font-size: 14px; background: #fafafa; cursor: pointer;">
          <option value="">-- Select Floor Level --</option>
          <option value="1st Floor">1st Floor</option>
          <option value="2nd Floor">2nd Floor</option>
          <option value="3rd Floor">3rd Floor</option>
        </select>
    </div>

    <div>
        <label style="display: block; font-size: 13px; color: #555; margin-bottom: 6px; font-weight: 600;">Task Description <span style="color:red;">*</span></label>
        <input type="text" list="commonTasks" name="task_description" placeholder="Select from list or type a custom task..." required style="width: 100%; padding: 12px 14px; border-radius: 8px; border: 1px solid #ddd; outline: none; font-size: 14px; background: #fafafa;">
        <datalist id="commonTasks">
            <option value="Empty All Bins">
            <option value="Perform Routine Maintenance">
            <option value="Clean Kiosk Area">
            <option value="Inspect Sensors">
        </datalist>
        <small style="color: #888; font-size: 12px; margin-top: 6px; display: block;">
            <i class='bx bx-info-circle'></i> Double-click the input box to see common tasks.
        </small>
    </div>

    <div>
        <label style="display: block; font-size: 13px; color: #555; margin-bottom: 6px; font-weight: 600;">Schedule Date <span style="color:red;">*</span></label>
        <input type="date" name="schedule_date" required style="width: 100%; padding: 12px 14px; border-radius: 8px; border: 1px solid #ddd; outline: none; font-size: 14px; background: #fafafa; cursor: pointer;">
    </div>

    <div class="modal-actions" style="display: flex; gap: 12px; margin-top: 10px;">
      <button type="submit" class="btn-primary" style="flex: 1; justify-content: center; padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer;">Save Schedule</button>
      <button type="button" class="cancel-btn" id="closeScheduleModalBtn" style="flex: 1; justify-content: center; padding: 12px; border-radius: 8px; font-weight: 600; background: #f1f1f1; color: #333; border: none; cursor: pointer;">Cancel</button>
    </div>
  </form>
</div>

<?php ob_start(); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const scheduleModal = document.getElementById('scheduleModal');
  document.getElementById('openScheduleModalBtn').onclick = () => scheduleModal.style.display = 'flex';
  document.getElementById('closeScheduleModalBtn').onclick = () => { 
      scheduleModal.style.display = 'none'; 
      document.getElementById('addScheduleForm').reset(); 
  };
});
</script>
<?php 
$extra_js = ob_get_clean();
require_once __DIR__ . '/../layouts/footer.php'; 
?>