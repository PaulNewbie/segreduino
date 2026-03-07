<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// Fetch bin status and last emptied for each type
require_once __DIR__ . "/config.php";
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch unique bin types from trash_bins table, filtered by machine if selected
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
        $stmt = $conn->prepare("
            SELECT tb.bin_status, tb.last_updated, tb.floor_level, m.machine_name 
            FROM trash_bins tb
            JOIN machines m ON tb.machine_id = m.machine_id
            WHERE tb.bin_type = ? AND tb.machine_id = ?
            ORDER BY tb.last_updated DESC LIMIT 1
        ");
        $stmt->bind_param("ss", $type, $_GET['kiosk']);
    } else {
        $stmt = $conn->prepare("
            SELECT tb.bin_status, tb.last_updated, tb.floor_level, m.machine_name 
            FROM trash_bins tb
            JOIN machines m ON tb.machine_id = m.machine_id
            WHERE tb.bin_type = ?
            ORDER BY tb.last_updated DESC LIMIT 1
        ");
        $stmt->bind_param("s", $type);
    }

    $stmt->execute();
    $stmt->bind_result($status, $last_updated, $floor_level, $machine_name);
    if ($stmt->fetch()) {
        $bin_data[$type] = [
            'status' => $status,
            'last_updated' => $last_updated,
            'floor_level' => $floor_level,
            'machine_name' => $machine_name
        ];
    }
    $stmt->close();
}

// Use the same open connection to load kiosks instead of reconnecting
$kiosks = [];
$result = $conn->query("SELECT machine_id, machine_name FROM machines ORDER BY machine_name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $kiosks[] = $row;
    }
}

// close connection once everything is done
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />
  <title>Admin</title>
  <style>
    .bin-grid {
      display: flex;
      flex-direction: row;
      gap: 24px;
      justify-content: flex-start;
      margin-bottom: 32px;
    }
    .bin-card {
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.05);
      padding: 20px 24px;
      min-width: 220px;
      flex: 1 1 0;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .chart-container {
      width: 120px;
      height: 120px;
      margin-bottom: 12px;
    }
    @media (max-width: 900px) {
      .bin-grid {
        flex-direction: column;
        gap: 16px;
      }
      .bin-card {
        min-width: unset;
        width: 100%;
      }
    }
    
    .progress-circle {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  background: conic-gradient(var(--color) var(--value), #e6e6e6 0);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  font-weight: bold;
  color: var(--color);
  margin-bottom: 12px;
  transition: background 0.5s ease, color 0.3s ease;
  /* ensure inner span not affected by CSS variables when replaced via JS */
  position: relative;
}

    .table-data .order table {
      width: 100%;
      border-collapse: collapse;
    }
    .table-data .order th,
    .table-data .order td {
      padding: 12px 10px;
      text-align: left;
      vertical-align: middle;
      font-size: 16px;
    }
    .table-data .order th:nth-child(1),
    .table-data .order td:nth-child(1) {
      min-width: 140px;
      width: 18%;
    }
    .table-data .order th:nth-child(2),
    .table-data .order td:nth-child(2) {
      min-width: 100px;
      width: 12%;
    }
    .table-data .order th:nth-child(3),
    .table-data .order td:nth-child(3) {
      min-width: 180px;
      width: 28%;
    }
    .table-data .order th:nth-child(4),
    .table-data .order td:nth-child(4) {
      min-width: 120px;
      width: 18%;
    }
    .table-data .order th:nth-child(5),
    .table-data .order td:nth-child(5) {
      min-width: 140px;
      width: 24%;
    }
    .table-data .order th {
      background: var(--light-green);
      color: green;
      font-weight: 600;
    }
    .table-data .order tr {
      border-bottom: 1px solid #222;
    }
    .table-data .order tr:last-child td {
      border-bottom: none;
    }
    .schedule-dropdown {
      display: none;
      position: absolute;
      background: #232825;
      color: #fff;
      border-radius: 10px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.18);
      padding: 8px 0;
      z-index: 1000;
      min-width: 220px;
      right: 32px;
      top: 48px;
      font-family: inherit;
      border: 1px solid #222;
      animation: fadeIn 0.18s;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-8px);}
      to   { opacity: 1; transform: translateY(0);}
    }
    .schedule-filter-btn {
  display: block;
  width: 100%;
  padding: 8px 12px;
  background: white;
  border: none;
  text-align: left;
  cursor: pointer;
  transition: 0.2s;
}

