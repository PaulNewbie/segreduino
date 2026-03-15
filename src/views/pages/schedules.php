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
  <form id="addScheduleForm" method="post" action="/controllers/Actions/add_schedule.php" class="modal-form">
    <h2>Add Schedule</h2>
    <select name="user_id" required>
      <option value="">-- Select Staff Member --</option>
      <?php foreach($users as $user) { echo '<option value="' . htmlspecialchars($user["user_id"]) . '">' . htmlspecialchars($user["full_name"]) . '</option>'; } ?>
    </select>
    <input type="text" name="floor_level" placeholder="E.g., 2nd Floor, Main Wing" required>
    <input type="text" name="task_description" placeholder="Task Description" required>
    <input type="date" name="schedule_date" required>
    <div class="modal-actions">
      <button type="submit" class="btn-primary" style="justify-content:center;">Save Schedule</button>
      <button type="button" class="cancel-btn" id="closeScheduleModalBtn">Cancel</button>
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