<?php
// views/pages/bin.php

// Connect to Database once at the top
require_once __DIR__ . '/../../config/config.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ---------------------------------------------------------
// PAGE SETUP & LAYOUT INCLUSION
// ---------------------------------------------------------
$page_title = "Bin Monitoring - Admin";
$current_page = "bin"; 
$extra_css = '<link rel="stylesheet" href="/assets/css/pages/bin.css" />';

// Pull in the top layout (Head, Sidebar, Navbar)
require_once __DIR__ . '/../layouts/header.php'; 
?>

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
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $sql = "SELECT machine_id, machine_name, location FROM machines";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['machine_name'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($row['location'] ?? '') . "</td>";
                echo "<td style='display:flex; gap:8px;'>";
                echo "<button class='empty-btn' style='background:#27ae60; padding:4px 10px; font-size:14px;' onclick='editKiosk(".$row['machine_id'].")'>Edit</button>";
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

    <button class="empty-btn" id="addBinBtn" style="margin-top:8px;">
      <i class="bx bx-plus"></i> Add Kiosk
    </button>
  </div>

  <div class="card-custom" style="margin-top:18px;">
    <h2>Current Bin Levels</h2>
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
          <th style="text-align:center;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $sql = "SELECT b.bin_id, m.machine_name, b.floor_level, b.last_updated, b.bin_type
                FROM trash_bins b
                LEFT JOIN machines m ON b.machine_id = m.machine_id
                ORDER BY m.machine_name, b.floor_level";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['machine_name'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($row['floor_level'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($row['last_updated'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($row['bin_type'] ?? '') . "</td>";
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

<div id="addTrashBinModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.25); align-items:center; justify-content:center; z-index:9999;">
  <form id="addTrashBinForm" style="background:#fff; padding:24px 20px; border-radius:12px; min-width:320px; box-shadow:0 8px 32px rgba(0,0,0,0.18); display:flex; flex-direction:column; gap:12px; position:relative;">
    <h2 style="margin:0 0 8px 0;">Add Trash Bin</h2>
    <label>
      Machine Name
      <select id="addMachineSelect" name="machine_id" required style="width:100%; padding:8px 12px; border-radius:6px; border:1px solid #ccc; margin-top:4px;">
        <option value="">Select Machine</option>
        <?php
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

<?php 
// ---------------------------------------------------------
// PAGE SPECIFIC SCRIPTS (Injected into footer)
// ---------------------------------------------------------
ob_start(); 
?>
<script>
async function safeParseResponse(resp) {
  try {
    const json = await resp.json();
    return { type: 'json', data: json };
  } catch (err) {
    const txt = await resp.text();
    return { type: 'text', data: txt };
  }
}

/* --- Kiosk Modals --- */
const addBinBtn = document.getElementById('addBinBtn');
const addBinModal = document.getElementById('addBinModal');
const cancelAddBtn = document.getElementById('cancelAddBin');
const addBinForm = document.getElementById('addBinForm');

addBinBtn.onclick = () => { addBinModal.style.display = 'flex'; }
cancelAddBtn.onclick = () => { addBinModal.style.display = 'none'; addBinForm.reset(); }

addBinForm.onsubmit = async function (e) {
  e.preventDefault();
  const formData = new FormData(addBinForm);
  try {
    // UPDATED PATH TO ABSOLUTE
    const res = await fetch("/controllers/Actions/add_kiosk.php", { method: "POST", body: formData });
    const parsed = await safeParseResponse(res);
    if (parsed.type === 'json' && parsed.data.success) {
        alert("✅ " + parsed.data.message);
        location.reload(); 
    } else {
        alert("❌ Failed to add kiosk: " + (parsed.data.message || parsed.data));
    }
  } catch (error) { alert("⚠️ Error connecting to server: " + error); }
};

/* --- Trash Bin Modals --- */
const addTrashBinBtn = document.getElementById('addTrashBinBtn');
const addTrashBinModal = document.getElementById('addTrashBinModal');
const cancelAddTrashBinBtn = document.getElementById('cancelAddTrashBin');
const addTrashBinForm = document.getElementById('addTrashBinForm');
const addMachineSelect = document.getElementById('addMachineSelect');
const binTypeSelect = document.getElementById('binTypeSelect');
const customBinTypeInput = document.getElementById('customBinType');
const addTrashBinMessage = document.getElementById('addTrashBinMessage');

addTrashBinBtn.onclick = () => { addTrashBinModal.style.display = 'flex'; addTrashBinMessage.style.display = 'none'; }
cancelAddTrashBinBtn.onclick = () => { addTrashBinModal.style.display = 'none'; addTrashBinForm.reset(); customBinTypeInput.style.display = 'none'; }

binTypeSelect.addEventListener('change', function() {
  if (this.value === 'OTHER') {
    customBinTypeInput.style.display = 'block';
    customBinTypeInput.required = true;
  } else {
    customBinTypeInput.style.display = 'none';
    customBinTypeInput.required = false;
  }
});

addTrashBinForm.onsubmit = async function(e) {
    e.preventDefault();
    addTrashBinMessage.style.display = 'none';
    const machineId = addMachineSelect.value;
    if (!machineId) return;

    const fd = new FormData(addTrashBinForm);
    if (binTypeSelect.value === 'OTHER') {
      fd.set('bin_type', customBinTypeInput.value.trim());
    } else {
      fd.set('bin_type', binTypeSelect.value);
    }

    try {
      // UPDATED PATH
      const resp = await fetch('/controllers/Actions/add_trash_bin.php', { method: 'POST', body: fd });
      const parsed = await safeParseResponse(resp);
      if (parsed.type === 'json' && parsed.data.success) {
          alert('✅ Trash bin added successfully!');
          location.reload();
      } else if (typeof parsed.data === 'string' && parsed.data.trim() === 'success') {
          alert('✅ Trash bin added successfully!');
          location.reload();
      } else {
          alert('Failed to add trash bin: ' + (parsed.data.message || parsed.data));
      }
    } catch (err) { alert('Error connecting to server: ' + err); }
};

/* --- Edit / Delete Functions --- */
async function editKiosk(machineId) {
  try {
    const resp = await fetch(`/controllers/Api/get_machine.php?id=${encodeURIComponent(machineId)}`);
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

      const updateResp = await fetch('/controllers/Actions/edit_kiosk.php', { method: 'POST', body: form });
      const parsedUp = await safeParseResponse(updateResp);
      if (parsedUp.type === 'json' && parsedUp.data.success) {
          alert('✅ ' + parsedUp.data.message); location.reload();
      } else { alert('❌ Update failed'); }
    }
  } catch (err) { alert('Error editing kiosk: ' + err); }
}

