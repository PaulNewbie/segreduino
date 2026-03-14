<?php
session_start();
$_SESSION['username'] = 'Admin User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="../../assets/css/style.css" />
  <title>History & Reports</title>
  <style>

main {
  padding: 20px;
}
.card-custom {
  background: var(--grey);
  padding: 25px;
  border-radius: 12px;
  box-shadow: 0 4px 8px rgba(0,0,0,0.05);
  margin-top: 20px;
  transition: background 0.3s;
}
    .card-custom h2 {
      margin-bottom: 20px;
      color: black;
    }
    .log-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 25px;
    }
    .log-table th, .log-table td {
      padding: 12px 16px;
      text-align: left;
      border-bottom: 1px solid #eee;
    }
    .log-table th {
      background-color: var(--light-green);
      color: green;
      font-weight: 600;
    }
    .log-table tr:hover {
      background-color: inherit;
    }
    .export-buttons {
      display: flex;
      gap: 12px;
      margin-top: 16px;
    }
    .export-buttons button {
      background-color: #2ecc71;
      color: white;
      border: none;
      padding: 10px 18px;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.3s ease;
      font-weight: 500;
    }
    .export-buttons button:hover {
      background-color: #27ae60;
    }
    .chart-section {
      margin-top: 30px;
    }
    .head-title h1 {
      font-size: 28px;
      color: #2c3e50;
    }
    .breadcrumb {
      list-style: none;
      display: flex;
      gap: 8px;
      padding: 0;
      margin-top: 10px;
      color: #888;
    }
    .breadcrumb li a.active {
      font-weight: bold;
      color: #3498db;
    }
    @media (max-width: 900px) {
      main {
        padding: 10px;
      }
      .card-custom {
        padding: 10px;
      }
      .log-table th, .log-table td {
        padding: 8px 6px;
        font-size: 14px;
      }
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
      <a href="dashboard.php">
        <i class="bx bxs-dashboard"></i>
        <span class="text">Dashboard</span>
      </a>
    </li>
    <li>
      <a href="user.php">
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
    <li class="active">
      <a href="history.php">
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

<!-- CONTENT -->
<section id="content">
  <!-- NAVBAR -->
  <nav>
    <i class="bx bx-menu"></i>
    <a href="#" class="nav-link">History & Reports</a>
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
    <img src="../../assets/img/pdm logo.jfif" alt="profile" />
    </a>
  </nav>

  <!-- MAIN -->
  <main>
    <div class="head-title">
      <div class="left">
        <h1>History & Reports</h1>
        <ul class="breadcrumb">
          <li></li>
          <li><i class="bx bx-chevron-right"></i></li>
          <li><a class="active" href="#">History & Reports</a></li>
        </ul>
      </div>
    </div>

    <div class="card-custom">
      <h2>Unrecognized Waste</h2>
      <table class="log-table" id="historyTable">
        <thead>
          <tr>
            <th>Date/Time</th>
            <th>Bin Type</th>
            <th>Waste Type</th>
            <th>Image</th>
          </tr>
        </thead>
        <tbody>
          <?php
          require_once __DIR__ . '/../../config/config.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
          $sql = "SELECT disposal_time, bin_type, waste_type, image_data FROM waste_disposal_log ORDER BY disposal_time DESC";
          $result = $conn->query($sql);

          if ($result && $result->num_rows > 0) {
              while($row = $result->fetch_assoc()) {
                  echo "<tr>";
                  echo "<td>" . htmlspecialchars($row['disposal_time']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['bin_type']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['waste_type']) . "</td>";
                  echo "<td><img src='data:image/jpeg;base64," . base64_encode($row['image_data']) . "' alt='Waste Image' style='max-width:60px;max-height:60px;'/></td>";
                  echo "</tr>";
              }
          } else {
              echo "<tr><td colspan='4'>No records found.</td></tr>";
          }
          
          ?>
        </tbody>
      </table>
      <div class="export-buttons">
        <button onclick="exportTableToCSV('waste-history.csv')">Export CSV</button>
        <button onclick="printCSV()">Print CSV</button>
        <button onclick="exportTableToPDF()">Export PDF</button>
      </div>
    </div>

    <div class="card-custom chart-section">
      <h2>Waste Data Report</h2>
      <!-- Add this above your chart in the HTML -->
      <div style="margin-bottom:16px;">
        <label for="trendRange">View Trends By:</label>
        <select id="trendRange" style="padding:6px 12px; border-radius:6px;">
          <option value="day">Day</option>
          <option value="week">Week</option>
          <option value="month" selected>Month</option>
          <option value="year">Year</option>
        </select>
      </div>
      <canvas id="trendChart" height="80"></canvas>
    </div>
  </main>
</section>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Utility to format date for grouping
function formatDate(dateStr, range) {
  const d = new Date(dateStr);
  if (isNaN(d)) return '';
  if (range === 'day') {
    return d.toLocaleDateString() + ' ' + d.getHours() + ':00';
  } else if (range === 'week') {
    // Get ISO week number
    const temp = new Date(d.getFullYear(), d.getMonth(), d.getDate());
    const dayNum = temp.getDay() || 7;
    temp.setDate(temp.getDate() + 4 - dayNum);
    const yearStart = new Date(temp.getFullYear(),0,1);
    const weekNum = Math.ceil((((temp - yearStart) / 86400000) + 1)/7);
    return 'Week ' + weekNum + ' ' + d.getFullYear();
  } else if (range === 'month') {
    return d.toLocaleString('default', { month: 'short', year: 'numeric' });
  } else if (range === 'year') {
    return d.getFullYear().toString();
  }
  return '';
}

// Waste types to track
const wasteTypes = ['Biodegradable', 'Non-Biodegradable', 'Recyclable', 'Unrecognized Waste'];
const wasteTypeMap = {
  'Biodegradable': 'Biodegradable',
  'Non-Biodegradable': 'Non-Biodegradable',
  'Recyclable': 'Recyclable'
  // Any other value will be counted as 'Unrecognized Waste'
};

// Parse logs from table
function parseLogs() {
  const rows = document.querySelectorAll('#historyTable tbody tr');
  const logs = [];
  rows.forEach(tr => {
    const tds = tr.querySelectorAll('td');
    if (tds.length >= 3) {
      logs.push({
        date: tds[0].innerText,
        wasteType: tds[2].innerText.trim()
      });
    }
  });
  return logs;
}

// Aggregate logs by range and waste type
function aggregateLogs(logs, range) {
  const data = {};
  logs.forEach(log => {
    let type = wasteTypeMap[log.wasteType] || 'Unrecognized Waste';
    let label = formatDate(log.date, range);
    if (!data[label]) data[label] = { 'Biodegradable': 0, 'Non-Biodegradable': 0, 'Recyclable': 0, 'Unrecognized Waste': 0 };
    data[label][type] += 1;
  });
  // Sort labels chronologically
  const labels = Object.keys(data).sort((a, b) => new Date(a) - new Date(b));
  const result = {
    labels: labels,
    Biodegradable: labels.map(l => data[l]['Biodegradable']),
    'Non-Biodegradable': labels.map(l => data[l]['Non-Biodegradable']),
    Recyclable: labels.map(l => data[l]['Recyclable']),
    'Unrecognized Waste': labels.map(l => data[l]['Unrecognized Waste'])
  };
  return result;
}

// Chart rendering
let trendChart;
function renderTrendChart(range) {
  const logs = parseLogs();
  const agg = aggregateLogs(logs, range);
  const ctx = document.getElementById('trendChart').getContext('2d');
  if (trendChart) trendChart.destroy();
  trendChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: agg.labels,
      datasets: [
        {
          label: 'Biodegradable',
          data: agg.Biodegradable,
          borderColor: '#27ae60',
          backgroundColor: 'rgba(39,174,96,0.1)',
          fill: true,
          tension: 0.3
        },
        {
          label: 'Non-Biodegradable',
          data: agg['Non-Biodegradable'],
          borderColor: '#f1c40f',
          backgroundColor: 'rgba(241,196,15,0.1)',
          fill: true,
          tension: 0.3
        },
        {
          label: 'Recyclable',
          data: agg.Recyclable,
          borderColor: '#2980b9',
          backgroundColor: 'rgba(41,128,185,0.1)',
          fill: true,
          tension: 0.3
        },
        {
          label: 'Unrecognized Waste',
          data: agg['Unrecognized Waste'],
          borderColor: '#e74c3c',
          backgroundColor: 'rgba(231,76,60,0.1)',
          fill: true,
          tension: 0.3
        }
      ]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: true },
        tooltip: { enabled: true }
      },
      scales: {
        y: { beginAtZero: true }
      }
    }
  });
}

