<?php
// src/views/pages/dashboard.php

if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit;
}

require_once __DIR__ . '/../../config/config.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ---------------------------------------------------------
// DATA FETCHING LOGIC
// ---------------------------------------------------------
$bin_types = [];
$where = "";
$params = [];
$param_types = "";

if (isset($_GET['kiosk']) && $_GET['kiosk'] !== "") {
    $where = "WHERE machine_id = ?";
    $params[] = $_GET['kiosk'];
    $param_types .= "s";
}

$sql = "SELECT DISTINCT bin_type FROM trash_bins $where";
$stmt = $conn->prepare($sql);
if ($where) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $bin_types[] = $row['bin_type'];
}
$stmt->close();

$bin_data = [];
foreach ($bin_types as $type) {
    if ($where) {
        $stmt = $conn->prepare("SELECT tb.bin_status, tb.last_updated, tb.floor_level, m.machine_name FROM trash_bins tb JOIN machines m ON tb.machine_id = m.machine_id WHERE tb.bin_type = ? AND tb.machine_id = ? ORDER BY tb.last_updated DESC LIMIT 1");
        $stmt->bind_param("ss", $type, $_GET['kiosk']);
    } else {
        $stmt = $conn->prepare("SELECT tb.bin_status, tb.last_updated, tb.floor_level, m.machine_name FROM trash_bins tb JOIN machines m ON tb.machine_id = m.machine_id WHERE tb.bin_type = ? ORDER BY tb.last_updated DESC LIMIT 1");
        $stmt->bind_param("s", $type);
    }
    $stmt->execute();
    $stmt->bind_result($status, $last_updated, $floor_level, $machine_name);
    if ($stmt->fetch()) {
        $bin_data[$type] = ['status' => $status, 'last_updated' => $last_updated, 'floor_level' => $floor_level, 'machine_name' => $machine_name];
    }
    $stmt->close();
}

$kiosks = [];
$result = $conn->query("SELECT machine_id, machine_name FROM machines ORDER BY machine_name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $kiosks[] = $row;
    }
}

