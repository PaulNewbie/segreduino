const allSideMenu = document.querySelectorAll('#sidebar .side-menu.top li a');

allSideMenu.forEach(item=> {
	const li = item.parentElement;

	item.addEventListener('click', function () {
		allSideMenu.forEach(i=> {
			i.parentElement.classList.remove('active');
		})
		li.classList.add('active');
	})
});




// TOGGLE SIDEBAR
const menuBar = document.querySelector('#content nav .bx.bx-menu');
const sidebar = document.getElementById('sidebar');

menuBar.addEventListener('click', function () {
	sidebar.classList.toggle('hide');
})







const searchButton = document.querySelector('#content nav form .form-input button');
const searchButtonIcon = document.querySelector('#content nav form .form-input button .bx');
const searchForm = document.querySelector('#content nav form');

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





if(window.innerWidth < 768) {
	sidebar.classList.add('hide');
} else if(window.innerWidth > 576) {
	searchButtonIcon.classList.replace('bx-x', 'bx-search');
	searchForm.classList.remove('show');
}


window.addEventListener('resize', function () {
	if(this.innerWidth > 576) {
		searchButtonIcon.classList.replace('bx-x', 'bx-search');
		searchForm.classList.remove('show');
	}
})



const switchMode = document.getElementById('switch-mode');

switchMode.addEventListener('change', function () {
	if(this.checked) {
		document.body.classList.add('dark');
	} else {
		document.body.classList.remove('dark');
	}
})


// Notification handling
function checkNotifications() {
    fetch('get_notifications.php')
        .then(response => response.json())
        .then(data => {
            const notifCount = document.querySelector('.notification .num');
            const notifications = data.notifications || [];
            
            // Update notification count
            notifCount.textContent = notifications.length;
            
            // Show/hide notification count
            if (notifications.length > 0) {
                notifCount.style.display = 'block';
            } else {
                notifCount.style.display = 'none';
            }
        });
}

// Check for new notifications every 30 seconds
setInterval(checkNotifications, 30000);

document.querySelector('.notification').addEventListener('click', function(e) {
    e.preventDefault();
    const dropdown = this.querySelector('.notification-dropdown');
    
    // Toggle dropdown
    if (dropdown.style.display === 'block') {
        dropdown.style.display = 'none';
    } else {
        // Fetch and show notifications
        fetch('get_notifications.php')
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
