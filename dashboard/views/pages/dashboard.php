<?php
// views/pages/dashboard.php

if (!isset($_SESSION['username'])) {
    header("Location: /login.php");
    exit;
}

require_once __DIR__ . '/../../config/config.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ---------------------------------------------------------
// DATA FETCHING LOGIC (Kept exactly as you had it)
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

// ---------------------------------------------------------
// PAGE SETUP & LAYOUT INCLUSION
// ---------------------------------------------------------
$page_title = "Dashboard - Admin";
$current_page = "dashboard"; 
$extra_css = '<link rel="stylesheet" href="/views/pages/css/dashboard.css" />';

// Pull in the top layout
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

  <div class="main">
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
          <p class="bin-date">Last Updated: <?php echo htmlspecialchars($bin_data[$type]['last_updated'] ?? 'N/A'); ?></p>
          <p class="bin-floor">Floor Level: <?php echo htmlspecialchars($bin_data[$type]['floor_level'] ?? 'N/A'); ?></p>
          <p class="bin-machine">Machine: <?php echo htmlspecialchars($bin_data[$type]['machine_name'] ?? 'N/A'); ?></p>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="table-data">
      <div class="order">
        <div class="head">
          <h3>Schedule</h3>
          <i class="bx bx-search"></i>
          <i class="bx bx-chevron-down" style="color: black;" id="scheduleDropdownBtn"></i>
          <div id="scheduleDropdown" class="schedule-dropdown">
            <button class="schedule-filter-btn" data-filter="tomorrow">Schedule for Tomorrow</button>
            <button class="schedule-filter-btn" data-filter="week">Schedule for Week</button>
            <button class="schedule-filter-btn" data-filter="month">Schedule for Month</button>
          </div>
        </div>
        <table>
          <thead>
            <tr>
              <th>Name</th><th>Floor Level</th><th>Task Description</th><th>Schedule Date</th><th>Created At</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $where = "";
            if (isset($_GET['schedule_filter'])) {
                if ($_GET['schedule_filter'] === 'tomorrow') {
                    $tomorrow = date('Y-m-d', strtotime('+1 day'));
                    $where = "WHERE schedules.schedule_date = '$tomorrow'";
                } elseif ($_GET['schedule_filter'] === 'week') {
                    $monday = date('Y-m-d', strtotime('monday this week'));
                    $sunday = date('Y-m-d', strtotime('sunday this week'));
                    $where = "WHERE schedules.schedule_date BETWEEN '$monday' AND '$sunday'";
                } elseif ($_GET['schedule_filter'] === 'month') {
                    $first = date('Y-m-01');
                    $last = date('Y-m-t');
                    $where = "WHERE schedules.schedule_date BETWEEN '$first' AND '$last'";
                }
            }
            $sql = "SELECT users.full_name, schedules.floor_level, schedules.task_description, schedules.schedule_date, schedules.created_at FROM schedules JOIN users ON schedules.user_id = users.user_id $where ORDER BY schedules.created_at DESC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo '<tr><td>'.htmlspecialchars($row["full_name"]).'</td><td>'.htmlspecialchars($row["floor_level"]).'</td><td>'.htmlspecialchars($row["task_description"]).'</td><td>'.htmlspecialchars($row["schedule_date"]).'</td><td>'.htmlspecialchars($row["created_at"]).'</td></tr>';
                }
            } else {
                echo '<tr><td colspan="5">No schedule found</td></tr>';
            }
            ?>
          </tbody>
        </table>
      </div>

      <div class="todo">
        <div class="head">
          <h3>Add Schedule</h3>
          <i class="bx bx-plus" id="addScheduleBtn" style="cursor:pointer;"></i>
          <i class="bx bx-chevron-down" style="color: white;"></i>
        </div>
        <form id="addScheduleForm" method="post" action="/controllers/Actions/add_schedule.php" style="display:none; margin-top:16px; background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.07); padding:24px; max-width:400px;">
          <div style="display:flex; flex-direction:column; gap:16px;">
            <select name="user_id" required style="padding:10px 14px; border:1px solid #ddd; border-radius:8px; font-size:16px;">
              <option value="">Select User</option>
              <?php
              $result = $conn->query("SELECT user_id, full_name FROM users ORDER BY full_name ASC");
              if ($result && $result->num_rows > 0) {
                  while($row = $result->fetch_assoc()) {
                      echo '<option value="' . htmlspecialchars($row["user_id"]) . '">' . htmlspecialchars($row["full_name"]) . '</option>';
                  }
              }
              ?>
            </select>
            <input type="text" name="floor_level" placeholder="Floor Level" required style="padding:10px 14px; border:1px solid #ddd; border-radius:8px; font-size:16px;">
            <input type="text" name="task_description" placeholder="Task Description" required style="padding:10px 14px; border:1px solid #ddd; border-radius:8px; font-size:16px;">
            <input type="date" name="schedule_date" required style="padding:10px 14px; border:1px solid #ddd; border-radius:8px; font-size:16px;">
            <button type="submit" style="background:green; color:#fff; border:none; border-radius:8px; padding:12px; font-size:16px; cursor:pointer;">Add Schedule</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>

<?php 
// ---------------------------------------------------------
// PAGE SPECIFIC SCRIPTS (Injected into footer)
// ---------------------------------------------------------
ob_start(); 
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Toggle Add Schedule form
  const addScheduleBtn = document.getElementById('addScheduleBtn');
  const addScheduleForm = document.getElementById('addScheduleForm');
  if (addScheduleBtn && addScheduleForm) {
    addScheduleBtn.onclick = () => { addScheduleForm.style.display = (addScheduleForm.style.display === 'none' || addScheduleForm.style.display === '') ? 'block' : 'none'; };
  }

  // Dropdown UI Logic
  const scheduleDropdownBtn = document.getElementById('scheduleDropdownBtn');
  const scheduleDropdown = document.getElementById('scheduleDropdown');
  if (scheduleDropdownBtn && scheduleDropdown) {
    scheduleDropdownBtn.onclick = (e) => { e.stopPropagation(); scheduleDropdown.style.display = (scheduleDropdown.style.display === 'block') ? 'none' : 'block'; };
    document.addEventListener('click', (event) => {
      if (!scheduleDropdown.contains(event.target) && !scheduleDropdownBtn.contains(event.target)) scheduleDropdown.style.display = 'none';
    });
  }

  // Live Bin Data Update
  async function fetchLiveBinData() {
    try {
      const response = await fetch("/controllers/Api/fetch_bin_status.php"); // Updated path
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

// Pull in the bottom layout
require_once __DIR__ . '/../layouts/footer.php'; 
?>