async function deleteKiosk(machineId) {
  if (!confirm('Are you sure you want to delete this kiosk? This is permanent.')) return;
  try {
    const form = new FormData();
    form.append('machine_id', machineId);
    const resp = await fetch('/controllers/Actions/delete_kiosk.php', { method: 'POST', body: form });
    const parsed = await safeParseResponse(resp);
    if (parsed.type === 'json' && parsed.data.success) {
        alert('✅ ' + parsed.data.message); location.reload();
    } else { alert('❌ Delete failed'); }
  } catch (err) { alert('Error deleting kiosk: ' + err); }
}

async function editTrashBin(binId) {
  try {
    const resp = await fetch(`/controllers/Api/get_trash_bin.php?id=${encodeURIComponent(binId)}`);
    const parsed = await safeParseResponse(resp);
    if (parsed.type === 'json' && parsed.data.success && parsed.data.bin) {
      const bin = parsed.data.bin;
      const newFloor = prompt('Edit Floor Level:', bin.floor_level || '');
      if (newFloor === null) return;
      const newType = prompt('Edit Bin Type (free text allowed):', bin.bin_type || '');
      if (newType === null) return;

      const form = new FormData();
      form.append('bin_id', binId);
      form.append('floor_level', newFloor.trim());
      form.append('bin_type', newType.trim());

      const updateResp = await fetch('/controllers/Actions/edit_trash_bin.php', { method: 'POST', body: form });
      const parsedUp = await safeParseResponse(updateResp);
      if (parsedUp.type === 'json' && parsedUp.data.success) {
          alert('✅ Bin updated successfully'); location.reload();
      } else { alert('❌ Update failed'); }
    }
  } catch (err) { alert('Error editing bin: ' + err); }
}

async function deleteTrashBin(binId) {
  if (!confirm('Are you sure you want to delete this trash bin? This is permanent.')) return;
  try {
    const form = new FormData();
    form.append('bin_id', binId);
    const resp = await fetch('/controllers/Actions/delete_trash_bin.php', { method: 'POST', body: form });
    const parsed = await safeParseResponse(resp);
    if (parsed.type === 'json' && parsed.data.success) {
        alert('✅ Bin deleted successfully'); location.reload();
    } else { alert('❌ Delete failed'); }
  } catch (err) { alert('Error deleting bin: ' + err); }
}
</script>
<?php 
$extra_js = ob_get_clean();

// Pull in the bottom layout
require_once __DIR__ . '/../layouts/footer.php'; 
?>