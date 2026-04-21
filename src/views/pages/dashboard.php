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
// BIN DATA (kept intact)
// ---------------------------------------------------------
$bin_types = [];
$where     = "";
$params    = [];
$param_types = "";

if (isset($_GET['kiosk']) && $_GET['kiosk'] !== "") {
    $where = "WHERE machine_id = ?";
    $params[] = $_GET['kiosk'];
    $param_types .= "s";
}

$sql  = "SELECT DISTINCT bin_type FROM trash_bins $where";
$stmt = $conn->prepare($sql);
if ($where) { $stmt->bind_param($param_types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) { $bin_types[] = $row['bin_type']; }
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
if ($result) { while ($row = $result->fetch_assoc()) { $kiosks[] = $row; } }

// Modal data (kept intact)
$users = [];
$userResult = $conn->query("SELECT user_id, full_name FROM users ORDER BY full_name ASC");
if ($userResult && $userResult->num_rows > 0) {
    while ($row = $userResult->fetch_assoc()) { $users[] = $row; }
}
$machines = [];
$machineResult = $conn->query("SELECT machine_id, machine_name FROM machines ORDER BY machine_name ASC");
if ($machineResult && $machineResult->num_rows > 0) {
    while ($row = $machineResult->fetch_assoc()) { $machines[] = $row; }
}
$bins = [];
$binResult = $conn->query("SELECT bin_id, machine_id, bin_type FROM trash_bins ORDER BY bin_type ASC");
if ($binResult && $binResult->num_rows > 0) {
    while ($row = $binResult->fetch_assoc()) { $bins[] = $row; }
}

// ---------------------------------------------------------
// QUICK COUNTS for the two panel headers
// ---------------------------------------------------------
$scheduleCount = 0;
$r = $conn->query("SELECT COUNT(*) as c FROM schedules"); 
if ($r) $scheduleCount = $r->fetch_assoc()['c'];

$taskPending = 0;
$taskProgress = 0;
$taskDone = 0;
$r = $conn->query("
    SELECT task_status FROM tasks
    UNION ALL
    SELECT 'scheduled' AS task_status FROM schedules
");
if ($r) {
    while ($tr = $r->fetch_assoc()) {
        $s = strtolower(trim($tr['task_status'] ?? ''));
        if (str_contains($s, 'pending'))                              $taskPending++;
        elseif (str_contains($s, 'progress'))                         $taskProgress++;
        elseif (str_contains($s, 'done') || str_contains($s, 'complet')) $taskDone++;
    }
}

// ---------------------------------------------------------
// HELPER: staff initials
// ---------------------------------------------------------
function getInitials($name) {
    $parts = explode(' ', trim($name ?? ''));
    $initials = '';
    foreach ($parts as $p) $initials .= strtoupper(substr($p, 0, 1));
    return substr($initials, 0, 2);
}

// ---------------------------------------------------------
// PAGE SETUP
// ---------------------------------------------------------
$page_title  = "Dashboard - Admin";
$current_page = "dashboard";
$extra_css   = '
<link rel="stylesheet" href="/assets/css/pages/dashboard.css" />
<style>
  /* Status Badge UI consistency for Scheduled items */
  .status-badge.scheduled {
      background-color: #e0e7ff;
      color: #3730a3;
  }
  .status-badge.scheduled .badge-dot {
      background-color: #4f46e5;
  }
</style>
';
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
        $color  = "#6a5acd";
        if ($status < 50)      $color = "#1abc3a";
        elseif ($status < 80)  $color = "#f39c12";
        else                   $color = "#e74c3c";
      ?>
      <div class="bin-card" data-bin="<?= htmlspecialchars($type) ?>">
        <h4><?= htmlspecialchars($type) ?></h4>
        <div class="progress-circle" style="--value:<?= $status ?>%;--color:<?= $color ?>;">
          <span><?= $status ?>%</span>
        </div>
        <p class="bin-date" style="font-size:13px;color:#666;">Last Updated: <?= htmlspecialchars($bin_data[$type]['last_updated'] ?? 'N/A') ?></p>
        <p class="bin-floor" style="font-size:14px;margin-top:6px;">Floor: <strong><?= htmlspecialchars($bin_data[$type]['floor_level'] ?? 'N/A') ?></strong></p>
        <p class="bin-machine" style="font-size:14px;">Kiosk: <strong><?= htmlspecialchars($bin_data[$type]['machine_name'] ?? 'N/A') ?></strong></p>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="dash-panels">

    <div class="dash-panel">
      <div class="panel-header">
        <div class="panel-title-group">
          <span class="panel-icon panel-icon-sched"><i class='bx bx-calendar-check'></i></span>
          <div>
            <h3 class="panel-title">Recent Schedules</h3>
            <span class="panel-subtitle"><?= $scheduleCount ?> routine template<?= $scheduleCount !== 1 ? 's' : '' ?> total</span>
          </div>
        </div>
        <button class="empty-btn" id="openScheduleModalBtn">
          <i class="bx bx-plus"></i> Add Schedule
        </button>
      </div>

      <div class="panel-filters">
        <input type="text" id="searchSched" class="filter-input" placeholder="Search..." onkeyup="runSchedFilter()">
        <input type="date" id="dateStartSched" class="filter-input" onchange="runSchedFilter()">
        <span class="filter-sep">–</span>
        <input type="date" id="dateEndSched" class="filter-input" onchange="runSchedFilter()">
      </div>

      <div class="panel-table-wrap">
        <table id="dashSchedTable" class="panel-table">
          <thead>
            <tr>
              <th class="sortable" onclick="sortTable('dashSchedTable',0)">Staff <i class='bx bx-sort sort-icon'></i></th>
              <th class="sortable" onclick="sortTable('dashSchedTable',1)">Floor <i class='bx bx-sort sort-icon'></i></th>
              <th class="sortable" onclick="sortTable('dashSchedTable',2)">Task <i class='bx bx-sort sort-icon'></i></th>
              <th class="sortable" onclick="sortTable('dashSchedTable',3,true)">Date <i class='bx bx-sort sort-icon'></i></th>
            </tr>
          </thead>
          <tbody>
            <?php
            $sql = "SELECT users.full_name, schedules.floor_level, schedules.task_description, schedules.schedule_date
                    FROM schedules JOIN users ON schedules.user_id = users.user_id
                    ORDER BY schedules.schedule_date ASC LIMIT 8";
            $res = $conn->query($sql);
            if ($res && $res->num_rows > 0):
                while ($row = $res->fetch_assoc()):
                    $ts = strtotime($row['schedule_date']);
                    $initials = getInitials($row['full_name']);
            ?>
              <tr>
                <td>
                  <div class="staff-cell">
                    <div class="mini-avatar"><?= $initials ?></div>
                    <span><?= htmlspecialchars($row['full_name'] ?? 'Unassigned') ?></span>
                  </div>
                </td>
                <td><?= htmlspecialchars($row['floor_level']) ?></td>
                <td class="td-task"><?= htmlspecialchars($row['task_description']) ?></td>
                <td data-time="<?= $ts ?>"><?= date('M d, Y', $ts) ?></td>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="4" class="empty-row"><i class='bx bx-calendar-x'></i><p>No schedules yet</p></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <a href="/schedules.php" class="view-all-btn">
        View all schedules <i class='bx bx-right-arrow-alt'></i>
      </a>
    </div>

    <div class="dash-panel">
      <div class="panel-header">
        <div class="panel-title-group">
          <span class="panel-icon panel-icon-task"><i class='bx bx-task'></i></span>
          <div>
            <h3 class="panel-title">Recent Tasks</h3>
            <div class="panel-task-counts">
              <span class="tc-badge tc-pending"><?= $taskPending ?> pending</span>
              <span class="tc-badge tc-progress"><?= $taskProgress ?> in progress</span>
              <span class="tc-badge tc-done"><?= $taskDone ?> done</span>
            </div>
          </div>
        </div>
        <button class="empty-btn" id="openTaskModalBtn">
          <i class="bx bx-plus"></i> Add Task
        </button>
      </div>

      <div class="panel-filters">
        <input type="text" id="searchTask" class="filter-input" placeholder="Search..." onkeyup="runTaskFilter()">
        <select id="statusTask" class="filter-select" onchange="runTaskFilter()">
          <option value="all">All</option>
          <option value="scheduled">Scheduled</option>
          <option value="pending">Pending</option>
          <option value="in progress">In Progress</option>
          <option value="done">Done</option>
        </select>
      </div>

      <div class="panel-table-wrap">
        <table id="dashTaskTable" class="panel-table">
          <thead>
            <tr>
              <th class="sortable" onclick="sortTable('dashTaskTable',0)">Staff <i class='bx bx-sort sort-icon'></i></th>
              <th class="sortable" onclick="sortTable('dashTaskTable',1)">Kiosk <i class='bx bx-sort sort-icon'></i></th>
              <th class="sortable" onclick="sortTable('dashTaskTable',2)">Status <i class='bx bx-sort sort-icon'></i></th>
              <th class="sortable" onclick="sortTable('dashTaskTable',3,true)">Created <i class='bx bx-sort sort-icon'></i></th>
            </tr>
          </thead>
          <tbody>
            <?php
            $sql = "
                SELECT * FROM (
                    SELECT 
                        users.full_name, 
                        tasks.task_description, 
                        tasks.task_status, 
                        tasks.created_at,
                        machines.machine_name, 
                        trash_bins.bin_type
                    FROM tasks
                    JOIN users ON tasks.user_id = users.user_id
                    LEFT JOIN machines ON tasks.machine_id = machines.machine_id
                    LEFT JOIN trash_bins ON tasks.bin_id = trash_bins.bin_id
                    
                    UNION ALL
                    
                    SELECT 
                        users.full_name, 
                        CONCAT(schedules.task_description, ' (Routine)') AS task_description, 
                        'scheduled' AS task_status, 
                        schedules.created_at,
                        machines.machine_name, 
                        trash_bins.bin_type
                    FROM schedules
                    JOIN users ON schedules.user_id = users.user_id
                    LEFT JOIN machines ON schedules.machine_id = machines.machine_id
                    LEFT JOIN trash_bins ON schedules.bin_id = trash_bins.bin_id
                ) AS combined_tables
                ORDER BY created_at DESC LIMIT 8
            ";
            
            $res = $conn->query($sql);
            if ($res && $res->num_rows > 0):
                while ($row = $res->fetch_assoc()):
                    $ts       = strtotime($row['created_at']);
                    $initials = getInitials($row['full_name']);
                    
                    // Same UI logic used in tasks.php
                    $raw      = strtolower(trim($row['task_status'] ?? ''));
                    $cls = 'pending'; $label = 'Pending';
                    
                    if ($raw === 'scheduled') {
                        $cls = 'scheduled';
                        $label = 'Scheduled';
                    } elseif (str_contains($raw, 'pending')) {
                        $cls = 'pending';
                        $label = 'Pending';
                    } elseif (str_contains($raw, 'progress')) { 
                        $cls = 'in-progress'; 
                        $label = 'In Progress'; 
                    } elseif (str_contains($raw, 'done') || str_contains($raw, 'complet')) { 
                        $cls = 'done'; 
                        $label = 'Completed'; 
                    }
            ?>
              <tr>
                <td>
                  <div class="staff-cell">
                    <div class="mini-avatar"><?= $initials ?></div>
                    <span><?= htmlspecialchars($row['full_name'] ?? 'Unassigned') ?></span>
                  </div>
                </td>
                <td>
                  <div class="kiosk-name"><?= htmlspecialchars($row['machine_name'] ?? '—') ?></div>
                  <div class="bin-sub"><?= htmlspecialchars($row['bin_type'] ?? '—') ?></div>
                </td>
                <td>
                  <span class="status-badge <?= $cls ?>">
                    <span class="badge-dot"></span><?= $label ?>
                  </span>
                </td>
                <td data-time="<?= $ts ?>"><?= date('M d, Y', $ts) ?></td>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="4" class="empty-row"><i class='bx bx-task'></i><p>No tasks yet</p></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <a href="/tasks.php" class="view-all-btn">
        View all tasks <i class='bx bx-right-arrow-alt'></i>
      </a>
    </div>

  </div></main>

<?php include __DIR__ . '/../components/add_schedule_modal.php'; ?>
<?php include __DIR__ . '/../components/add_task_modal.php'; ?>

<?php ob_start(); ?>
<script src="/assets/js/table-utils.js"></script>
<script>
function runSchedFilter() {
    filterGenericTable({
        tableId: 'dashSchedTable', searchId: 'searchSched',
        startId: 'dateStartSched', endId: 'dateEndSched', timeCol: 3
    });
}
function runTaskFilter() {
    filterGenericTable({
        tableId: 'dashTaskTable', searchId: 'searchTask',
        statusId: 'statusTask', statusCol: 2,
        startId: null, endId: null
    });
}

// Live bin data polling (kept intact)
document.addEventListener('DOMContentLoaded', function () {
    async function fetchLiveBinData() {
        try {
            const response = await fetch("/controllers/Api/fetch_bin_status.php");
            const bins = await response.json();
            bins.forEach(bin => {
                const { bin_type, bin_status, last_updated } = bin;
                const percent = parseInt(bin_status);
                let color = "#6a5acd";
                if (percent < 50)      color = "#1abc3a";
                else if (percent < 80) color = "#f39c12";
                else                   color = "#e74c3c";

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
<?php include __DIR__ . '/../components/modal_scripts.php'; ?>
<?php
$extra_js = ob_get_clean();
require_once __DIR__ . '/../layouts/footer.php';
?>