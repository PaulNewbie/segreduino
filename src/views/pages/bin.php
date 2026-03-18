<?php
// src/views/pages/bin.php

require_once __DIR__ . '/../../config/config.php';
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// ---------------------------------------------------------
// DATA FETCHING & GROUPING LOGIC
// ---------------------------------------------------------
// 1. Fetch all machines (Kiosks)
$machines = [];
$machineSql = "SELECT machine_id, machine_name, location FROM machines ORDER BY machine_name ASC";
$mResult = $conn->query($machineSql);
if ($mResult && $mResult->num_rows > 0) {
    while ($m = $mResult->fetch_assoc()) {
        $m['bins'] = []; // Initialize empty array for bins
        $machines[$m['machine_id']] = $m;
    }
}

// 2. Fetch all bins and slot them into their parent machines
$binSql = "SELECT bin_id, machine_id, floor_level, last_updated, bin_type, bin_status FROM trash_bins ORDER BY floor_level ASC";
$bResult = $conn->query($binSql);
if ($bResult && $bResult->num_rows > 0) {
    while ($b = $bResult->fetch_assoc()) {
        if (isset($machines[$b['machine_id']])) {
            $machines[$b['machine_id']]['bins'][] = $b;
        }
    }
}

// ---------------------------------------------------------
// PAGE SETUP
// ---------------------------------------------------------
$page_title = "Bin Monitoring - Admin";
$current_page = "bin"; 
$extra_css = '<link rel="stylesheet" href="/assets/css/pages/bin.css" />';
require_once __DIR__ . '/../layouts/header.php'; 
?>

<main id="main-content">
  <div class="head-title">
    <div class="left">
      <h1>Bin Monitoring</h1>
      <ul class="breadcrumb">
        <li><a href="/dashboard.php" style="color: #888; pointer-events: auto;">Dashboard</a></li>
        <li><i class="bx bx-chevron-right"></i></li>
        <li><a class="active" href="/bin.php">Kiosk Monitoring</a></li>
      </ul>
    </div>
  </div>

  <div class="top-controls">
    <button class="empty-btn" id="addBinBtn">
      <i class="bx bx-plus"></i> Register New Kiosk
    </button>
  </div>

  <div class="kiosk-grid">
    <?php if(empty($machines)): ?>
        <div style="text-align:center; padding: 40px; color: #888; background:#fff; border-radius:12px;">
            No Kiosks registered. Click 'Register New Kiosk' to begin.
        </div>
    <?php else: ?>
        <?php foreach ($machines as $kiosk): ?>
            <div class="kiosk-card">
                <div class="kiosk-header">
                    <div class="kiosk-title">
                        <h2><?= htmlspecialchars($kiosk['machine_name']) ?></h2>
                        <p><i class='bx bx-map'></i> Location: <?= htmlspecialchars($kiosk['location']) ?></p>
                    </div>
                    <div class="kiosk-actions">
                        <button class="empty-btn btn-edit" onclick="editKiosk(<?= $kiosk['machine_id'] ?>)"><i class='bx bx-edit'></i> Edit Kiosk</button>
                        <button class="empty-btn btn-delete" onclick="deleteKiosk(<?= $kiosk['machine_id'] ?>)"><i class='bx bx-trash'></i> Delete Kiosk</button>
                    </div>
                </div>

                <div class="bins-container">
                    <?php foreach ($kiosk['bins'] as $bin): 
                        // Logic for Progress Circle Colors
                        $status = (int)($bin['bin_status'] ?? 0); 
                        $color = "#6a5acd"; 
                        if ($status < 50) { $color = "#1abc3a"; } // Green
                        elseif ($status < 80) { $color = "#f39c12"; } // Orange
                        else { $color = "#e74c3c"; } // Red
                    ?>
                        <div class="bin-item">
                            <div class="bin-type"><?= htmlspecialchars($bin['bin_type']) ?></div>
                            <div class="progress-circle" style="--value: <?= $status ?>%; --color: <?= $color ?>;">
                                <span><?= $status ?>%</span>
                            </div>
                            <div class="bin-detail">Floor: <strong><?= htmlspecialchars($bin['floor_level']) ?></strong></div>
                            <div class="bin-detail" style="font-size:11px;">Updated: <?= htmlspecialchars($bin['last_updated'] ?? 'N/A') ?></div>
                            
                            <div class="bin-actions">
                                <button class="empty-btn btn-edit" onclick="editTrashBin(<?= $bin['bin_id'] ?>)"><i class='bx bx-edit'></i></button>
                                <button class="empty-btn btn-delete" onclick="deleteTrashBin(<?= $bin['bin_id'] ?>)"><i class='bx bx-trash'></i></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="bin-item add-bin-card" onclick="openAddBinModal(<?= $kiosk['machine_id'] ?>)">
                        <i class='bx bx-plus-circle'></i>
                        <p style="margin-top:12px; color:#888; font-weight:500;">Add Bin</p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
  </div>