// Initial render
renderTrendChart(document.getElementById('trendRange').value);

// Dropdown event
document.getElementById('trendRange').addEventListener('change', function() {
  renderTrendChart(this.value);
});

// Simple CSV export
function exportTableToCSV(filename) {
  var csv = [];
  var rows = document.querySelectorAll("#historyTable tr");
  for (var i = 0; i < rows.length; i++) {
    var row = [], cols = rows[i].querySelectorAll("td, th");
    for (var j = 0; j < cols.length; j++)
      row.push('"' + cols[j].innerText + '"');
    csv.push(row.join(","));
  }
  // Download CSV
  var csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
  var downloadLink = document.createElement("a");
  downloadLink.download = filename;
  downloadLink.href = window.URL.createObjectURL(csvFile);
  downloadLink.style.display = "none";
  document.body.appendChild(downloadLink);
  downloadLink.click();
}

function printCSV() {
  var csv = [];
  var rows = document.querySelectorAll("#historyTable tr");
  for (var i = 0; i < rows.length; i++) {
    var row = [], cols = rows[i].querySelectorAll("td, th");
    for (var j = 0; j < cols.length; j++)
      row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
    csv.push(row.join(","));
  }
  var csvContent = csv.join("\n");
  var printWindow = window.open('', '', 'height=600,width=800');
  printWindow.document.write('<html><head><title>Print CSV</title>');
  printWindow.document.write('<style>body{font-family:monospace;font-size:14px;} pre{white-space:pre-wrap;word-break:break-all;}</style>');
  printWindow.document.write('</head><body>');
  printWindow.document.write('<h2>Waste Disposal Logs (CSV Format)</h2>');
  printWindow.document.write('<pre>' + csvContent + '</pre>');
  printWindow.document.write('</body></html>');
  printWindow.document.close();
  printWindow.focus();
  printWindow.print();
}

