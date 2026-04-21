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
// PAGINATION & FILTER LOGIC (kept intact for backend compatibility)
// ---------------------------------------------------------
$limit = 10;
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

$countSql = "SELECT COUNT(*) as total FROM schedules $whereClause";
$countResult = $conn->query($countSql);
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

// ---------------------------------------------------------
// NEW: Fetch schedules grouped by staff member
// ---------------------------------------------------------
$staffRoutines = [];

$sql = "
    SELECT 
        s.schedule_id,
        s.user_id,
        u.full_name,
        s.floor_level,
        s.task_description,
        s.schedule_date,
        s.recurrence_pattern,
        s.day_of_week,
        s.schedule_time,
        s.created_at,
        m.machine_name,
        tb.bin_type
    FROM schedules s
    JOIN users u ON s.user_id = u.user_id
    LEFT JOIN machines m ON s.machine_id = m.machine_id
    LEFT JOIN trash_bins tb ON s.bin_id = tb.bin_id
    ORDER BY u.full_name ASC, s.schedule_time ASC
";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $uid = $row['user_id'];
        if (!isset($staffRoutines[$uid])) {
            $staffRoutines[$uid] = [
                'user_id'   => $uid,
                'full_name' => $row['full_name'],
                'routines'  => []
            ];
        }
        $staffRoutines[$uid]['routines'][] = $row;
    }
}

// Fetch Users for Modal dropdown (kept for add_schedule_modal.php)
$users = [];
$userResult = $conn->query("SELECT user_id, full_name FROM users ORDER BY full_name ASC");
if ($userResult && $userResult->num_rows > 0) {
    while ($row = $userResult->fetch_assoc()) {
        $users[] = $row;
    }
}

// Fetch Machines for Modal dropdown (kept for add_schedule_modal.php)
$machines = [];
$machineResult = $conn->query("SELECT machine_id, machine_name FROM machines ORDER BY machine_name ASC");
if ($machineResult && $machineResult->num_rows > 0) {
    while ($row = $machineResult->fetch_assoc()) {
        $machines[] = $row;
    }
}

// Fetch Bins to feed to Javascript (kept for modal_scripts.php)
$bins = [];
$binResult = $conn->query("SELECT bin_id, machine_id, bin_type FROM trash_bins ORDER BY bin_type ASC");
if ($binResult && $binResult->num_rows > 0) {
    while ($row = $binResult->fetch_assoc()) {
        $bins[] = $row;
    }
}

// ---------------------------------------------------------
// PAGE SETUP
// ---------------------------------------------------------
$page_title = "Routine Manager - Admin";
$current_page = "schedules";
$extra_css = '<link rel="stylesheet" href="/assets/css/pages/schedules.css" />';
require_once __DIR__ . '/../layouts/header.php';
?>

