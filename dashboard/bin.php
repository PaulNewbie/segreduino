<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="style.css" />
  <title>Bin Monitoring</title>
  <style>
/* (keep your CSS exactly as before) */
body {
  font-family: 'Segoe UI', sans-serif;
  background-color: var(--light);
  margin: 0;
  padding: 0;
  color: var(--dark);
  transition: background 0.3s, color 0.3s;
}
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
.head-title h1 {
  font-size: 28px;
  color: var(--blue);
  transition: color 0.3s;
}
.breadcrumb {
  list-style: none;
  display: flex;
  gap: 8px;
  padding: 0;
  margin-top: 10px;
  color: var(--dark-grey);
}
.breadcrumb li a.active {
  font-weight: bold;
  color: var(--blue);
}
.bin-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 25px;
  background: var(--light);
  border-radius: 10px;
  overflow: hidden;
  transition: background 0.3s;
}
.bin-table th, .bin-table td {
  padding: 14px 16px;
  text-align: left;
  border-bottom: 1px solid var(--grey);
  vertical-align: middle;
  transition: background 0.3s, color 0.3s;
}
.bin-table th {
  background-color: var(--light-green);
  color: green;
  font-weight: 600;
  font-size: 16px;
}
.bin-table tr:hover {
  background-color: inherit;
}
.empty-btn {
  background-color: #27ae60;
  color: #ffffff;
  border: none;
  padding: 8px 18px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  font-size: 15px;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: background-color 0.3s;
}
.empty-btn:hover {
  background-color: var(--red);
}
.level-bar {
  background: var(--grey);
  border-radius: 8px;
  overflow: hidden;
  height: 18px;
  width: 120px;
  margin-right: 8px;
  display: inline-block;
  vertical-align: middle;
  transition: background 0.3s;
}
.level-fill {
  height: 100%;
  border-radius: 8px;
  transition: width 0.4s;
}
.level-fill.green { background: #27ae60; }
.level-fill.yellow { background: #f1c40f; }
.level-fill.red { background: #e74c3c; }
.bin-type-icon {
  font-size: 22px;
  vertical-align: middle;
  margin-right: 6px;
}
.waste-icon {
  font-size: 20px;
  vertical-align: middle;
  margin-right: 4px;
}
@media (max-width: 900px) {
  main {
    padding: 10px;
  }
  .card-custom {
    padding: 10px;
  }
  .bin-table th, .bin-table td {
    padding: 8px 6px;
    font-size: 14px;
  }
  .level-bar {
    width: 80px;
    height: 12px;
  }
}
/* small helper for the custom bin input */
#customBinType { display:none; margin-top:6px; width:100%; padding:8px; border-radius:6px; border:1px solid #ccc; }
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
      <a href="index.php">
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
    <li class="active">
      <a href="bin.php">
        <i class="bx bxs-shopping-bag-alt"></i>
        <span class="text">Bin Monitoring</span>
      </a>
    </li>
    <li>
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
    <i class="bx bx-menu" ></i>
    <a href="#" class="nav-link">Bin Monitoring</a>
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

  <!-- MAIN -->
  <main>
    <div class="head-title">
      <div class="left">
        <h1>Bin Monitoring</h1>
        <ul class="breadcrumb">
          <li></li>
          <li><i class="bx bx-chevron-right"></i></li>
          <li><a class="active" href="#">Kiosk Monitoring</a></li>
        </ul>
      </div>
    </div>

 <div class="card-custom">
  <h2>KIOSK</h2>
  <table class="bin-table">
    <thead>
      <tr>
        <th>Kiosk Name</th>
        <th>Location</th>
        <th>Actions</th> <!-- New column for Edit/Delete -->
      </tr>
    </thead>
    <tbody>
     <?php
require_once __DIR__ . "/config.php";
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$sql = "SELECT machine_id, machine_name, location FROM machines";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['machine_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['location']) . "</td>";
        echo "<td style='display:flex; gap:8px;'>";
        // ✅ Edit button - calls editKiosk()
        echo "<button class='empty-btn' style='background:#27ae60; padding:4px 10px; font-size:14px;' onclick='editKiosk(".$row['machine_id'].")'>Edit</button>";
        // ✅ Delete button - calls deleteKiosk()
        echo "<button class='empty-btn' style='background:#e74c3c; padding:4px 10px; font-size:14px;' onclick='deleteKiosk(".$row['machine_id'].")'>Delete</button>";
        echo "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='3'>No kiosks found.</td></tr>";
}

?>
        </tbody>
      </table>

<!-- Add Kiosk -->
<button class="empty-btn" id="addBinBtn" style="margin-top:8px;">
  <i class="bx bx-plus"></i> Add Kiosk
</button>
    </div>

    <div class="card-custom" style="margin-top:18px;">
      <h2>Current Bin Levels</h2>
      <!-- Add Trash Bin Button -->
      <button class="empty-btn" id="addTrashBinBtn" style="margin-bottom:18px;">
        <i class="bx bx-plus"></i> Add Trash Bin
      </button>

 <table class="bin-table">
  <thead>
    <tr>
      <th>Machine Name</th>
      <th>Floor Level</th>
      <th>Last updated</th>
      <th>Bin Type</th>
      <th style="text-align:center;">Actions</th> <!-- Centered header for buttons -->
    </tr>
  </thead>
  <tbody>
    <?php
    require_once __DIR__ . "/config.php";
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT b.bin_id, m.machine_name, b.floor_level, b.last_updated, b.bin_type
            FROM trash_bins b
            LEFT JOIN machines m ON b.machine_id = m.machine_id
            ORDER BY m.machine_name, b.floor_level";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['machine_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['floor_level']) . "</td>";
            echo "<td>" . htmlspecialchars($row['last_updated']) . "</td>";
            echo "<td>" . htmlspecialchars($row['bin_type']) . "</td>";
            // note: editTrashBin / deleteTrashBin use bin_id
            echo '<td style="display:flex; justify-content:center; gap:6px;">
                    <button class="empty-btn" style="padding:6px 12px; font-size:14px;" onclick="editTrashBin(' . $row['bin_id'] . ')">
                        <i class="bx bx-edit"></i> Edit
                    </button>
                    <button class="empty-btn" style="background:#e74c3c; padding:6px 12px; font-size:14px;" onclick="deleteTrashBin(' . $row['bin_id'] . ')">
                        <i class="bx bx-trash"></i> Delete
                    </button>
                  </td>';
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='5'>No bins found.</td></tr>";
    }
    
    ?>
  </tbody>
</table>
    </div>
  </main>
</section>

<!-- Add Kiosk Modal -->
<div id="addBinModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.25); align-items:center; justify-content:center; z-index:9999;">
  <form id="addBinForm" style="background:#fff; padding:32px 28px; border-radius:12px; min-width:320px; box-shadow:0 8px 32px rgba(0,0,0,0.18); display:flex; flex-direction:column; gap:18px; position:relative;">
    <h2 style="margin:0 0 8px 0;">Add Kiosk</h2>
    <label>
      Machine Name
      <input type="text" name="machine_name" required style="width:100%; padding:8px 12px; border-radius:6px; border:1px solid #ccc; margin-top:4px;">
    </label>
    <label>
      Location
      <select name="location" required style="width:100%; padding:8px 12px; border-radius:6px; border:1px solid #ccc; margin-top:4px;">
        <option value="">Select Floor</option>
        <option value="1st Floor">1st Floor</option>
        <option value="2nd Floor">2nd Floor</option>
        <option value="3rd Floor">3rd Floor</option>
      </select>
    </label>
    <div style="display:flex; gap:12px; margin-top:10px;">
      <button type="submit" class="empty-btn" style="flex:1;">Add</button>
      <button type="button" id="cancelAddBin" style="flex:1; background:#eee; color:#222; border:none; border-radius:6px; padding:8px 0; cursor:pointer;">Cancel</button>
    </div>
  </form>
</div>

<!-- Add Trash Bin Modal -->
<div id="addTrashBinModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.25); align-items:center; justify-content:center; z-index:9999;">
  <form id="addTrashBinForm" style="background:#fff; padding:24px 20px; border-radius:12px; min-width:320px; box-shadow:0 8px 32px rgba(0,0,0,0.18); display:flex; flex-direction:column; gap:12px; position:relative;">
    <h2 style="margin:0 0 8px 0;">Add Trash Bin</h2>

    <label>
      Machine Name
      <select id="addMachineSelect" name="machine_id" required style="width:100%; padding:8px 12px; border-radius:6px; border:1px solid #ccc; margin-top:4px;">
        <option value="">Select Machine</option>
        <?php
        // Fetch machines for dropdown
        require_once __DIR__ . "/config.php";
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $machineRes = $conn->query("SELECT machine_id, machine_name FROM machines");
        if ($machineRes && $machineRes->num_rows > 0) {
          while($m = $machineRes->fetch_assoc()) {
            echo "<option value='".htmlspecialchars($m['machine_id'])."'>".htmlspecialchars($m['machine_name'])."</option>";
          }
        }
        
        ?>
      </select>
    </label>

   <label>
  Floor Level
  <select name="floor_level" required style="width:100%; padding:8px 12px; border-radius:6px; border:1px solid #ccc; margin-top:4px;">
    <option value="">Select Floor</option>
    <option value="1st Floor">1st Floor</option>
    <option value="2nd Floor">2nd Floor</option>
    <option value="3rd Floor">3rd Floor</option>
  </select>
</label>

    <label>
      Bin Type
      <select id="binTypeSelect" name="bin_type" required style="width:100%; padding:8px 12px; border-radius:6px; border:1px solid #ccc; margin-top:4px;">
        <option value="">Select Type</option>
        <option value="BIODEGRADABLE">BIODEGRADABLE</option>
        <option value="NON-BIODEGRADABLE">NON-BIODEGRADABLE</option>
        <option value="RECYCLABLE">RECYCLABLE</option>
        <option value="OTHER">Other (Custom)</option>
      </select>
      <input type="text" id="customBinType" name="custom_bin_type" placeholder="Enter custom bin type name" />
    </label>

    <div style="display:flex; gap:12px; margin-top:6px;">
      <button type="submit" class="empty-btn" style="flex:1;">Add</button>
      <button type="button" id="cancelAddTrashBin" style="flex:1; background:#eee; color:#222; border:none; border-radius:6px; padding:8px 0; cursor:pointer;">Cancel</button>
    </div>
    <div id="addTrashBinMessage" style="color:#b71c1c; font-size:13px; display:none;"></div>
  </form>
</div>

<script>
// small helper: try parse json, fallback to text
async function safeParseResponse(resp) {
  try {
    const json = await resp.json();
    return { type: 'json', data: json };
  } catch (err) {
    const txt = await resp.text();
    return { type: 'text', data: txt };
  }
}

/* Add Kiosk modal logic */
const addBinBtn = document.getElementById('addBinBtn');
const addBinModal = document.getElementById('addBinModal');
const cancelAddBtn = document.getElementById('cancelAddBin');
const addBinForm = document.getElementById('addBinForm');

addBinBtn.onclick = function() {
    addBinModal.style.display = 'flex';
}

cancelAddBtn.onclick = function() {
    addBinModal.style.display = 'none';
    addBinForm.reset();
}

addBinForm.onsubmit = async function (e) {
  e.preventDefault();
  const formData = new FormData(addBinForm);

  try {
    const res = await fetch("add_kiosk.php", {
      method: "POST",
      body: formData
    });

    const parsed = await safeParseResponse(res);
    if (parsed.type === 'json') {
      const data = parsed.data;
      if (data.success) {
        alert("✅ " + data.message);
        addBinModal.style.display = "none";
        addBinForm.reset();
        location.reload(); // refresh table to show new kiosk
      } else {
        alert("❌ Failed to add kiosk: " + (data.message || parsed.data));
      }
    } else {
      alert("Server response: " + parsed.data);
    }
  } catch (error) {
    alert("⚠️ Error connecting to server: " + error);
  }
};

/* Add Trash Bin modal logic with limits and custom bin type handling */
const addTrashBinBtn = document.getElementById('addTrashBinBtn');
const addTrashBinModal = document.getElementById('addTrashBinModal');
const cancelAddTrashBinBtn = document.getElementById('cancelAddTrashBin');
const addTrashBinForm = document.getElementById('addTrashBinForm');
const addMachineSelect = document.getElementById('addMachineSelect');
const binTypeSelect = document.getElementById('binTypeSelect');
const customBinTypeInput = document.getElementById('customBinType');
const addTrashBinMessage = document.getElementById('addTrashBinMessage');

addTrashBinBtn.onclick = function() {
    addTrashBinModal.style.display = 'flex';
    addTrashBinMessage.style.display = 'none';
}

cancelAddTrashBinBtn.onclick = function() {
    addTrashBinModal.style.display = 'none';
    addTrashBinForm.reset();
    customBinTypeInput.style.display = 'none';
    addTrashBinMessage.style.display = 'none';
}

// show/hide custom bin input
binTypeSelect.addEventListener('change', function() {
  if (this.value === 'OTHER') {
    customBinTypeInput.style.display = 'block';
    customBinTypeInput.required = true;
  } else {
    customBinTypeInput.style.display = 'none';
    customBinTypeInput.required = false;
  }
});

// Before submit: check machine selected and the count of bins for it (max 3)
addTrashBinForm.onsubmit = async function(e) {
    e.preventDefault();
    addTrashBinMessage.style.display = 'none';

    const machineId = addMachineSelect.value;
    if (!machineId) {
      addTrashBinMessage.textContent = 'Please select a machine.';
      addTrashBinMessage.style.display = 'block';
      return;
    }

    // check how many bins already exist for this machine
    try {
      const respCheck = await fetch(`count_bins_by_machine.php?machine_id=${encodeURIComponent(machineId)}`);
      // expected JSON: { success: true, count: N }
      const parsed = await safeParseResponse(respCheck);
      if (parsed.type === 'json' && parsed.data && parsed.data.success) {
        const count = parseInt(parsed.data.count || 0, 10);
        if (count >= 3) {
          addTrashBinMessage.textContent = 'Maximum of 3 trash bins allowed per machine.';
          addTrashBinMessage.style.display = 'block';
          return;
        }
      } else {
        // If endpoint missing or not JSON, allow (or you can enforce via server)
        // but warn
        console.warn('count_bins_by_machine response:', parsed);
      }
    } catch (err) {
      console.warn('Error checking bin count:', err);
    }

    // prepare actual form data. If custom type is chosen, replace bin_type with custom field
    const fd = new FormData(addTrashBinForm);
    if (binTypeSelect.value === 'OTHER') {
      fd.set('bin_type', customBinTypeInput.value.trim());
    } else {
      fd.set('bin_type', binTypeSelect.value);
    }

    try {
      const resp = await fetch('add_trash_bin.php', { method: 'POST', body: fd });
      const parsed = await safeParseResponse(resp);
      if (parsed.type === 'json') {
        const data = parsed.data;
        if (data.success) {
          alert('✅ Trash bin added successfully!');
          addTrashBinModal.style.display = 'none';
          addTrashBinForm.reset();
          customBinTypeInput.style.display = 'none';
          location.reload();
        } else {
          alert('Failed to add trash bin: ' + (data.message || JSON.stringify(data)));
        }
      } else {
        // fallback text
        if (typeof parsed.data === 'string' && parsed.data.trim() === 'success') {
          alert('✅ Trash bin added successfully!');
          addTrashBinModal.style.display = 'none';
          addTrashBinForm.reset();
          customBinTypeInput.style.display = 'none';
          location.reload();
        } else {
          alert('Failed to add trash bin: ' + parsed.data);
        }
      }
    } catch (err) {
      alert('Error connecting to server: ' + err);
    }
};

/* notifications loader (unchanged logic but robust parse) */
document.addEventListener('DOMContentLoaded', function() {
    const bellNum = document.querySelector('.notification .num');
    const bell    = document.querySelector('.notification');

    async function loadNotifications() {
        try {
            const res  = await fetch('get_notifications.php');
            const parsed = await safeParseResponse(res);
            if (parsed.type === 'json' && parsed.data.success) {
                bellNum.textContent = parsed.data.count; // 🔔 update the number

                bell.onclick = (e) => {
                    e.preventDefault();
                    let list = parsed.data.data.map(n =>
                        `<li><strong>${n.msg}</strong><br><small>${n.time}</small></li>`
                    ).join('');
                    if (!list) list = '<li>No new notifications</li>';
                    showPopup(list);
                };
            } else {
                console.warn('notifications load fallback:', parsed);
            }
        } catch(err) {
            console.error('Notification error:', err);
        }
    }

    function showPopup(content) {
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

/* -------------------
   Separate edit/delete functions
   ------------------- */

/* --- Kiosk (machine) edit/delete --- */
async function editKiosk(machineId) {
  try {
    const resp = await fetch(`get_machine.php?id=${encodeURIComponent(machineId)}`);
    const parsed = await safeParseResponse(resp);
    if (parsed.type === 'json' && parsed.data.success && parsed.data.machine) {
      const machine = parsed.data.machine;
      const newName = prompt('Edit Kiosk Name:', machine.machine_name || '');
      if (newName === null) return;
      const newLocation = prompt('Edit Location:', machine.location || '');
      if (newLocation === null) return;

      const form = new FormData();
      form.append('machine_id', machineId);
      form.append('machine_name', newName.trim());
      form.append('location', newLocation.trim());

      const updateResp = await fetch('edit_kiosk.php', { method: 'POST', body: form });
      const parsedUp = await safeParseResponse(updateResp);
      if (parsedUp.type === 'json') {
        if (parsedUp.data.success) {
          alert('✅ ' + parsedUp.data.message);
          location.reload();
        } else {
          alert('❌ Update failed: ' + (parsedUp.data.message || JSON.stringify(parsedUp.data)));
        }
      } else {
        alert('Server response: ' + parsedUp.data);
      }
    } else {
      alert('Failed to load kiosk: ' + (parsed.type === 'json' ? (parsed.data.message || JSON.stringify(parsed.data)) : parsed.data));
    }
  } catch (err) {
    console.error('editKiosk error', err);
    alert('Error editing kiosk: ' + err);
  }
}

async function deleteKiosk(machineId) {
  const ok = confirm('Are you sure you want to delete this kiosk? This is permanent.');
  if (!ok) return;

  try {
    const form = new FormData();
    form.append('machine_id', machineId);

    const resp = await fetch('delete_kiosk.php', {
      method: 'POST',
      body: form
    });
    const parsed = await safeParseResponse(resp);
    if (parsed.type === 'json') {
      if (parsed.data.success) {
        alert('✅ ' + parsed.data.message);
        location.reload();
      } else {
        alert('❌ Delete failed: ' + (parsed.data.message || JSON.stringify(parsed.data)));
      }
    } else {
      alert('Server response: ' + parsed.data);
    }
  } catch (err) {
    console.error('deleteKiosk error', err);
    alert('Error deleting kiosk: ' + err);
  }
}

/* --- Trash Bin edit/delete (separate) --- */
async function editTrashBin(binId) {
  try {
    const resp = await fetch(`get_trash_bin.php?id=${encodeURIComponent(binId)}`);
    const parsed = await safeParseResponse(resp);
    if (parsed.type === 'json' && parsed.data.success && parsed.data.bin) {
      const bin = parsed.data.bin;
      // let user edit floor and bin_type (supports custom type)
      const newFloor = prompt('Edit Floor Level:', bin.floor_level || '');
      if (newFloor === null) return;
      // allow edit bin type text (not limited to preset)
      const newType = prompt('Edit Bin Type (free text allowed):', bin.bin_type || '');
      if (newType === null) return;

      const form = new FormData();
      form.append('bin_id', binId);
      form.append('floor_level', newFloor.trim());
      form.append('bin_type', newType.trim());

      const updateResp = await fetch('edit_trash_bin.php', { method: 'POST', body: form });
      const parsedUp = await safeParseResponse(updateResp);
      if (parsedUp.type === 'json') {
        if (parsedUp.data.success) {
          alert('✅ Bin updated successfully');
          location.reload();
        } else {
          alert('❌ ' + (parsedUp.data.message || JSON.stringify(parsedUp.data)));
        }
      } else {
        alert('Server response: ' + parsedUp.data);
      }
    } else {
      alert('Failed to load bin: ' + (parsed.type === 'json' ? (parsed.data.message || JSON.stringify(parsed.data)) : parsed.data));
    }
  } catch (err) {
    console.error('editTrashBin error', err);
    alert('Error editing bin: ' + err);
  }
}

async function deleteTrashBin(binId) {
  const ok = confirm('Are you sure you want to delete this trash bin? This is permanent.');
  if (!ok) return;

  try {
    const form = new FormData();
    form.append('bin_id', binId);

    const resp = await fetch('delete_trash_bin.php', {
      method: 'POST',
      body: form
    });
    const parsed = await safeParseResponse(resp);
    if (parsed.type === 'json') {
      if (parsed.data.success) {
        alert('✅ Bin deleted successfully');
        location.reload();
      } else {
        alert('❌ Delete failed: ' + (parsed.data.message || JSON.stringify(parsed.data)));
      }
    } else {
      alert('Server response: ' + parsed.data);
    }
  } catch (err) {
    console.error('deleteTrashBin error', err);
    alert('Error deleting bin: ' + err);
  }
}
</script>

<script src="script.js"></script>
</body>
</html>
