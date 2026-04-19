<div id="scheduleModal" class="custom-modal">
  <form id="addScheduleForm" method="post" action="/controllers/Actions/add_schedule.php" class="modal-form" style="padding: 24px; border-radius: 12px; width: 100%; max-width: 420px; gap: 16px; display: flex; flex-direction: column;">
    <h2 style="margin: 0 0 10px 0; font-size: 22px; color: #333;">Add Schedule</h2>
    
    <div>
        <label style="display: block; font-size: 13px; color: #555; margin-bottom: 6px; font-weight: 600;">Assign Staff <span style="color:red;">*</span></label>
        <select name="user_id" required style="width: 100%; padding: 12px 14px; border-radius: 8px; border: 1px solid #ddd; outline: none; font-size: 14px; background: #fafafa; cursor: pointer;">
          <option value="">-- Select Staff Member --</option>
          <?php foreach($users as $user) { echo '<option value="' . htmlspecialchars($user["user_id"]) . '">' . htmlspecialchars($user["full_name"]) . '</option>'; } ?>
        </select>
    </div>
    
    <div style="display:flex; gap:12px;">
      <div style="flex:1;">
        <label style="display: block; font-size: 13px; color: #555; margin-bottom: 6px; font-weight: 600;">Machine / Kiosk <span style="color:red;">*</span></label>
        <select id="schedule_machine_id" name="machine_id" required style="width: 100%; padding: 12px 14px; border-radius: 8px; border: 1px solid #ddd; outline: none; font-size: 14px; background: #fafafa; cursor: pointer;">
            <option value="">-- Select Machine --</option>
            <?php foreach($machines as $machine): ?>
                <option value="<?= htmlspecialchars($machine['machine_id']) ?>"><?= htmlspecialchars($machine['machine_name']) ?></option>
            <?php endforeach; ?>
        </select>
      </div>

      <div style="flex:1;">
        <label style="display: block; font-size: 13px; color: #555; margin-bottom: 6px; font-weight: 600;">Trash Bin <span style="color:red;">*</span></label>
        <select id="schedule_bin_id" name="bin_id" required style="width: 100%; padding: 12px 14px; border-radius: 8px; border: 1px solid #ddd; outline: none; font-size: 14px; background: #fafafa; cursor: pointer;">
            <option value="">-- Select Bin --</option>
        </select>
      </div>
    </div>

    <div>
        <label style="display: block; font-size: 13px; color: #555; margin-bottom: 6px; font-weight: 600;">Floor Level <span style="color:red;">*</span></label>
        <select name="floor_level" required style="width: 100%; padding: 12px 14px; border-radius: 8px; border: 1px solid #ddd; outline: none; font-size: 14px; background: #fafafa; cursor: pointer;">
          <option value="">-- Select Floor Level --</option>
          <option value="1st">1st Floor</option>
          <option value="2nd">2nd Floor</option>
          <option value="3rd">3rd Floor</option>
        </select>    
    </div>

    <div>
        <label style="display: block; font-size: 13px; color: #555; margin-bottom: 6px; font-weight: 600;">Task Description <span style="color:red;">*</span></label>
        <input type="text" list="commonTasks" name="task_description" placeholder="Select from list or type a custom task..." required style="width: 100%; padding: 12px 14px; border-radius: 8px; border: 1px solid #ddd; outline: none; font-size: 14px; background: #fafafa;">
        <datalist id="commonTasks">
            <option value="Empty All Bins">
            <option value="Perform Routine Maintenance">
            <option value="Clean Kiosk Area">
            <option value="Inspect Sensors">
        </datalist>
        <small style="color: #888; font-size: 12px; margin-top: 6px; display: block;">
            <i class='bx bx-info-circle'></i> Double-click to see common tasks.
        </small>
    </div>

    <div>
        <label style="display: block; font-size: 13px; color: #555; margin-bottom: 6px; font-weight: 600;">Schedule Date <span style="color:red;">*</span></label>
        <input type="date" name="schedule_date" required style="width: 100%; padding: 12px 14px; border-radius: 8px; border: 1px solid #ddd; outline: none; font-size: 14px; background: #fafafa; cursor: pointer;">
    </div>

    <div class="modal-actions" style="display: flex; gap: 12px; margin-top: 10px;">
      <button type="submit" class="btn-primary" style="flex: 1; justify-content: center; padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer;">Save Schedule</button>
      <button type="button" class="cancel-btn" id="closeScheduleModalBtn" style="flex: 1; justify-content: center; padding: 12px; border-radius: 8px; font-weight: 600; background: #f1f1f1; color: #333; border: none; cursor: pointer;">Cancel</button>
    </div>
  </form>
</div>