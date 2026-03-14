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