.schedule-filter-btn:hover {
  background: #f0f0f0;
}

.schedule-filter-btn.active-filter {
  background: #4CAF50;   /* green highlight */
  color: white;
  font-weight: bold;
}

  </style>
</head>
<body>
  <!-- SIDEBAR -->
  <section id="sidebar">
    <a href="#" class="brand">
      <i class="bx bxs-chip"></i>
      <span class="text">SegreDuino</span>
      <span class="text">Admin</span>
    </a>
    <ul class="side-menu top">
      <li>
        <a href="index.php" id="dashboard-link">
          <i class="bx bxs-dashboard"></i>
          <span class="text">Dashboard</span>
        </a>
      </li>
      <li>
        <a href="user.php" id="users-link">
          <i class="bx bxs-user"></i>
          <span class="text">Users</span>
        </a>
      </li>
    <li>
        <a href="bin.php">
          <i class="bx bxs-shopping-bag-alt"></i>
          <span class="text">Bin Monitoring</span>
        </a>
      </li>
      <li>
        <a href="history.php" id="history-reports-link">
          <i class="bx bxs-message-dots"></i>
          <span class="text">History & Reports</span>
        </a>
      </li>
      <li>
        <a href="logout.php" class="logout">
          <i class="bx bxs-log-out-circle"></i>
          <span class="text">Logout</span>
        </a>
      </li>
    </ul>
  </section>
  <!-- SIDEBAR -->

  <!-- CONTENT -->
  <section id="content">
    <!-- NAVBAR -->
    <nav>
      <i class="bx bx-menu"></i>
      <a href="#" class="nav-link">Categories</a>
      <form action="#">
        <div class="form-input">
          <input type="search" placeholder="Search..." />
          <button type="submit" class="search-btn">
            <i class="bx bx-search"></i>
          </button>
        </div>
      </form>
      
      <a href="#" class="notification">
        <i class="bx bxs-bell"></i>
        <span class="num">0</span>
      </a>
      <a href="#" class="profile">
        <img src="../img/pdm logo.jfif" alt="profile" />
      </a>
    </nav>
    <!-- NAVBAR -->
    <!-- MAIN -->
    <main id="main-content">
      <div class="head-title">
        <div class="left">
          <h1>Dashboard Overview</h1>
          <ul class="breadcrumb">
            <li></li>
            <li><i class="bx bx-chevron-right"></i></li>
            <li><a class="active" href="index.php">Home</a></li>
          </ul>
        </div>
      </div>
      <div class="main">
       <div class="bin-grid" id="binGrid">
  <?php foreach ($bin_types as $type): ?>
    <?php 
      $status = (int)($bin_data[$type]['status'] ?? 0); 
      // default color
      $color = "#6a5acd"; 

      if ($status < 50) {
          $color = "#1abc3a"; // green
      } elseif ($status < 80) {
          $color = "#f39c12"; // orange
      } else {
          $color = "#e74c3c"; // red
      }
    ?>
    <div class="bin-card" data-bin="<?php echo htmlspecialchars($type); ?>">
      <h4><?php echo htmlspecialchars($type); ?></h4>
      <div class="progress-circle" 
           style="--value: <?= $status ?>%; --color: <?= $color ?>;">
        <span><?= $status ?>%</span>
      </div>
      <p class="bin-date">
        Last Updated: <?php echo htmlspecialchars($bin_data[$type]['last_updated'] ?? 'N/A'); ?>
      </p>
      <p class="bin-floor">
        Floor Level: <?php echo htmlspecialchars($bin_data[$type]['floor_level'] ?? 'N/A'); ?>
      </p>
      <p class="bin-machine">
        Machine: <?php echo htmlspecialchars($bin_data[$type]['machine_name'] ?? 'N/A'); ?>
      </p>
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
                <th>Name</th>
                <th>Floor Level</th>
                <th>Task Description</th>
                <th>Schedule Date</th>
                <th>Created At</th>
              </tr>
            </thead>
            <tbody>
