<?php
///// index.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session globally so all routed pages can access $_SESSION
session_start();

// Handle static files for the built-in PHP server (like /assets/css/style.css)
if (preg_match('/\.(?:css|js|png|jpg|jpeg|gif|ico|jfif)$/', $_SERVER["REQUEST_URI"])) {
    return false; 
}

$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Automatically route the root domain AND /index.php to the dashboard
if ($request === '/' || $request === '/index.php') {
    $request = '/dashboard.php';
}

// =========================================================
// Allow direct access to the controllers folder inside /src
// =========================================================
if (strpos($request, '/controllers/') === 0) {
    // We prepend /src because controllers now live in segreduino/src/controllers/
    $file_path = __DIR__ . '/src' . $request;
    if (file_exists($file_path)) {
        require $file_path;
        exit; // Stop the router here so it doesn't hit the 404 below
    }
}

// =========================================================
// Allow direct access to the Cron Engine scripts folder
// =========================================================
if (strpos($request, '/scripts/') === 0) {
    $file_path = __DIR__ . $request;
    if (file_exists($file_path)) {
        require $file_path;
        exit; 
    }
}

switch ($request) {
    // MAIN PAGES
    case '/dashboard.php':
        require __DIR__ . '/src/views/pages/dashboard.php'; 
        break;
    case '/bin.php':
        require __DIR__ . '/src/views/pages/bin.php';
        break;
    case '/history.php':
        require __DIR__ . '/src/views/pages/history.php';
        break;
    case '/user.php':
        require __DIR__ . '/src/views/pages/user.php';
        break;

    // EXTRA PAGES
    case '/schedules.php':
        require __DIR__ . '/src/views/pages/schedules.php';
        break;
    case '/tasks.php':
        require __DIR__ . '/src/views/pages/tasks.php';
        break;
    case '/profile.php':
        require __DIR__ . '/src/views/pages/profile.php';
        break;
    case '/settings.php':
        require __DIR__ . '/src/views/pages/settings.php';
        break;

    // AUTH PAGES
    case '/login.php':
        require __DIR__ . '/src/views/auth/login.php';
        break;
    case '/register.php':
        require __DIR__ . '/src/views/auth/register.php';
        break;
    case '/forgot-password.php':
        require __DIR__ . '/src/views/auth/forgot_password.php';
        break;
    case '/verify-code.php':
        require __DIR__ . '/src/views/auth/verify_code.php';
        break;
    case '/reset-password.php':
        require __DIR__ . '/src/views/auth/admin_reset_password.php';
        break;
    case '/logout.php':
        require __DIR__ . '/src/views/auth/logout.php';
        break;
    case '/verification_email.php': // for mobile app password reset
        require __DIR__ . '/src/verification_email.php';
        break;

    // TEST PAGES FOR ESP32
    case '/test_endpoint.php':
        require __DIR__ . '/test_endpoint.php';
        break;
    case '/test_view.php':
        require __DIR__ . '/test_view.php';
        break;

    case '/generate_routine_tasks.php':
        require __DIR__ . '/generate_routine_tasks.php';
        break;
    
    default:
        http_response_code(404);
        echo "<h1 style='text-align:center; margin-top:50px; font-family:sans-serif;'>404 - Page Not Found</h1>";
        echo "<p style='text-align:center;'><a href='/dashboard.php'>Return to Dashboard</a></p>";
        break;
}