// Fetch Users for the dropdowns
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
$page_title = "Dashboard - Admin";
$current_page = "dashboard"; 
// Cache-busting query string added to CSS to force browser to load the new styles
$extra_css = '<link rel="stylesheet" href="/assets/css/pages/dashboard.css?v=' . time() . '" />';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<main id="main-content">
  <div class="head-title">
    <div class="left">
      <h1>Dashboard Overview</h1>
      <ul class="breadcrumb">
        <li></li>
        <li><i class="bx bx-chevron-right"></i></li>
        <li><a class="active" href="/dashboard.php">Home</a></li>
      </ul>
    </div>
  </div>

  <div class="bin-grid" id="binGrid">
    <?php foreach ($bin_types as $type): ?>
      <?php 
        $status = (int)($bin_data[$type]['status'] ?? 0); 
        $color = "#6a5acd"; 
        if ($status < 50) { $color = "#1abc3a"; } 
        elseif ($status < 80) { $color = "#f39c12"; } 
        else { $color = "#e74c3c"; }
      ?>
      <div class="bin-card" data-bin="<?php echo htmlspecialchars($type); ?>">
        <h4><?php echo htmlspecialchars($type); ?></h4>
        <div class="progress-circle" style="--value: <?= $status ?>%; --color: <?= $color ?>;">
          <span><?= $status ?>%</span>
        </div>
        <p class="bin-date" style="font-size: 13px; color: #666;">Last Updated: <?php echo htmlspecialchars($bin_data[$type]['last_updated'] ?? 'N/A'); ?></p>
        <p class="bin-floor" style="font-size: 14px; margin-top: 6px;">Floor: <strong><?php echo htmlspecialchars($bin_data[$type]['floor_level'] ?? 'N/A'); ?></strong></p>
        <p class="bin-machine" style="font-size: 14px;">Kiosk: <strong><?php echo htmlspecialchars($bin_data[$type]['machine_name'] ?? 'N/A'); ?></strong></p>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="nav-tabs">
      <button class="nav-tab-btn active" onclick="switchTab(event, 'schedules-tab')">Recent Schedules</button>
      <button class="nav-tab-btn" onclick="switchTab(event, 'tasks-tab')">Recent Tasks</button>
  </div>

  <div class="table-data">
    
    <div class="order tab-content active" id="schedules-tab">
      <div class="head">
        <h3>Schedule Overview</h3>
        <button class="empty-btn" id="openScheduleModalBtn"><i class="bx bx-plus"></i> Add Schedule</button>
      </div>
      
      <div class="table-controls">
          <input type="text" id="searchSched" class="filter-input search-bar" placeholder="Search user or task..." onkeyup="runSchedFilter()">
          <div class="filter-group">
              <label style="font-size:14px; color:#555;">Date:</label>
              <input type="date" id="dateStartSched" class="filter-input" onchange="runSchedFilter()">
              <span style="color:#888;">to</span>
              <input type="date" id="dateEndSched" class="filter-input" onchange="runSchedFilter()">
          </div>
      </div>

      <div class="table-scroll-wrapper">
          <table id="dashSchedTable">
          <thead>
              <tr>
              <th class="sortable" onclick="sortTable('dashSchedTable', 0)">Name <i class='bx bx-sort sort-icon'></i></th>
              <th class="sortable" onclick="sortTable('dashSchedTable', 1)">Floor Level <i class='bx bx-sort sort-icon'></i></th>
              <th class="sortable" onclick="sortTable('dashSchedTable', 2)">Task <i class='bx bx-sort sort-icon'></i></th>
              <th class="sortable" onclick="sortTable('dashSchedTable', 3, true)">Schedule Date <i class='bx bx-sort sort-icon'></i></th>
              </tr>
          </thead>
          <tbody>
              <?php
              $sql = "SELECT users.full_name, schedules.floor_level, schedules.task_description, schedules.schedule_date 
                      FROM schedules JOIN users ON schedules.user_id = users.user_id 
                      ORDER BY schedules.schedule_date ASC LIMIT 50";
              $result = $conn->query($sql);
              if ($result && $result->num_rows > 0) {
                  while($row = $result->fetch_assoc()) {
                      $timestamp = strtotime($row["schedule_date"]);
                      echo '<tr>';
                      echo '<td><strong>'.htmlspecialchars($row["full_name"]).'</strong></td>';
                      echo '<td>'.htmlspecialchars($row["floor_level"]).'</td>';
                      echo '<td>'.htmlspecialchars($row["task_description"]).'</td>';
                      echo "<td data-time='$timestamp'>".htmlspecialchars(date('M d, Y', $timestamp))."</td>";
                      echo '</tr>';
                  }
              } else {
                  echo '<tr><td colspan="4" style="text-align:center; padding: 20px;">No recent schedules found</td></tr>';
              }
              ?>
          </tbody>
          </table>
      </div>
      <a href="/schedules.php" class="view-all-link">View All Schedules in Directory</a>
    </div>

    <div class="order tab-content" id="tasks-tab">
      <div class="head">
        <h3>Task Overview</h3>
        <button class="empty-btn" id="openTaskModalBtn"><i class="bx bx-plus"></i> Add Task</button>
      </div>

      <div class="table-controls">
          <input type="text" id="searchTask" class="filter-input search-bar" placeholder="Search user or bin..." onkeyup="runTaskFilter()">
          <div class="filter-group">
              <label style="font-size:14px; color:#555;">Status:</label>
              <select id="statusTask" class="filter-select" onchange="runTaskFilter()">
                  <option value="all">All Status</option>
                  <option value="pending">Pending</option>
                  <option value="in progress">In Progress</option>
                  <option value="done">Done</option>
              </select>
          </div>
          <div class="filter-group">
              <label style="font-size:14px; color:#555;">Created:</label>
              <input type="date" id="dateStartTask" class="filter-input" onchange="runTaskFilter()">
              <span style="color:#888;">to</span>
              <input type="date" id="dateEndTask" class="filter-input" onchange="runTaskFilter()">
          </div>
      </div>

      <div class="table-scroll-wrapper">
          <table id="dashTaskTable">
          <thead>
              <tr>
              <th class="sortable" onclick="sortTable('dashTaskTable', 0)">User <i class='bx bx-sort sort-icon'></i></th>
              <th class="sortable" onclick="sortTable('dashTaskTable', 1)">Bin ID <i class='bx bx-sort sort-icon'></i></th>
              <th class="sortable" onclick="sortTable('dashTaskTable', 2)">Task <i class='bx bx-sort sort-icon'></i></th>
              <th class="sortable" onclick="sortTable('dashTaskTable', 3)">Status <i class='bx bx-sort sort-icon'></i></th>
              <th class="sortable" onclick="sortTable('dashTaskTable', 4, true)">Created At <i class='bx bx-sort sort-icon'></i></th>
              </tr>
          </thead>
          <tbody>
              <?php
              $sql = "SELECT users.full_name, tasks.bin_id, tasks.task_description, tasks.task_status, tasks.created_at 
                      FROM tasks JOIN users ON tasks.user_id = users.user_id 
                      ORDER BY tasks.created_at DESC LIMIT 50";
              $result = $conn->query($sql);
              if ($result && $result->num_rows > 0) {
                  while ($row = $result->fetch_assoc()) {
                      $timestamp = strtotime($row["created_at"]);
                      
                      $statusClass = 'pending'; 
                      if (trim($row["task_status"]) === 'In Progress') $statusClass = 'in-progress';
                      if (trim($row["task_status"]) === 'Done' || trim($row["task_status"]) === 'Completed') $statusClass = 'done';

                      echo '<tr>';
                      echo '<td><strong>' . htmlspecialchars($row["full_name"]) . '</strong></td>';
                      echo '<td>Bin: ' . htmlspecialchars($row["bin_id"]) . '</td>';
                      echo '<td>' . htmlspecialchars($row["task_description"]) . '</td>';
                      
                      // Status Badge coloring inline
                      $bg = "#fff3e0"; $txt = "#e65100";
                      if ($statusClass == 'in-progress') { $bg = "#e3f2fd"; $txt = "#1565c0"; }
                      if ($statusClass == 'done') { $bg = "#e8f5e9"; $txt = "#2e7d32"; }

                      echo '<td><span style="background:'.$bg.'; color:'.$txt.'; padding:4px 10px; border-radius:20px; font-size:12px; font-weight:600;">' . htmlspecialchars(trim($row["task_status"])) . '</span></td>';
                      echo "<td data-time='$timestamp'>" . htmlspecialchars(date('M d, Y', $timestamp)) . '</td>';
                      echo '</tr>';
                  }
              } else {
                  echo '<tr><td colspan="5" style="text-align:center; padding: 20px;">No recent tasks found</td></tr>';
              }
              ?>
          </tbody>
          </table>
      </div>
      <a href="/tasks.php" class="view-all-link">View All Tasks in Directory</a>
    </div>
    
  </div>