<?php
require_once __DIR__ . "/config.php";
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$where = "";
if (isset($_GET['schedule_filter'])) {
    $today = date('Y-m-d');
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
$sql = "SELECT users.full_name, schedules.floor_level, schedules.task_description, schedules.schedule_date, schedules.created_at
        FROM schedules
        JOIN users ON schedules.user_id = users.user_id
        $where
        ORDER BY schedules.created_at DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row["full_name"]) . '</td>';
        echo '<td>' . htmlspecialchars($row["floor_level"]) . '</td>';
        echo '<td>' . htmlspecialchars($row["task_description"]) . '</td>';
        echo '<td>' . htmlspecialchars($row["schedule_date"]) . '</td>';
        echo '<td>' . htmlspecialchars($row["created_at"]) . '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="5">No schedule found</td></tr>';
}
$conn->close();
?>
            </tbody>
          </table>
        </div>
        <!-- Add Schedule Section -->
        <div class="todo">
          <div class="head">
            <h3>Add Schedule</h3>
            <i class="bx bx-plus" id="addScheduleBtn" style="cursor:pointer;"></i>
            <i class="bx bx-chevron-down" style="color: white;"></i>
          </div>
          <form id="addScheduleForm" method="post" action="add_schedule.php" style="display:none; margin-top:16px; background:#fff; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.07); padding:24px; max-width:400px;">
            <div style="display:flex; flex-direction:column; gap:16px;">
              <select name="user_id" required style="padding:10px 14px; border:1px solid #ddd; border-radius:8px; font-size:16px;">
                <option value="">Select User</option>
                <?php
                require_once __DIR__ . "/config.php";
                if ($conn->connect_error) {
                    die("Connection failed: " . $conn->connect_error);
                }
                $sql = "SELECT user_id, full_name FROM users ORDER BY full_name ASC";
                $result = $conn->query($sql);
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row["user_id"]) . '">' . htmlspecialchars($row["full_name"]) . '</option>';
                    }
                }
                $conn->close();
                ?>
              </select>
              <input type="text" name="floor_level" placeholder="Floor Level" required style="padding:10px 14px; border:1px solid #ddd; border-radius:8px; font-size:16px;">
              <input type="text" name="task_description" placeholder="Task Description" required style="padding:10px 14px; border:1px solid #ddd; border-radius:8px; font-size:16px;">
              <input type="date" name="schedule_date" required style="padding:10px 14px; border:1px solid #ddd; border-radius:8px; font-size:16px;">
              <button type="submit" style="background:green; color:#fff; border:none; border-radius:8px; padding:12px; font-size:16px; cursor:pointer; transition:background 0.2s;">Add Schedule</button>
            </div>
          </form>
        </div>
      </div>
      <div class="table-data">
        <div class="order">
          <div class="head">
            <h3>Task</h3>
            <i class="bx bx-search"></i>
            <i class="bx bx-filter"></i>
          </div>
          <table>
            <thead>
              <tr>
                <th>User</th>
                <th>Bin ID</th>
                <th>Machine ID</th>
                <th>Task Description</th>
                <th>Status</th>
                <th>Created At</th>
              </tr>
            </thead>
            <tbody>