</main>

<div id="addBinModal" class="custom-modal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); align-items:center; justify-content:center; z-index:9999;">
  <form id="addBinForm" style="background:#fff; padding:32px 28px; border-radius:16px; width:100%; max-width:400px; box-shadow:0 10px 40px rgba(0,0,0,0.2); display:flex; flex-direction:column; gap:16px;">
    <h2 style="margin:0 0 8px 0; font-size:22px; color:#333;">Add Kiosk</h2>
    <input type="text" name="machine_name" placeholder="Machine Name" required style="width:100%; padding:12px 14px; border-radius:8px; border:1px solid #ddd; outline:none;">
    <select name="location" required style="width:100%; padding:12px 14px; border-radius:8px; border:1px solid #ddd; outline:none;">
      <option value="">Select Floor Location</option>
      <option value="1st Floor">1st Floor</option>
      <option value="2nd Floor">2nd Floor</option>
      <option value="3rd Floor">3rd Floor</option>
    </select>
    <div style="display:flex; gap:12px; margin-top:8px;">
      <button type="submit" class="empty-btn" style="flex:1;">Save</button>
      <button type="button" id="cancelAddBin" style="flex:1; background:#f0f0f0; color:#555; border:none; border-radius:8px; padding:12px 0; cursor:pointer;">Cancel</button>
    </div>
  </form>
</div>

<div id="addTrashBinModal" class="custom-modal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); align-items:center; justify-content:center; z-index:9999;">
  <form id="addTrashBinForm" style="background:#fff; padding:32px 28px; border-radius:16px; width:100%; max-width:400px; box-shadow:0 10px 40px rgba(0,0,0,0.2); display:flex; flex-direction:column; gap:16px;">
    <h2 style="margin:0 0 8px 0; font-size:22px; color:#333;">Add Trash Bin</h2>
    
    <select id="addMachineSelect" name="machine_id" required style="width:100%; padding:12px 14px; border-radius:8px; border:1px solid #ddd; outline:none;">
      <option value="">Select Machine</option>
      <?php foreach($machines as $m) { echo "<option value='".htmlspecialchars($m['machine_id'])."'>".htmlspecialchars($m['machine_name'])."</option>"; } ?>
    </select>
    
    <select id="binTypeSelect" name="bin_type" required style="width:100%; padding:12px 14px; border-radius:8px; border:1px solid #ddd; outline:none;">
      <option value="">Select Type</option>
      <option value="BIODEGRADABLE">BIODEGRADABLE</option>
      <option value="NON-BIODEGRADABLE">NON-BIODEGRADABLE</option>
      <option value="RECYCLABLE">RECYCLABLE</option>
      <option value="OTHER">Other (Custom)</option>
    </select>
    <input type="text" id="customBinType" name="custom_bin_type" placeholder="Enter custom bin type" style="display:none; width:100%; padding:12px 14px; border-radius:8px; border:1px solid #ddd; outline:none;" />
    
    <div style="display:flex; gap:12px; margin-top:8px;">
      <button type="submit" class="empty-btn" style="flex:1;">Save</button>
      <button type="button" id="cancelAddTrashBin" style="flex:1; background:#f0f0f0; color:#555; border:none; border-radius:8px; padding:12px 0; cursor:pointer;">Cancel</button>
    </div>
  </form>
</div>

<?php ob_start(); ?>
<script>
// JSON Response handler
async function safeParseResponse(resp) {
  const text = await resp.text(); // Read the stream once as text
  try {
    const json = JSON.parse(text); // Try to parse that text into JSON
    return { type: 'json', data: json };
  } catch (err) {
    return { type: 'text', data: text }; // If it fails, return the raw text
  }
}

/* --- Kiosk Modals --- */
const addBinModal = document.getElementById('addBinModal');
const addBinForm = document.getElementById('addBinForm');

document.getElementById('addBinBtn').onclick = () => { addBinModal.style.display = 'flex'; }
document.getElementById('cancelAddBin').onclick = () => { addBinModal.style.display = 'none'; addBinForm.reset(); }

addBinForm.onsubmit = async function (e) {
  e.preventDefault();
  try {
    const res = await fetch("/controllers/Actions/add_kiosk.php", { method: "POST", body: new FormData(addBinForm) });
    const parsed = await safeParseResponse(res);
    if (parsed.type === 'json' && parsed.data.success) {
        alert("✅ Kiosk Added!"); location.reload(); 
    } else { alert("❌ Failed: " + (parsed.data.message || parsed.data)); }
  } catch (error) { alert("⚠️ Error: " + error); }
};

/* --- Trash Bin Modals --- */
const addTrashBinModal = document.getElementById('addTrashBinModal');
const addTrashBinForm = document.getElementById('addTrashBinForm');
const addMachineSelect = document.getElementById('addMachineSelect');
const binTypeSelect = document.getElementById('binTypeSelect');
const customBinTypeInput = document.getElementById('customBinType');

