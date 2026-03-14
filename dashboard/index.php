<?php
// /var/www/html/segreduino/dashboard/index.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// 1. Tell local PHP server to serve static assets (CSS, JS, Images) normally
if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($path !== '/index.php' && is_file(__DIR__ . $path)) {
        return false; 
    }
}

// 2. Get the requested URL path
$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 3. Routing Logic looking for .php URLs
switch ($request) {
    // ==========================================
    // MAIN PAGES
    // ==========================================
    case '/':
    //case '/index.php':
    case '/dashboard.php':
        require __DIR__ . '/views/Pages/dashboard.php'; 
        break;
        
    case '/bin.php':
        require __DIR__ . '/views/Pages/bin.php';
        break;
        
    case '/history.php':
        require __DIR__ . '/views/Pages/history.php';
        break;
        
    case '/user.php':
        require __DIR__ . '/views/Pages/user.php';
        break;

    case '/schedules.php':
        require __DIR__ . '/views/Pages/schedules.php';
        break;

    case '/notifications.php':
        require __DIR__ . '/views/Pages/notifications.php';
        break;

    case '/user_status.php':
        require __DIR__ . '/views/Pages/user_status.php';
        break;

    // ==========================================
    // AUTHENTICATION PAGES
    // ==========================================
    case '/login.php':
        require __DIR__ . '/views/Auth/login.php';
        break;
        
    case '/register.php':
        require __DIR__ . '/views/Auth/register.php';
        break;

    case '/logout.php':
        require __DIR__ . '/views/Auth/logout.php';
        break;
        
    case '/reset_password.php':
        require __DIR__ . '/views/Auth/reset_password.php';
        break;

    // ==========================================
    // ADMIN PASSWORD RECOVERY PAGES
    // ==========================================
    case '/forgot_password.php':
        require __DIR__ . '/views/Admin/forgot_password.php';
        break;

    case '/verify_code.php':
        require __DIR__ . '/views/Admin/verify_code.php';
        break;

    case '/admin_reset_password.php': 
        // Note: Renamed URL slightly so it doesn't conflict with Auth/reset_password.php
        require __DIR__ . '/views/Admin/reset_password.php';
        break;

    // ==========================================
    // MISC FILES
    // ==========================================
    case '/verification_email.php':
        require __DIR__ . '/verification_email.php';
        break;

    // ==========================================
    // 404 FALLBACK
    // ==========================================
    default:
        http_response_code(404);
        echo "<h1>404 - Page Not Found</h1>";
        echo "<p>The requested URL '<strong>" . htmlspecialchars($request) . "</strong>' was not found.</p>";
        break;
}