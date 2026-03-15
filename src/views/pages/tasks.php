<?php
// src/views/pages/tasks.php

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

$filter = $_GET['status'] ?? 'all';
$whereClause = "";

$cleanFilter = strtolower(trim($filter));

if ($cleanFilter === 'pending') {
    $whereClause = "WHERE LOWER(tasks.task_status) LIKE '%pending%'";
} elseif ($cleanFilter === 'in progress') {
    // This will catch "In Progress", "In-Progress", "InProgress", etc.
    $whereClause = "WHERE LOWER(tasks.task_status) LIKE '%progress%'";
} elseif ($cleanFilter === 'done' || $cleanFilter === 'completed') {
    // This catches both "Done" and "Completed" just to be safe
    $whereClause = "WHERE LOWER(tasks.task_status) LIKE '%done%' OR LOWER(tasks.task_status) LIKE '%complete%'";
}

// Count total records for pagination
$countSql = "SELECT COUNT(*) as total FROM tasks $whereClause";
$countResult = $conn->query($countSql);
$totalRows = $countResult->fetch_assoc()['total'] ?? 0;
$totalPages = ceil($totalRows / $limit);

// Fetch task data
$sql = "SELECT users.full_name, tasks.bin_id, tasks.machine_id, tasks.task_description, tasks.task_status, tasks.created_at 
        FROM tasks 
        JOIN users ON tasks.user_id = users.user_id 
        $whereClause 
        ORDER BY tasks.created_at DESC 
        LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Fetch Users for the Modal dropdown
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
$page_title = "Tasks - Admin";
$current_page = "tasks"; 
$extra_css = '<link rel="stylesheet" href="/assets/css/pages/tasks.css" />';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<main id="main-content">
  <div class="head-title">
    <div class="left">
      <h1>Tasks Directory</h1>
        <ul class="breadcrumb">
          <li><a href="/dashboard.php" style="color: #888; text-decoration: none; pointer-events: auto;">Dashboard</a></li>
          <li><i class="bx bx-chevron-right"></i></li>
          <li><a class="active" href="/tasks.php">Tasks</a></li>
        </ul>
    </div>
  </div>

  <div class="card-custom">
    <div class="table-controls">
        <div class="filter-group">
            <label for="statusFilter" style="font-weight: 500; color: #555;">Filter Status:</label>
            <select id="statusFilter" class="filter-select" onchange="window.location.href='?status=' + encodeURIComponent(this.value)">
                <option value="all" <?= strtolower($filter) === 'all' ? 'selected' : '' ?>>All Tasks</option>
                <option value="Pending" <?= strtolower($filter) === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="In Progress" <?= strtolower($filter) === 'in progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="Done" <?= strtolower($filter) === 'done' ? 'selected' : '' ?>>Done</option>
                <option value="Completed" <?= strtolower($filter) === 'completed' ? 'selected' : '' ?>>Completed</option>
            </select>
        </div>
        <button class="btn-primary" id="openTaskModalBtn">
            <i class="bx bx-plus"></i> Add New Task
        </button>
    </div>

    <table class="task-table">
      <thead>
        <tr>
          <th>Assigned Staff</th>
          <th>Kiosk / Bin</th>
          <th>Task Description</th>
          <th>Status</th>
          <th>Date Created</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): 
                // Determine CSS class based on status
                $statusClass = 'pending'; // Default
                if (trim($row["task_status"]) === 'In Progress') $statusClass = 'in-progress';
                if (trim($row["task_status"]) === 'Done' || trim($row["task_status"]) === 'Completed') $statusClass = 'done';
            ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row["full_name"]) ?></strong></td>
                    <td>
                        <div style="font-size:13px; color:#666;">ID: <?= htmlspecialchars($row["machine_id"]) ?></div>
                        <div>Bin: <?= htmlspecialchars($row["bin_id"]) ?></div>
                    </td>
                    <td><?= htmlspecialchars($row["task_description"]) ?></td>
                    <td><span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars(trim($row["task_status"])) ?></span></td>
                    <td><?= htmlspecialchars(date('M d, Y g:i A', strtotime($row["created_at"]))) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5" style="text-align:center; padding: 40px 0; color: #888;">No tasks found for this filter.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?status=<?= urlencode($filter) ?>&page=<?= $i ?>" class="page-btn <?= ($i === $page) ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
  </div>
</main> <div id="taskModal" class="custom-modal">
  <form id="addTasksForm" method="post" action="/controllers/Actions/add_tasks.php" class="modal-form">
    <h2>Add Task</h2>
    <select name="user_id" required>
      <option value="">-- Select Staff Member --</option>
      <?php foreach($users as $user) { echo '<option value="' . htmlspecialchars($user['user_id']) . '">' . htmlspecialchars($user['full_name']) . '</option>'; } ?>
    </select>
    <div style="display:flex; gap:12px;">
        <input type="text" id="machine_id" name="machine_id" placeholder="Machine ID" required style="flex:1;">
        <input type="text" id="bin_id" name="bin_id" placeholder="Bin ID" required style="flex:1;">
    </div>
    <textarea id="task_description" name="task_description" rows="3" placeholder="Task Description" required></textarea>
    <select id="status" name="status" required>
      <option value="">-- Select Status --</option>
      <option value="Pending">Pending</option>
      <option value="In Progress">In Progress</option>
      <option value="Done">Done</option>
    </select>
    <input type="datetime-local" id="created_at" name="created_at" required>
    <div class="modal-actions">
      <button type="submit" class="btn-primary" style="justify-content:center;">Save Task</button>
      <button type="button" class="cancel-btn" id="closeTaskModalBtn">Cancel</button>
    </div>
  </form>
</div>

<?php ob_start(); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const taskModal = document.getElementById('taskModal');
  
  document.getElementById('openTaskModalBtn').onclick = () => taskModal.style.display = 'flex';
  
  document.getElementById('closeTaskModalBtn').onclick = () => { 
      taskModal.style.display = 'none'; 
      document.getElementById('addTasksForm').reset(); 
  };
});
</script>
<?php 
$extra_js = ob_get_clean();
require_once __DIR__ . '/../layouts/footer.php'; 
?>