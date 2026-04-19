<script>
document.addEventListener('DOMContentLoaded', function() {
  const allBins = <?= json_encode($bins ?? []) ?>;

  // --- SCHEDULE MODAL LOGIC ---
  const scheduleModal = document.getElementById('scheduleModal');
  const openScheduleBtn = document.getElementById('openScheduleModalBtn');
  const closeScheduleBtn = document.getElementById('closeScheduleModalBtn');
  const scheduleMachineSelect = document.getElementById('schedule_machine_id');
  const scheduleBinSelect = document.getElementById('schedule_bin_id');

  if (scheduleModal && openScheduleBtn) {
      openScheduleBtn.onclick = () => scheduleModal.style.display = 'flex';
      closeScheduleBtn.onclick = () => { 
          scheduleModal.style.display = 'none'; 
          document.getElementById('addScheduleForm').reset(); 
          scheduleBinSelect.innerHTML = '<option value="">-- Select Bin --</option>';
      };

      scheduleMachineSelect.addEventListener('change', function() {
          const selectedMachineId = this.value;
          scheduleBinSelect.innerHTML = '<option value="">-- Select Bin --</option>';
          if (selectedMachineId) {
              const filteredBins = allBins.filter(bin => bin.machine_id == selectedMachineId);
              if(filteredBins.length === 0) {
                  scheduleBinSelect.innerHTML = '<option value="">No Bins Available</option>';
              } else {
                  filteredBins.forEach(bin => {
                      const option = document.createElement('option');
                      option.value = bin.bin_id;
                      option.textContent = bin.bin_type; 
                      scheduleBinSelect.appendChild(option);
                  });
              }
          }
      });
  }

  // --- TASK MODAL LOGIC ---
  const taskModal = document.getElementById('taskModal');
  const openTaskBtn = document.getElementById('openTaskModalBtn');
  const closeTaskBtn = document.getElementById('closeTaskModalBtn');
  const taskMachineSelect = document.getElementById('task_machine_id');
  const taskBinSelect = document.getElementById('task_bin_id');

  if (taskModal && openTaskBtn) {
      openTaskBtn.onclick = () => taskModal.style.display = 'flex';
      closeTaskBtn.onclick = () => { 
          taskModal.style.display = 'none'; 
          document.getElementById('addTasksForm').reset(); 
          taskBinSelect.innerHTML = '<option value="">-- Select Bin --</option>';
      };

      taskMachineSelect.addEventListener('change', function() {
          const selectedMachineId = this.value;
          taskBinSelect.innerHTML = '<option value="">-- Select Bin --</option>';
          if (selectedMachineId) {
              const filteredBins = allBins.filter(bin => bin.machine_id == selectedMachineId);
              if(filteredBins.length === 0) {
                  taskBinSelect.innerHTML = '<option value="">No Bins Available</option>';
              } else {
                  filteredBins.forEach(bin => {
                      const option = document.createElement('option');
                      option.value = bin.bin_id;
                      option.textContent = bin.bin_type; 
                      taskBinSelect.appendChild(option);
                  });
              }
          }
      });
  }
});
</script>