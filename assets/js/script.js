// Top Nav Search Toggle
const searchButton = document.querySelector('#content nav form .form-input button');
const searchButtonIcon = document.querySelector('#content nav form .form-input button .bx');
const searchForm = document.querySelector('#content nav form');

if(searchButton && searchForm) {
    searchButton.addEventListener('click', function (e) {
        if(window.innerWidth < 576) {
            e.preventDefault();
            searchForm.classList.toggle('show');
            if(searchForm.classList.contains('show')) {
                searchButtonIcon.classList.replace('bx-search', 'bx-x');
            } else {
                searchButtonIcon.classList.replace('bx-x', 'bx-search');
            }
        }
    })
}

// Global Window Resize rules for Nav Search
window.addEventListener('resize', function () {
	if(this.innerWidth > 576 && searchButtonIcon && searchForm) {
		searchButtonIcon.classList.replace('bx-x', 'bx-search');
		searchForm.classList.remove('show');
	}
})

// Dark Mode Switch (if implemented in future nav)
const switchMode = document.getElementById('switch-mode');
if(switchMode) {
    switchMode.addEventListener('change', function () {
        if(this.checked) document.body.classList.add('dark');
        else document.body.classList.remove('dark');
    })
}

// Global Notification Handling
function checkNotifications() {
    fetch('/controllers/Api/get_notifications.php')
        .then(response => response.json())
        .then(data => {
            const notifCount = document.querySelector('.notification .num');
            if(notifCount) {
                const notifications = data.notifications || [];
                notifCount.textContent = notifications.length;
                notifCount.style.display = notifications.length > 0 ? 'flex' : 'none';
            }
        }).catch(err => console.log(err));
}
setInterval(checkNotifications, 30000);

const notifBell = document.querySelector('.notification');
if(notifBell) {
    notifBell.addEventListener('click', function(e) {
        e.preventDefault();
        const dropdown = this.querySelector('.notification-dropdown');
        if (dropdown.style.display === 'block') {
            dropdown.style.display = 'none';
        } else {
            fetch('/controllers/Api/get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    const notifications = data.notifications || [];
                    dropdown.innerHTML = notifications.map(notif => `
                        <div class="notification-item ${notif.status}">
                            <div class="message">${notif.message}</div>
                            <div class="time">${notif.created_at}</div>
                        </div>
                    `).join('') || '<div class="notification-item">No new notifications</div>';
                    dropdown.style.display = 'block';
                });
        }
    });
}