// Open modal and pre-select the machine!
function openAddBinModal(machineId) {
    addMachineSelect.value = machineId;
    addTrashBinModal.style.display = 'flex';
}

document.getElementById('cancelAddTrashBin').onclick = () => { addTrashBinModal.style.display = 'none'; addTrashBinForm.reset(); customBinTypeInput.style.display = 'none'; }

binTypeSelect.addEventListener('change', function() {
  if (this.value === 'OTHER') { customBinTypeInput.style.display = 'block'; customBinTypeInput.required = true; } 
  else { customBinTypeInput.style.display = 'none'; customBinTypeInput.required = false; }
});

addTrashBinForm.onsubmit = async function(e) {
    e.preventDefault();
    const fd = new FormData(addTrashBinForm);
    if (binTypeSelect.value === 'OTHER') fd.set('bin_type', customBinTypeInput.value.trim());
    else fd.set('bin_type', binTypeSelect.value);

    try {
      const resp = await fetch('/controllers/Actions/add_trash_bin.php', { method: 'POST', body: fd });
      const parsed = await safeParseResponse(resp);
      if (parsed.type === 'json' && parsed.data.success) {
          alert('✅ Bin Added!'); location.reload();
      } else if (typeof parsed.data === 'string' && parsed.data.trim() === 'success') {
          alert('✅ Bin Added!'); location.reload();
      } else { alert('❌ Failed: ' + (parsed.data.message || parsed.data)); }
    } catch (err) { alert('⚠️ Error: ' + err); }
};

/* --- Edit / Delete Kiosk --- */
async function editKiosk(machineId) {
  try {
    const resp = await fetch(`/controllers/Api/get_machine.php?id=${encodeURIComponent(machineId)}`);
    const parsed = await safeParseResponse(resp);
    if (parsed.type === 'json' && parsed.data.success && parsed.data.machine) {
      const m = parsed.data.machine;
      const newName = prompt('Edit Kiosk Name:', m.machine_name || '');
      if (newName === null) return;
      const newLocation = prompt('Edit Location:', m.location || '');
      if (newLocation === null) return;

      const form = new FormData(); form.append('machine_id', machineId); form.append('machine_name', newName.trim()); form.append('location', newLocation.trim());
      const updateResp = await fetch('/controllers/Actions/edit_kiosk.php', { method: 'POST', body: form });
      const parsedUp = await safeParseResponse(updateResp);
      if (parsedUp.type === 'json' && parsedUp.data.success) { alert('✅ Updated!'); location.reload(); } else { alert('❌ Update failed'); }
    }
  } catch (err) { alert('Error: ' + err); }
}

async function deleteKiosk(machineId) {
  if (!confirm('Delete this kiosk and all its bins? This is permanent.')) return;
  try {
    const form = new FormData(); form.append('machine_id', machineId);
    const resp = await fetch('/controllers/Actions/delete_kiosk.php', { method: 'POST', body: form });
    const parsed = await safeParseResponse(resp);
    if (parsed.type === 'json' && parsed.data.success) { alert('✅ Deleted!'); location.reload(); } else { alert('❌ Failed'); }
  } catch (err) { alert('Error: ' + err); }
}

/* --- Edit / Delete Bin --- */
async function editTrashBin(binId) {
  try {
    const resp = await fetch(`/controllers/Api/get_trash_bin.php?id=${encodeURIComponent(binId)}`);
    const parsed = await safeParseResponse(resp);
    if (parsed.type === 'json' && parsed.data.success && parsed.data.bin) {
      const bin = parsed.data.bin;
      const newFloor = prompt('Edit Floor Level:', bin.floor_level || '');
      if (newFloor === null) return;
      const newType = prompt('Edit Bin Type:', bin.bin_type || '');
      if (newType === null) return;

      const form = new FormData(); form.append('bin_id', binId); form.append('floor_level', newFloor.trim()); form.append('bin_type', newType.trim());
      const updateResp = await fetch('/controllers/Actions/edit_trash_bin.php', { method: 'POST', body: form });
      const parsedUp = await safeParseResponse(updateResp);
      if (parsedUp.type === 'json' && parsedUp.data.success) { alert('✅ Updated!'); location.reload(); } else { alert('❌ Update failed'); }
    }
  } catch (err) { alert('Error: ' + err); }
}

async function deleteTrashBin(binId) {
  if (!confirm('Delete this trash bin?')) return;
  try {
    const form = new FormData(); form.append('bin_id', binId);
    const resp = await fetch('/controllers/Actions/delete_trash_bin.php', { method: 'POST', body: form });
    const parsed = await safeParseResponse(resp);
    if (parsed.type === 'json' && parsed.data.success) { alert('✅ Deleted!'); location.reload(); } else { alert('❌ Failed'); }
  } catch (err) { alert('Error: ' + err); }
}
</script>
<?php 
$extra_js = ob_get_clean();
require_once __DIR__ . '/../layouts/footer.php'; 
?>