function exportTableToPDF() {
  const { jsPDF } = window.jspdf;
  var doc = new jsPDF();

  // Get table headers
  var headers = [];
  document.querySelectorAll("#historyTable thead th").forEach(function(th) {
    headers.push(th.innerText);
  });

  // Get table rows
  var data = [];
  document.querySelectorAll("#historyTable tbody tr").forEach(function(tr) {
    var row = [];
    tr.querySelectorAll("td").forEach(function(td, idx) {
      // For the image column, always use [Image] as in your PHP
      if (idx === 3) {
        row.push('[Image]');
      } else {
        row.push(td.innerText);
      }
    });
    if(row.length) data.push(row);
  });

  doc.text("Waste Disposal Logs", 14, 14);
  doc.autoTable({
    head: [headers],
    body: data,
    startY: 20
  });

  doc.save("waste-history.pdf");
}

document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle
    const menuIcon = document.querySelector('nav i.bx-menu');
    const sidebar = document.getElementById('sidebar');
    
    menuIcon.onclick = function() {
        sidebar.classList.toggle('active');
    };

    // Dark mode toggle
    const switchMode = document.getElementById('switch-mode');
    
    // Check saved preference
    if(localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark');
        switchMode.checked = true;
    }

    // Toggle dark mode
    switchMode.addEventListener('change', function() {
        if(this.checked) {
            document.body.classList.add('dark');
            localStorage.setItem('darkMode', 'true');
            // Update chart colors for dark mode
            updateChartColors(true);
        } else {
            document.body.classList.remove('dark');
            localStorage.setItem('darkMode', 'false');
            // Update chart colors for light mode
            updateChartColors(false);
        }
    });

    // Function to update chart colors
    function updateChartColors(isDark) {
        const textColor = isDark ? '#ffffff' : '#666666';
        if (window.trendChart) {
            window.trendChart.options.scales.x.ticks.color = textColor;
            window.trendChart.options.scales.y.ticks.color = textColor;
            window.trendChart.options.plugins.legend.labels.color = textColor;
            window.trendChart.update();
        }
    }
});
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<script src="../../assets/js/script.js"></script>
</body>
</html>