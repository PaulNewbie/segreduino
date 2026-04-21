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
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$filter = $_GET['status'] ?? 'all';
$whereClause = "";

$cleanFilter = strtolower(trim($filter));

if ($cleanFilter === 'pending') {
    $whereClause = "WHERE LOWER(task_status) LIKE '%pending%'";
} elseif ($cleanFilter === 'in progress' || $cleanFilter === 'in_progress') {
    $whereClause = "WHERE LOWER(task_status) LIKE '%progress%'";
} elseif ($cleanFilter === 'done' || $cleanFilter === 'completed') {
    $whereClause = "WHERE LOWER(task_status) LIKE '%done%' OR LOWER(task_status) LIKE '%complete%'";
}

// ---------------------------------------------------------
// STATUS COUNTS for the summary bar
// ---------------------------------------------------------
$countAll = 0;
$countPending = 0;
$countInProgress = 0;
$countDone = 0;

// Combine actual tasks and schedules for accurate counting
$cRes = $conn->query("
    SELECT task_status FROM tasks 
    UNION ALL 
    SELECT 'scheduled' AS task_status FROM schedules
");
if ($cRes) {
    while ($cRow = $cRes->fetch_assoc()) {
        $s = strtolower(trim($cRow['task_status'] ?? ''));
        $countAll++;
        if (str_contains($s, 'pending'))                          $countPending++;
        elseif (str_contains($s, 'progress'))                     $countInProgress++;
        elseif (str_contains($s, 'done') || str_contains($s, 'complet')) $countDone++;
    }
}
$pendingPct    = $countAll > 0 ? round(($countPending    / $countAll) * 100) : 0;
$progressPct   = $countAll > 0 ? round(($countInProgress / $countAll) * 100) : 0;
$donePct       = $countAll > 0 ? round(($countDone       / $countAll) * 100) : 0;

// ---------------------------------------------------------
// COUNT + FETCH for current filter/page
// ---------------------------------------------------------
$countSql = "
    SELECT COUNT(*) as total FROM (
        SELECT task_status FROM tasks
        UNION ALL
        SELECT 'scheduled' AS task_status FROM schedules
    ) AS combined_tables $whereClause
";
$countResult = $conn->query($countSql);
$totalRows = $countResult->fetch_assoc()['total'] ?? 0;
$totalPages = ceil($totalRows / $limit);

// THE SOLUTION: Schedules are marked as 'scheduled'
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
    $whereClause
    ORDER BY created_at DESC
    LIMIT $limit OFFSET $offset
";
$result = $conn->query($sql);

// ---------------------------------------------------------
// HELPER: relative date label
// ---------------------------------------------------------
function relativeDate($timestamp) {
    $diff = floor((time() - $timestamp) / 86400);
    if ($diff === 0)  return 'Today';
    if ($diff === 1)  return 'Yesterday';
    if ($diff < 7)   return $diff . ' days ago';
    if ($diff < 30)  return floor($diff / 7) . ' week' . (floor($diff / 7) > 1 ? 's' : '') . ' ago';
    return date('M j', $timestamp);
}

// ---------------------------------------------------------
// HELPER: staff initials (max 2 chars)
// ---------------------------------------------------------
function getInitials($name) {
    $parts = explode(' ', trim($name ?? ''));
    $initials = '';
    foreach ($parts as $p) $initials .= strtoupper(substr($p, 0, 1));
    return substr($initials, 0, 2);
}

// Fetch Users for Modal dropdown
$users = [];
$userResult = $conn->query("SELECT user_id, full_name FROM users ORDER BY full_name ASC");
if ($userResult && $userResult->num_rows > 0) {
    while ($row = $userResult->fetch_assoc()) { $users[] = $row; }
}

// Fetch Machines for Modal dropdown
$machines = [];
$machineResult = $conn->query("SELECT machine_id, machine_name FROM machines ORDER BY machine_name ASC");
if ($machineResult && $machineResult->num_rows > 0) {
    while ($row = $machineResult->fetch_assoc()) { $machines[] = $row; }
}

// Fetch Bins for JS modal
$bins = [];
$binResult = $conn->query("SELECT bin_id, machine_id, bin_type FROM trash_bins ORDER BY bin_type ASC");
if ($binResult && $binResult->num_rows > 0) {
    while ($row = $binResult->fetch_assoc()) { $bins[] = $row; }
}

// ---------------------------------------------------------
// PAGE SETUP
// ---------------------------------------------------------
$page_title = "Tasks - Admin";
$current_page = "tasks";
$extra_css = '
<link rel="stylesheet" href="/assets/css/pages/tasks.css" />
<style>
  /* Extra styling for the Scheduled badge so it looks different from Pending */
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
      <h1>Tasks Directory</h1>
      <ul class="breadcrumb">
        <li><a href="/dashboard.php" style="color:#888;text-decoration:none;pointer-events:auto;">Dashboard</a></li>
        <li><i class="bx bx-chevron-right"></i></li>
        <li><a class="active" href="/tasks.php">Tasks</a></li>
      </ul>
    </div>
  </div>

  <?php if (isset($_GET['success'])): ?>
    <div class="flash-msg flash-success"><i class='bx bx-check-circle'></i> <?= htmlspecialchars($_GET['success']) ?></div>
  <?php endif; ?>
  <?php if (isset($_GET['error'])): ?>
    <div class="flash-msg flash-error"><i class='bx bx-error-circle'></i> <?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <div class="summary-bar">

    <a href="/tasks.php?status=all" class="stat-card sf-all <?= ($cleanFilter === 'all' || $cleanFilter === '') ? 'active-filter' : '' ?>">
      <div class="stat-label"><i class='bx bx-list-ul'></i> Total Tasks</div>
      <div class="stat-num"><?= $countAll ?></div>
      <div class="stat-bar"><div class="stat-fill" style="width:100%"></div></div>
    </a>

    <a href="/tasks.php?status=pending" class="stat-card sf-pending <?= $cleanFilter === 'pending' ? 'active-filter' : '' ?>">
      <div class="stat-label"><i class='bx bx-time'></i> Pending</div>
      <div class="stat-num"><?= $countPending ?></div>
      <div class="stat-bar"><div class="stat-fill" style="width:<?= $pendingPct ?>%"></div></div>
    </a>

    <a href="/tasks.php?status=in_progress" class="stat-card sf-progress <?= in_array($cleanFilter, ['in progress','in_progress']) ? 'active-filter' : '' ?>">
      <div class="stat-label"><i class='bx bx-loader-alt'></i> In Progress</div>
      <div class="stat-num"><?= $countInProgress ?></div>
      <div class="stat-bar"><div class="stat-fill" style="width:<?= $progressPct ?>%"></div></div>
    </a>

    <a href="/tasks.php?status=done" class="stat-card sf-done <?= in_array($cleanFilter, ['done','completed']) ? 'active-filter' : '' ?>">
      <div class="stat-label"><i class='bx bx-check-circle'></i> Completed</div>
      <div class="stat-num"><?= $countDone ?></div>
      <div class="stat-bar"><div class="stat-fill" style="width:<?= $donePct ?>%"></div></div>
    </a>

  </div>

  <div class="card-custom">

    <div class="table-controls">
      <input type="text" id="searchTask" class="filter-input search-bar" placeholder="Search staff or task..." onkeyup="runTaskFilter()">

      <div class="filter-group">
        <label style="font-size:13px;color:#777;">Status:</label>
        <select id="statusTask" class="filter-select" onchange="runTaskFilter()">
          <option value="all">All Status</option>
          <option value="pending">Pending</option>
          <option value="in progress">In Progress</option>
          <option value="done">Done</option>
          <option value="completed">Completed</option>
        </select>
      </div>

      <div class="filter-group">
        <label style="font-size:13px;color:#777;">Created:</label>
        <input type="date" id="dateStartTask" class="filter-input" onchange="runTaskFilter()">
        <span style="color:#bbb;font-size:12px;">to</span>
        <input type="date" id="dateEndTask" class="filter-input" onchange="runTaskFilter()">
      </div>

      <button class="btn-primary" id="openTaskModalBtn">
        <i class="bx bx-plus"></i> Add Task
      </button>
    </div>

    <div class="table-scroll-wrapper">
      <table class="task-table" id="taskTable">
        <thead>
          <tr>
            <th class="sortable" onclick="sortTable('taskTable', 0)">Staff <i class='bx bx-sort sort-icon'></i></th>
            <th class="sortable" onclick="sortTable('taskTable', 1)">Kiosk / Bin <i class='bx bx-sort sort-icon'></i></th>
            <th class="sortable" onclick="sortTable('taskTable', 2)">Task Description <i class='bx bx-sort sort-icon'></i></th>
            <th class="sortable" onclick="sortTable('taskTable', 3)">Status <i class='bx bx-sort sort-icon'></i></th>
            <th class="sortable" onclick="sortTable('taskTable', 4, true)">Created <i class='bx bx-sort sort-icon'></i></th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()):
              $timestamp  = strtotime($row['created_at']);
              $initials   = getInitials($row['full_name']);
              $relDate    = relativeDate($timestamp);

              $rawStatus    = strtolower(trim($row['task_status'] ?? ''));
              $statusClass  = 'pending';
              $displayText  = 'Pending';
              
              if ($rawStatus === 'scheduled') {
                  $statusClass = 'scheduled';
                  $displayText = 'Scheduled';
              } elseif (str_contains($rawStatus, 'pending')) {
                  $statusClass = 'pending';
                  $displayText = 'Pending';
              } elseif (str_contains($rawStatus, 'progress')) {
                  $statusClass = 'in-progress';
                  $displayText = 'In Progress';
              } elseif (str_contains($rawStatus, 'done') || str_contains($rawStatus, 'complet')) {
                  $statusClass = 'done';
                  $displayText = 'Completed';
              }
            ?>
            <tr>
              <td>
                <div class="staff-cell">
                  <div class="mini-avatar"><?= $initials ?></div>
                  <span class="staff-name"><?= htmlspecialchars($row['full_name'] ?? 'Unassigned') ?></span>
                </div>
              </td>

              <td>
                <div class="kiosk-name"><?= htmlspecialchars($row['machine_name'] ?? 'Unknown Machine') ?></div>
                <div class="bin-sub"><?= htmlspecialchars($row['bin_type'] ?? 'Unknown Bin') ?></div>
              </td>

              <td class="task-desc"><?= htmlspecialchars($row['task_description']) ?></td>

              <td>
                <span class="status-badge <?= $statusClass ?>">
                  <span class="badge-dot"></span>
                  <?= htmlspecialchars($displayText) ?>
                </span>
              </td>

              <td data-time="<?= $timestamp ?>">
                <div class="date-main"><?= htmlspecialchars(date('M d, Y', $timestamp)) ?></div>
              </td>
            </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" class="empty-row">
                <i class='bx bx-task'></i>
                <p>No tasks found<?= $cleanFilter !== 'all' ? ' for this filter' : '' ?>.</p>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="pagination-bar">
      <span class="pager-info">
        Showing page <?= $page ?> of <?= $totalPages ?> &nbsp;(&nbsp;<?= $totalRows ?> total&nbsp;)
      </span>
      <div class="pagination">
        <?php if ($page > 1): ?>
          <a href="?status=<?= urlencode($filter) ?>&page=<?= $page - 1 ?>" class="page-btn"><i class='bx bx-chevron-left'></i></a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <a href="?status=<?= urlencode($filter) ?>&page=<?= $i ?>"
             class="page-btn <?= ($i === $page) ? 'active' : '' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
          <a href="?status=<?= urlencode($filter) ?>&page=<?= $page + 1 ?>" class="page-btn"><i class='bx bx-chevron-right'></i></a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>
</main>

<?php include __DIR__ . '/../components/add_task_modal.php'; ?>

<?php ob_start(); ?>
<script src="/assets/js/table-utils.js"></script>
<script>
function runTaskFilter() {
    filterGenericTable({
        tableId:   'taskTable',
        searchId:  'searchTask',
        statusId:  'statusTask',
        statusCol: 3,
        startId:   'dateStartTask',
        endId:     'dateEndTask',
        timeCol:   4
    });
}
</script>
<?php include __DIR__ . '/../components/modal_scripts.php'; ?>
<?php
$extra_js = ob_get_clean();
require_once __DIR__ . '/../layouts/footer.php';
?>