<main id="main-content">
  <div class="head-title">
    <div class="left">
      <h1>Schedule Manager</h1>
      <ul class="breadcrumb">
        <li><a href="/dashboard.php" style="color: #888; text-decoration: none; pointer-events: auto;">Dashboard</a></li>
        <li><i class="bx bx-chevron-right"></i></li>
        <li><a class="active" href="/schedules.php">Schedules</a></li>
      </ul>
    </div>
  </div>

  <?php if (isset($_GET['success'])): ?>
    <div class="flash-msg flash-success"><i class='bx bx-check-circle'></i> <?= htmlspecialchars($_GET['success']) ?></div>
  <?php endif; ?>
  <?php if (isset($_GET['error'])): ?>
    <div class="flash-msg flash-error"><i class='bx bx-error-circle'></i> <?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <!-- Info Banner -->
  <div class="routine-banner">
    <div class="routine-banner-icon"><i class='bx bx-info-circle'></i></div>
    <div class="routine-banner-text">
      <strong>Automated Routine Templates</strong>
      <span>These are recurring schedules, not one-time tasks. The system automatically generates active tasks for assigned staff on their scheduled days. Editing here updates the template — not past tasks.</span>
    </div>
  </div>

  <!-- Top Controls -->
  <div class="rm-controls">
    <input type="text" id="searchStaff" class="filter-input search-bar" placeholder="Search staff member..." oninput="filterStaffCards()">
    <div class="filter-group">
      <label style="font-size:13px; color:#777;">Pattern:</label>
      <select id="patternFilter" class="filter-select" onchange="filterStaffCards()">
        <option value="all">All Patterns</option>
        <option value="daily">Daily</option>
        <option value="weekly">Weekly</option>
      </select>
    </div>
    <button class="btn-primary" id="openScheduleModalBtn">
      <i class="bx bx-plus"></i> Add Schedule
    </button>
  </div>

  <!-- Staff Cards Grid -->
  <div class="staff-grid" id="staffGrid">
    <?php if (empty($staffRoutines)): ?>
      <div class="empty-state">
        <i class='bx bx-calendar-x'></i>
        <p>No routine templates yet.</p>
        <button class="btn-primary" id="openScheduleModalBtnEmpty" onclick="document.getElementById('openScheduleModalBtn').click()">
          <i class="bx bx-plus"></i> Add First Routine
        </button>
      </div>
    <?php else: ?>
      <?php foreach ($staffRoutines as $staff): 
        $initials = '';
        $nameParts = explode(' ', $staff['full_name']);
        foreach ($nameParts as $part) $initials .= strtoupper(substr($part, 0, 1));
        $initials = substr($initials, 0, 2);
        $routineCount = count($staff['routines']);
        // Encode routines as JSON for the modal
        $routinesJson = htmlspecialchars(json_encode($staff['routines']), ENT_QUOTES, 'UTF-8');
      ?>
        <div class="staff-card" 
             data-name="<?= htmlspecialchars(strtolower($staff['full_name'])) ?>"
             data-patterns="<?= htmlspecialchars(strtolower(implode(',', array_column($staff['routines'], 'recurrence_pattern')))) ?>"
             onclick="openStaffModal(<?= htmlspecialchars(json_encode($staff)) ?>)">
          <div class="staff-card-header">
            <div class="staff-avatar"><?= $initials ?></div>
            <div class="staff-info">
              <div class="staff-name"><?= htmlspecialchars($staff['full_name']) ?></div>
              <div class="staff-meta"><?= $routineCount ?> routine<?= $routineCount !== 1 ? 's' : '' ?> assigned</div>
            </div>
          </div>
          <div class="staff-routine-preview">
            <?php foreach (array_slice($staff['routines'], 0, 3) as $r): 
              $isDaily = strtolower($r['recurrence_pattern']) === 'daily';
              $dayLabel = !empty($r['day_of_week']) ? trim($r['day_of_week']) : 'Not set';
              $recurrenceLabel = $isDaily ? 'Every day' : ('Weekly · ' . $dayLabel);
              $timeLabel = !empty($r['schedule_time']) ? date('g:i A', strtotime($r['schedule_time'])) : '';
            ?>
              <div class="routine-pill">
                <i class='bx <?= $isDaily ? 'bx-refresh' : 'bx-repeat' ?>'></i>
                <span><?= $recurrenceLabel ?></span>
                <?php if ($timeLabel): ?><span class="pill-time"><?= $timeLabel ?></span><?php endif; ?>
              </div>
            <?php endforeach; ?>
            <?php if ($routineCount > 3): ?>
              <div class="routine-pill routine-pill-more">+<?= $routineCount - 3 ?> more</div>
            <?php endif; ?>
          </div>
          <div class="staff-card-footer">
            <span class="view-detail-hint"><i class='bx bx-expand-alt'></i> Click to view all routines</span>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</main>

<!-- Staff Routines Detail Modal -->
<div id="staffDetailModal" class="rm-modal-overlay" onclick="closeStaffModal(event)">
  <div class="rm-modal-box" onclick="event.stopPropagation()">
    <div class="rm-modal-header">
      <div class="rm-modal-avatar" id="modalAvatar"></div>
      <div>
        <div class="rm-modal-name" id="modalName"></div>
        <div class="rm-modal-subtitle" id="modalSubtitle"></div>
      </div>
      <button class="rm-modal-close" onclick="closeStaffModal()">&times;</button>
    </div>

    <!-- Weekly Pattern Grid -->
    <div class="week-grid-section">
      <div class="week-grid-label">Weekly overview</div>
      <div class="week-grid" id="weekGrid"></div>
    </div>

    <!-- Routine Cards List -->
    <div class="rm-routine-list" id="routineList"></div>
  </div>
</div>

<?php include __DIR__ . '/../components/add_schedule_modal.php'; ?>

