// assets/js/table-utils.js

// --- 1. GENERIC SORTING ---
const sortDirections = {};
function sortTable(tableId, colIndex, isDate = false) {
    const table = document.getElementById(tableId);
    if (!table) return;
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    sortDirections[tableId] = sortDirections[tableId] || [];
    sortDirections[tableId][colIndex] = !sortDirections[tableId][colIndex];
    let isAsc = sortDirections[tableId][colIndex];

    // Reset icons
    table.querySelectorAll('th i').forEach(icon => icon.className = 'bx bx-sort sort-icon');
    let currentIcon = table.querySelectorAll('th')[colIndex].querySelector('i');
    if (currentIcon) {
        currentIcon.className = isAsc ? 'bx bx-sort-up sort-icon' : 'bx bx-sort-down sort-icon';
    }

    rows.sort((a, b) => {
        let cellA = a.cells[colIndex];
        let cellB = b.cells[colIndex];
        if (!cellA || !cellB) return 0;

        let valA, valB;
        if (isDate) {
            valA = parseInt(cellA.getAttribute('data-time')) || 0;
            valB = parseInt(cellB.getAttribute('data-time')) || 0;
        } else {
            valA = cellA.innerText.trim().toLowerCase();
            valB = cellB.innerText.trim().toLowerCase();
        }

        if (valA < valB) return isAsc ? -1 : 1;
        if (valA > valB) return isAsc ? 1 : -1;
        return 0;
    });
    rows.forEach(row => tbody.appendChild(row));
}

// --- 2. GENERIC FILTERING ---
// config format: { tableId, searchId, statusId, statusCol, startId, endId, timeCol }
function filterGenericTable(config) {
    const table = document.getElementById(config.tableId);
    if (!table) return;

    const searchVal = config.searchId && document.getElementById(config.searchId) ? document.getElementById(config.searchId).value.toLowerCase() : '';
    const statusVal = config.statusId && document.getElementById(config.statusId) ? document.getElementById(config.statusId).value.toLowerCase() : 'all';
    
    const startElem = config.startId ? document.getElementById(config.startId) : null;
    const endElem = config.endId ? document.getElementById(config.endId) : null;
    const start = startElem && startElem.value ? new Date(startElem.value).setHours(0,0,0,0) / 1000 : 0;
    const end = endElem && endElem.value ? new Date(endElem.value).setHours(23,59,59,999) / 1000 : Infinity;

    const rows = table.querySelectorAll('tbody tr');

    rows.forEach(row => {
        let textMatch = row.innerText.toLowerCase().includes(searchVal);
        
        let statusMatch = true;
        if (statusVal !== 'all' && config.statusCol !== undefined) {
            let cellStatus = row.cells[config.statusCol] ? row.cells[config.statusCol].innerText.trim().toLowerCase() : '';
            statusMatch = (cellStatus === statusVal);
        }

        let dateMatch = true;
        if (config.timeCol !== undefined) {
            let cellTime = row.cells[config.timeCol] ? parseInt(row.cells[config.timeCol].getAttribute('data-time')) || 0 : 0;
            dateMatch = (cellTime >= start && cellTime <= end);
        }

        row.style.display = (textMatch && statusMatch && dateMatch) ? '' : 'none';
    });
}