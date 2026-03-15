<section id="sidebar">
  <a href="/dashboard.php" class="brand">
    <i class="bx bxs-chip"></i>
    <span class="text">SegreDuino Admin</span>
  </a>
  <ul class="side-menu top">
    <li class="<?= (isset($current_page) && $current_page === 'dashboard') ? 'active' : ''; ?>">
      <a href="/dashboard.php">
        <i class="bx bxs-dashboard"></i>
        <span class="text">Dashboard</span>
      </a>
    </li>
    <li class="<?= (isset($current_page) && $current_page === 'user') ? 'active' : ''; ?>">
      <a href="/user.php">
        <i class="bx bxs-user"></i>
        <span class="text">Users</span>
      </a>
    </li>
    <li class="<?= (isset($current_page) && $current_page === 'bin') ? 'active' : ''; ?>">
      <a href="/bin.php">
        <i class="bx bxs-shopping-bag-alt"></i>
        <span class="text">Bin Monitoring</span>
      </a>
    </li>
    <li class="<?= (isset($current_page) && $current_page === 'history') ? 'active' : ''; ?>">
      <a href="/history.php">
        <i class="bx bxs-message-dots"></i>
        <span class="text">History & Reports</span>
      </a>
    </li>
    <li>
      <a href="/logout.php" class="logout">
        <i class="bx bxs-log-out-circle"></i>
        <span class="text">Logout</span>
      </a>
    </li>
  </ul>
</section>

<style>
/* ==========================================
   SIDEBAR STYLES
========================================== */
#sidebar {
	position: fixed;
	top: 0;
	left: 0;
	width: 280px;
	height: 100%;
	background: var(--light);
	z-index: 2000;
	font-family: var(--lato);
	transition: .3s ease;
	overflow-x: hidden;
	scrollbar-width: none;
}
#sidebar::-webkit-scrollbar {
	display: none;
}
#sidebar.hide {
	width: 60px;
}
#sidebar .brand {
	font-size: 20px; /* Reduced from 24px for a cleaner fit */
	font-weight: 700;
	height: 56px;
	display: flex;
	align-items: center;
	color: var(--blue);
	position: sticky;
	top: 0;
	left: 0;
	background: var(--light);
	z-index: 500;
    /* Removed the awkward 20px bottom padding to tighten the layout */
    white-space: nowrap; /* Prevents long names from wrapping to a second line */
    overflow: hidden;
}
#sidebar .brand .bx {
	min-width: 60px;
	display: flex;
	justify-content: center;
}
#sidebar .brand .text {
    text-overflow: ellipsis; /* Adds '...' if the text ever gets too long */
}
#sidebar .side-menu {
	width: 100%;
	margin-top: 24px; /* Reduced from 48px to bring the menu up slightly */
}
#sidebar .side-menu li {
	height: 48px;
	background: transparent;
	margin-left: 6px;
	border-radius: 48px 0 0 48px;
	padding: 4px;
}
#sidebar .side-menu li.active {
	background: var(--grey);
	position: relative;
}
#sidebar .side-menu li.active::before {
	content: '';
	position: absolute;
	width: 40px;
	height: 40px;
	border-radius: 50%;
	top: -40px;
	right: 0;
	box-shadow: 20px 20px 0 var(--grey);
	z-index: -1;
}
#sidebar .side-menu li.active::after {
	content: '';
	position: absolute;
	width: 40px;
	height: 40px;
	border-radius: 50%;
	bottom: -40px;
	right: 0;
	box-shadow: 20px -20px 0 var(--grey);
	z-index: -1;
}
#sidebar .side-menu li a {
	width: 100%;
	height: 100%;
	background: var(--light);
	display: flex;
	align-items: center;
	border-radius: 48px;
	font-size: 16px;
	color: var(--dark);
	white-space: nowrap;
	overflow-x: hidden;
}
#sidebar .side-menu.top li.active a {
	color: var(--blue);
}
#sidebar.hide .side-menu li a {
	width: calc(48px - (4px * 2));
	transition: width .3s ease;
}
#sidebar .side-menu li a.logout {
	color: var(--red);
}
#sidebar .side-menu.top li a:hover {
	color: var(--blue);
}
#sidebar .side-menu li a .bx {
	min-width: calc(60px - ((4px + 6px) * 2));
	display: flex;
	justify-content: center;
}

/* Sidebar Responsive Rules */
@media screen and (max-width: 768px) {
	#sidebar {
		width: 200px;
	}
}
@media screen and (max-width: 576px) {
	#sidebar:not(.hide) {
		z-index: 2000;
	}
	#sidebar:not(.hide) ~ #content {
		width: 100%;
		left: 0;
	}
}
</style>

<script>
/* ==========================================
   SIDEBAR JAVASCRIPT
========================================== */
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Sidebar Active Link Toggle (Client-side fallback)
    const allSideMenu = document.querySelectorAll('#sidebar .side-menu.top li a');
    allSideMenu.forEach(item => {
        const li = item.parentElement;
        item.addEventListener('click', function () {
            allSideMenu.forEach(i => {
                i.parentElement.classList.remove('active');
            })
            li.classList.add('active');
        })
    });

    // 2. Sidebar Burger Menu Toggle
    const menuBar = document.querySelector('#content nav .bx.bx-menu');
    const sidebar = document.getElementById('sidebar');

    if(menuBar && sidebar) {
        menuBar.addEventListener('click', function () {
            sidebar.classList.toggle('hide');
        });

        // Auto-hide on small screens
        if(window.innerWidth < 768) {
            sidebar.classList.add('hide');
        }
    }
});
</script>