</main>

<div id="scheduleModal" class="custom-modal">
  <form id="addScheduleForm" method="post" action="/controllers/Actions/add_schedule.php" class="modal-form">
    <h2>Add Schedule</h2>
    <select name="user_id" required>
      <option value="">-- Select User --</option>
      <?php foreach($users as $user) { echo '<option value="' . htmlspecialchars($user["user_id"]) . '">' . htmlspecialchars($user["full_name"]) . '</option>'; } ?>
    </select>
    <input type="text" name="floor_level" placeholder="Floor Level" required>
    <input type="text" name="task_description" placeholder="Task Description" required>
    <input type="date" name="schedule_date" required>
    <div class="modal-actions">
      <button type="submit" class="empty-btn" style="justify-content:center;">Save Schedule</button>
      <button type="button" class="cancel-btn" id="closeScheduleModalBtn">Cancel</button>
    </div>
  </form>
</div>

<div id="taskModal" class="custom-modal">
  <form id="addTasksForm" method="post" action="/controllers/Actions/add_tasks.php" class="modal-form">
    <h2>Add Task</h2>
    <select name="user_id" required>
      <option value="">-- Select User --</option>
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
      <button type="submit" class="empty-btn" style="justify-content:center;">Save Task</button>
      <button type="button" class="cancel-btn" id="closeTaskModalBtn">Cancel</button>
    </div>
  </form>
</div>

<?php ob_start(); ?>

<script src="/assets/js/table-utils.js"></script>

<script>
// Filter Setup
function runSchedFilter() {
    filterGenericTable({
        tableId: 'dashSchedTable', searchId: 'searchSched', startId: 'dateStartSched', endId: 'dateEndSched', timeCol: 3
    });
}
function runTaskFilter() {
    filterGenericTable({
        tableId: 'dashTaskTable', searchId: 'searchTask', statusId: 'statusTask', statusCol: 3, startId: 'dateStartTask', endId: 'dateEndTask', timeCol: 4
    });
}

// Tab Switching
function switchTab(evt, tabId) {
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.nav-tab-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    evt.currentTarget.classList.add('active');
}

document.addEventListener('DOMContentLoaded', function() {
  const scheduleModal = document.getElementById('scheduleModal');
  const taskModal = document.getElementById('taskModal');

  document.getElementById('openScheduleModalBtn').onclick = () => scheduleModal.style.display = 'flex';
  document.getElementById('closeScheduleModalBtn').onclick = () => { scheduleModal.style.display = 'none'; document.getElementById('addScheduleForm').reset(); };
  
  document.getElementById('openTaskModalBtn').onclick = () => taskModal.style.display = 'flex';
  document.getElementById('closeTaskModalBtn').onclick = () => { taskModal.style.display = 'none'; document.getElementById('addTasksForm').reset(); };

  // Live Bin Data
  async function fetchLiveBinData() {
    try {
      const response = await fetch("/controllers/Api/fetch_bin_status.php");
      const bins = await response.json();
      bins.forEach(bin => {
        const { bin_type, bin_status, last_updated } = bin;
        const percent = parseInt(bin_status);
        let color = "#6a5acd";
        if (percent < 50) color = "#1abc3a";
        else if (percent < 80) color = "#f39c12";
        else color = "#e74c3c";

        const binCard = document.querySelector(`[data-bin="${bin_type}"]`);
        if (binCard) {
          const circle = binCard.querySelector(".progress-circle");
          if (circle) {
            circle.style.background = `conic-gradient(${color} ${percent}%, #e6e6e6 0)`;
            circle.style.color = color;
            const label = circle.querySelector("span");
            if (label) label.textContent = percent + "%";
          }
          const last = binCard.querySelector(".bin-date");
          if (last) last.textContent = "Last Updated: " + last_updated;
        }
      });
    } catch (err) { console.error("Live update error:", err); }
  }
  setInterval(fetchLiveBinData, 5000);
  fetchLiveBinData();
});
</script>
<?php 
$extra_js = ob_get_clean();
require_once __DIR__ . '/../layouts/footer.php'; 
?>