<?php ob_start(); ?>
<script src="/assets/js/table-utils.js"></script>
<script>
const DAYS = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

// --- Staff card filter ---
function filterStaffCards() {
  const search  = document.getElementById('searchStaff').value.toLowerCase();
  const pattern = document.getElementById('patternFilter').value;
  document.querySelectorAll('.staff-card').forEach(card => {
    const nameMatch    = card.dataset.name.includes(search);
    const patternMatch = pattern === 'all' || card.dataset.patterns.includes(pattern);
    card.style.display = (nameMatch && patternMatch) ? '' : 'none';
  });
}

// --- Open staff detail modal ---
function openStaffModal(staff) {
  const modal    = document.getElementById('staffDetailModal');
  const routines = staff.routines;

  // Header
  const initials = staff.full_name.split(' ').map(p => p[0].toUpperCase()).join('').slice(0, 2);
  document.getElementById('modalAvatar').textContent   = initials;
  document.getElementById('modalName').textContent     = staff.full_name;
  document.getElementById('modalSubtitle').textContent = routines.length + ' routine template' + (routines.length !== 1 ? 's' : '');

  // Weekly grid
  const weekGrid = document.getElementById('weekGrid');
  weekGrid.innerHTML = '';
  DAYS.forEach(day => {
    const dayRoutines = routines.filter(r =>
      r.recurrence_pattern === 'daily' ||
      (r.day_of_week && r.day_of_week.toLowerCase() === day.toLowerCase())
    );
    const col = document.createElement('div');
    col.className = 'week-col' + (dayRoutines.length ? ' week-col-active' : '');
    col.innerHTML = `<div class="week-day-label">${day.slice(0,3)}</div>` +
      (dayRoutines.length
        ? dayRoutines.map(r => `<div class="week-task-chip">${escHtml(r.task_description)}</div>`).join('')
        : `<div class="week-empty-chip">—</div>`);
    weekGrid.appendChild(col);
  });

  // Routine list
  const list = document.getElementById('routineList');
  list.innerHTML = '';
  routines.forEach(r => {
    const isDaily   = r.recurrence_pattern === 'daily';
    const timeStr   = r.schedule_time ? formatTime(r.schedule_time) : '';
    const recStr    = isDaily ? 'Every day' : ('Weekly · ' + (r.day_of_week || 'Not set'));
    const icon      = isDaily ? 'bx-refresh' : 'bx-repeat';
    const machineLbl = r.machine_name ? escHtml(r.machine_name) : '—';
    const binLbl     = r.bin_type    ? escHtml(r.bin_type)     : '—';
    const floorLbl   = r.floor_level ? escHtml(r.floor_level) + ' Floor' : '—';

    list.innerHTML += `
      <div class="rm-routine-card">
        <div class="rrc-top">
          <span class="rrc-recurrence"><i class='bx ${icon}'></i> ${escHtml(recStr)}</span>
          ${timeStr ? `<span class="rrc-time"><i class='bx bx-time-five'></i> ${timeStr}</span>` : ''}
        </div>
        <div class="rrc-task">${escHtml(r.task_description)}</div>
        <div class="rrc-meta">
          <span><i class='bx bx-building-house'></i> ${machineLbl}</span>
          <span><i class='bx bxs-trash-alt'></i> ${binLbl}</span>
          <span><i class='bx bx-layer'></i> ${floorLbl}</span>
        </div>
      </div>`;
  });

  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function closeStaffModal(e) {
  if (e && e.target !== document.getElementById('staffDetailModal')) return;
  document.getElementById('staffDetailModal').style.display = 'none';
  document.body.style.overflow = '';
}

function escHtml(str) {
  if (!str) return '';
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function formatTime(t) {
  if (!t) return '';
  const [h, m] = t.split(':');
  const hour = parseInt(h);
  const ampm = hour >= 12 ? 'PM' : 'AM';
  return ((hour % 12) || 12) + ':' + m + ' ' + ampm;
}

// Close modal on Escape
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.getElementById('staffDetailModal').style.display = 'none';
    document.body.style.overflow = '';
  }
});
</script>
<?php include __DIR__ . '/../components/modal_scripts.php'; ?>
<?php
$extra_js = ob_get_clean();
require_once __DIR__ . '/../layouts/footer.php';
?>