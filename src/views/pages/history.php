<?php
// views/pages/history.php

// Connect to Database once at the top
require_once __DIR__ . '/../../config/config.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ---------------------------------------------------------
// PAGE SETUP & LAYOUT INCLUSION
// ---------------------------------------------------------
$page_title = "History & Reports - Admin";
$current_page = "history"; 
$extra_css = '<link rel="stylesheet" href="/assets/css/pages/history.css" />'; 

// Pull in the top layout (Head, Sidebar, Navbar)
require_once __DIR__ . '/../layouts/header.php'; 
?>

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

<?php 
// ---------------------------------------------------------
// PAGE SPECIFIC SCRIPTS (Injected into footer)
// ---------------------------------------------------------
ob_start(); 
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

<script>
// Utility to format date for grouping
function formatDate(dateStr, range) {
  const d = new Date(dateStr);
  if (isNaN(d)) return '';
  if (range === 'day') {
    return d.toLocaleDateString() + ' ' + d.getHours() + ':00';
  } else if (range === 'week') {
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

const wasteTypes = ['Biodegradable', 'Non-Biodegradable', 'Recyclable', 'Unrecognized Waste'];
const wasteTypeMap = {
  'Biodegradable': 'Biodegradable',
  'Non-Biodegradable': 'Non-Biodegradable',
  'Recyclable': 'Recyclable'
};

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

function aggregateLogs(logs, range) {
  const data = {};
  logs.forEach(log => {
    let type = wasteTypeMap[log.wasteType] || 'Unrecognized Waste';
    let label = formatDate(log.date, range);
    if (!data[label]) data[label] = { 'Biodegradable': 0, 'Non-Biodegradable': 0, 'Recyclable': 0, 'Unrecognized Waste': 0 };
    data[label][type] += 1;
  });
  
  const labels = Object.keys(data).sort((a, b) => new Date(a) - new Date(b));
  return {
    labels: labels,
    Biodegradable: labels.map(l => data[l]['Biodegradable']),
    'Non-Biodegradable': labels.map(l => data[l]['Non-Biodegradable']),
    Recyclable: labels.map(l => data[l]['Recyclable']),
    'Unrecognized Waste': labels.map(l => data[l]['Unrecognized Waste'])
  };
}

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
        { label: 'Biodegradable', data: agg.Biodegradable, borderColor: '#27ae60', backgroundColor: 'rgba(39,174,96,0.1)', fill: true, tension: 0.3 },
        { label: 'Non-Biodegradable', data: agg['Non-Biodegradable'], borderColor: '#f1c40f', backgroundColor: 'rgba(241,196,15,0.1)', fill: true, tension: 0.3 },
        { label: 'Recyclable', data: agg.Recyclable, borderColor: '#2980b9', backgroundColor: 'rgba(41,128,185,0.1)', fill: true, tension: 0.3 },
        { label: 'Unrecognized Waste', data: agg['Unrecognized Waste'], borderColor: '#e74c3c', backgroundColor: 'rgba(231,76,60,0.1)', fill: true, tension: 0.3 }
      ]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: true }, tooltip: { enabled: true } },
      scales: { y: { beginAtZero: true } }
    }
  });
}

// Initialize chart
renderTrendChart(document.getElementById('trendRange').value);
document.getElementById('trendRange').addEventListener('change', function() {
  renderTrendChart(this.value);
});

function exportTableToCSV(filename) {
  var csv = [];
  var rows = document.querySelectorAll("#historyTable tr");
  for (var i = 0; i < rows.length; i++) {
    var row = [], cols = rows[i].querySelectorAll("td, th");
    for (var j = 0; j < cols.length; j++) row.push('"' + cols[j].innerText + '"');
    csv.push(row.join(","));
  }
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
    for (var j = 0; j < cols.length; j++) row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
    csv.push(row.join(","));
  }
  var csvContent = csv.join("\n");
  var printWindow = window.open('', '', 'height=600,width=800');
  printWindow.document.write('<html><head><title>Print CSV</title><style>body{font-family:monospace;font-size:14px;} pre{white-space:pre-wrap;word-break:break-all;}</style></head><body>');
  printWindow.document.write('<h2>Waste Disposal Logs (CSV Format)</h2><pre>' + csvContent + '</pre></body></html>');
  printWindow.document.close();
  printWindow.focus();
  printWindow.print();
}

function exportTableToPDF() {
  const { jsPDF } = window.jspdf;
  var doc = new jsPDF();
  var headers = [];
  document.querySelectorAll("#historyTable thead th").forEach(function(th) { headers.push(th.innerText); });
  var data = [];
  document.querySelectorAll("#historyTable tbody tr").forEach(function(tr) {
    var row = [];
    tr.querySelectorAll("td").forEach(function(td, idx) {
      if (idx === 3) row.push('[Image]'); else row.push(td.innerText);
    });
    if(row.length) data.push(row);
  });
  doc.text("Waste Disposal Logs", 14, 14);
  doc.autoTable({ head: [headers], body: data, startY: 20 });
  doc.save("waste-history.pdf");
}

document.addEventListener('DOMContentLoaded', function() {
    // Dark mode logic specific to updating charts
    const switchMode = document.getElementById('switch-mode');
    if (switchMode) {
        switchMode.addEventListener('change', function() {
            updateChartColors(this.checked);
        });
    }

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
<?php 
$extra_js = ob_get_clean();

// Pull in the bottom layout
require_once __DIR__ . '/../layouts/footer.php'; 
?>