<?php
require_once __DIR__ . "/config.php";
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$sql = "SELECT users.full_name, tasks.bin_id, tasks.machine_id, tasks.task_description, tasks.task_status, tasks.created_at
        FROM tasks
        JOIN users ON tasks.user_id = users.user_id
        ORDER BY tasks.created_at DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row["full_name"]) . '</td>';
        echo '<td>' . htmlspecialchars($row["bin_id"]) . '</td>';
        echo '<td>' . htmlspecialchars($row["machine_id"]) . '</td>';
        echo '<td>' . htmlspecialchars($row["task_description"]) . '</td>';
        echo '<td>' . htmlspecialchars($row["task_status"]) . '</td>';
        echo '<td>' . htmlspecialchars($row["created_at"]) . '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="6">No tasks found</td></tr>';
}
$conn->close();
?>
            </tbody>
          </table>
        </div>
        <!-- Add Task Section -->
        <div class="todo">
          <div class="head" style="display: flex; align-items: center; justify-content: space-between;">
            <h3>Add Tasks</h3>
            <div>
              <i class="bx bx-plus" id="addTaskBtn" style="cursor:pointer;"></i>
              <i class="bx bx-filter"></i>
            </div>
          </div>
          <form id="addTasksForm" method="post" action="add_tasks.php" style="display:none; background:#f9f9f9; border-radius:16px; box-shadow:0 4px 12px rgba(0,0,0,0.08); padding:32px; max-width:600px;">
            <div style="margin-bottom: 16px;">
              <label for="user_id" style="font-weight: 500;">User</label><br>
              <select id="user_id" name="user_id" required style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #ccc; margin-top: 6px;">
                <option value="">-- Select User --</option>
                <?php
                require_once __DIR__ . "/config.php";
                if ($conn->connect_error) {
                  die("Connection failed: " . $conn->connect_error);
                }
                $result = $conn->query("SELECT user_id, full_name FROM users ORDER BY full_name ASC");
                while ($row = $result->fetch_assoc()) {
                  echo '<option value="' . htmlspecialchars($row['user_id']) . '">' . htmlspecialchars($row['full_name']) . '</option>';
                }
                $conn->close();
                ?>
              </select>
            </div>
            <div style="margin-bottom: 16px;">
              <label for="bin_id" style="font-weight: 500;">Bin ID</label><br>
              <input type="text" id="bin_id" name="bin_id" required style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #ccc; margin-top: 6px;">
            </div>
            <div style="margin-bottom: 16px;">
              <label for="machine_id" style="font-weight: 500;">Machine ID</label><br>
              <input type="text" id="machine_id" name="machine_id" required style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #ccc; margin-top: 6px;">
            </div>
            <div style="margin-bottom: 16px;">
              <label for="task_description" style="font-weight: 500;">Task Description</label><br>
              <textarea id="task_description" name="task_description" rows="3" required style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #ccc; margin-top: 6px;"></textarea>
            </div>
            <div style="margin-bottom: 16px;">
              <label for="status" style="font-weight: 500;">Status</label><br>
              <select id="status" name="status" required style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #ccc; margin-top: 6px;">
                <option value="Select">Select</option>
                <option value="Pending">Pending</option>
                <option value="In Progress">In Progress</option>
                <option value="Done">Done</option>
              </select>
            </div>
            <div style="margin-bottom: 20px;">
              <label for="created_at" style="font-weight: 500;">Created At</label><br>
              <input type="datetime-local" id="created_at" name="created_at" required style="width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #ccc; margin-top: 6px;">
            </div>
            <button type="submit" style="background-color: green; color: white; padding: 12px 20px; border: none; border-radius: 10px; font-weight: 600; font-size: 16px; cursor: pointer; transition: 0.3s;">
              Add Task
            </button>
          </form>
        </div>
      </div>
    </main>
  </section>
  <!-- Chart.js CDN -->
  <script src="script.js"></script>
  <script>
document.addEventListener('DOMContentLoaded', function() {
  // Toggle Add Schedule form
  const addScheduleBtn = document.getElementById('addScheduleBtn');
  const addScheduleForm = document.getElementById('addScheduleForm');
  if (addScheduleBtn && addScheduleForm) {
    addScheduleBtn.onclick = function() {
      addScheduleForm.style.display =
        (addScheduleForm.style.display === 'none' || addScheduleForm.style.display === '') 
        ? 'block' 
        : 'none';
    };
  }

  // Toggle Add Task form
  const addTaskBtn = document.getElementById('addTaskBtn');
  const addTasksForm = document.getElementById('addTasksForm');
  if (addTaskBtn && addTasksForm) {
    addTaskBtn.onclick = function() {
      addTasksForm.style.display =
        (addTasksForm.style.display === 'none' || addTasksForm.style.display === '') 
        ? 'block' 
        : 'none';
    };
  }

  // Schedule filter redirect to bin.php
  document.querySelectorAll('.schedule-filter-btn').forEach(btn => {
    btn.onclick = function() {
      const filter = btn.getAttribute('data-filter');
      window.location.href = 'bin.php?schedule_filter=' + filter;
    };
  });
});
</script>

