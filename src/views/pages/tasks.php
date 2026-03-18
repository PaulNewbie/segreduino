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
    $whereClause = "WHERE LOWER(tasks.task_status) LIKE '%progress%'";
} elseif ($cleanFilter === 'done' || $cleanFilter === 'completed') {
    $whereClause = "WHERE LOWER(tasks.task_status) LIKE '%done%' OR LOWER(tasks.task_status) LIKE '%complete%'";
}

// Count total records for pagination
$countSql = "SELECT COUNT(*) as total FROM tasks $whereClause";
$countResult = $conn->query($countSql);
$totalRows = $countResult->fetch_assoc()['total'] ?? 0;
$totalPages = ceil($totalRows / $limit);

// Fetch task data WITH Machine and Bin Names (Using JOINs)
$sql = "SELECT users.full_name, tasks.task_description, tasks.task_status, tasks.created_at, 
               machines.machine_name, trash_bins.bin_type 
        FROM tasks 
        JOIN users ON tasks.user_id = users.user_id 
        LEFT JOIN machines ON tasks.machine_id = machines.machine_id
        LEFT JOIN trash_bins ON tasks.bin_id = trash_bins.bin_id
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
            <i class="bx bx-plus"></i> Add Task
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
                $statusClass = 'pending';
                if (trim($row["task_status"]) === 'In Progress') $statusClass = 'in-progress';
                if (trim($row["task_status"]) === 'Done' || trim($row["task_status"]) === 'Completed') $statusClass = 'done';
            ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row["full_name"]) ?></strong></td>
                    <td>
                        <div style="font-size:14px; font-weight:600; color:#333;"><?= htmlspecialchars($row["machine_name"] ?? 'Unknown Machine') ?></div>
                        <div style="font-size:13px; color:#666;"><?= htmlspecialchars($row["bin_type"] ?? 'Unknown Bin') ?></div>
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
</main> 

<div id="taskModal" class="custom-modal">
  <form id="addTasksForm" method="post" action="/controllers/Actions/add_tasks.php" class="modal-form">
    <h2>Add Task</h2>
    <select name="user_id" required>
      <option value="">-- Select Staff Member --</option>
      <?php foreach($users as $user) { echo '<option value="' . htmlspecialchars($user['user_id']) . '">' . htmlspecialchars($user['full_name']) . '</option>'; } ?>
    </select>
    
    <div style="display:flex; gap:12px;">
        <select id="machine_id" name="machine_id" required style="flex:1;">
            <option value="">-- Select Machine --</option>
            <?php foreach($machines as $machine): ?>
                <option value="<?= htmlspecialchars($machine['machine_id']) ?>"><?= htmlspecialchars($machine['machine_name']) ?></option>
            <?php endforeach; ?>
        </select>

        <select id="bin_id" name="bin_id" required style="flex:1;">
            <option value="">-- Select Bin --</option>
            </select>
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
  const machineSelect = document.getElementById('machine_id');
  const binSelect = document.getElementById('bin_id');
  
  // Pass PHP bins array to JS
  const allBins = <?= json_encode($bins) ?>;

  // Handle Cascading Dropdown functionality
  machineSelect.addEventListener('change', function() {
      const selectedMachineId = this.value;
      
      // Reset Bin dropdown
      binSelect.innerHTML = '<option value="">-- Select Bin --</option>';
      
      if (selectedMachineId) {
          // Filter bins matching the selected machine
          const filteredBins = allBins.filter(bin => bin.machine_id == selectedMachineId);
          
          if(filteredBins.length === 0) {
              binSelect.innerHTML = '<option value="">No Bins Available</option>';
          } else {
              // Add matched bins to the dropdown
              filteredBins.forEach(bin => {
                  const option = document.createElement('option');
                  option.value = bin.bin_id;
                  option.textContent = bin.bin_type; 
                  binSelect.appendChild(option);
              });
          }
      }
  });

  document.getElementById('openTaskModalBtn').onclick = () => taskModal.style.display = 'flex';
  
  document.getElementById('closeTaskModalBtn').onclick = () => { 
      taskModal.style.display = 'none'; 
      document.getElementById('addTasksForm').reset();
      binSelect.innerHTML = '<option value="">-- Select Bin --</option>'; // Reset dropdown on close
  };
});
</script>
<?php 
$extra_js = ob_get_clean();
require_once __DIR__ . '/../layouts/footer.php'; 
?>