<!-- 🔽 Separate script para sa dropdown + active highlight -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  const scheduleDropdownBtn = document.getElementById('scheduleDropdownBtn');
  const scheduleDropdown = document.getElementById('scheduleDropdown');
  const scheduleFilterBtns = document.querySelectorAll('.schedule-filter-btn');

  if (scheduleDropdownBtn && scheduleDropdown) {
    scheduleDropdownBtn.onclick = function(e) {
      e.stopPropagation();
      scheduleDropdown.style.display =
        (scheduleDropdown.style.display === 'block') ? 'none' : 'block';
    };

    // Close dropdown kapag click sa labas
    document.addEventListener('click', function(event) {
      if (!scheduleDropdown.contains(event.target) && !scheduleDropdownBtn.contains(event.target)) {
        scheduleDropdown.style.display = 'none';
      }
    });
  }

  // Highlight active filter
  const urlParams = new URLSearchParams(window.location.search);
  const activeFilter = urlParams.get('schedule_filter');
  if (activeFilter) {
    scheduleFilterBtns.forEach(btn => {
      if (btn.dataset.filter === activeFilter) {
        btn.classList.add('active-filter');
      } else {
        btn.classList.remove('active-filter');
      }
    });
  }
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const bellNum = document.querySelector('.notification .num');
    const bell    = document.querySelector('.notification');

    async function loadNotifications() {
        try {
            const res  = await fetch('get_notifications.php');
            const data = await res.json();
            if (data.success) {
                bellNum.textContent = data.count; // 🔔 update the number

                // Optional: show a dropdown on click
                bell.onclick = (e) => {
                    e.preventDefault();
                    let list = data.data.map(n =>
                        `<li><strong>${n.msg}</strong><br><small>${n.time}</small></li>`
                    ).join('');
                    if (!list) list = '<li>No new notifications</li>';
                    showPopup(list);
                };
            }
        } catch(err) {
            console.error('Notification error:', err);
        }
    }

    function showPopup(content) {
        // Simple popup; replace with your own design if needed
        const box = document.createElement('div');
        box.innerHTML = `<ul style="
            background:#fff;padding:12px;border:1px solid #ccc;
            position:fixed;top:60px;right:20px;z-index:999;
            box-shadow:0 2px 8px rgba(0,0,0,0.15);
            max-width:260px;font-size:14px;
            border-radius:8px;list-style:none;">${content}</ul>`;
        document.body.appendChild(box);
        setTimeout(()=>box.remove(),5000);
    }

    loadNotifications();             // first load
    setInterval(loadNotifications, 5000); // every 5 seconds
});
</script>

<!-- === LIVE UPDATE SCRIPT === -->
<script>
async function fetchLiveBinData() {
  try {
    const response = await fetch("fetch_bin_status.php");
    const bins = await response.json();

    bins.forEach(bin => {
      const { bin_type, bin_status, last_updated } = bin;
      const percent = parseInt(bin_status);
      let color = "#6a5acd";
      if (percent < 50) color = "#1abc3a";
      else if (percent < 80) color = "#f39c12";
      else color = "#e74c3c";

      // find the bin card by data-bin (we added data-bin to each card)
      const binCard = document.querySelector(`[data-bin="${bin_type}"], [data-bin="${bin_type.toUpperCase()}"], [data-bin="${bin_type.toLowerCase()}"]`);
      if (binCard) {
        const circle = binCard.querySelector(".progress-circle");
        // update CSS background (conic gradient) and --color variable
        if (circle) {
          circle.style.background = `conic-gradient(${color} ${percent}%, #e6e6e6 0)`;
          circle.style.color = color;
          const label = circle.querySelector("span");
          if (label) label.textContent = percent + "%";
        }
        // update last-updated text
        const last = binCard.querySelector(".bin-date, .last-updated");
        if (last) last.textContent = "Last Updated: " + last_updated;
        // update floor and machine if provided in response (optional)
        const floor = binCard.querySelector(".bin-floor");
        if (floor && bin.floor_level !== undefined) floor.textContent = "Floor Level: " + bin.floor_level;
        const machine = binCard.querySelector(".bin-machine");
        if (machine && bin.machine_name !== undefined) machine.textContent = "Machine: " + bin.machine_name;
      } else {
        // If no existing card for this bin_type, you could optionally create one here.
        // For now, we skip creating new cards to preserve layout.
        // console.log('No card for', bin_type);
      }
    });
  } catch (err) {
    console.error("Live update error:", err);
  }
}

// Update every 5 seconds
setInterval(fetchLiveBinData, 5000);
fetchLiveBinData();
</script>
</body>
</html>
