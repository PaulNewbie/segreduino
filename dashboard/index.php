<?php
// index.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (preg_match('/\.(?:css|js|png|jpg|jpeg|gif|ico|jfif)$/', $_SERVER["REQUEST_URI"])) {
    return false; 
}

$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Automatically route the root domain to the dashboard
if ($request === '/') {
    $request = '/dashboard.php';
}

switch ($request) {
    // MAIN PAGES
    case '/dashboard.php':
        require __DIR__ . '/views/pages/dashboard.php'; 
        break;
    case '/bin.php':
        require __DIR__ . '/views/pages/bin.php';
        break;
    case '/history.php':
        require __DIR__ . '/views/pages/history.php';
        break;
    case '/user.php':
        require __DIR__ . '/views/pages/user.php';
        break;

    // AUTH PAGES
    case '/login.php':
        require __DIR__ . '/views/auth/login.php';
        break;
    case '/register.php':
        require __DIR__ . '/views/auth/register.php';
        break;
    case '/forgot-password.php':
        require __DIR__ . '/views/auth/forgot_password.php';
        break;
    case '/logout.php':
        require __DIR__ . '/views/auth/logout.php';
        break;

    default:
        http_response_code(404);
        echo "<h1 style='text-align:center; margin-top:50px; font-family:sans-serif;'>404 - Page Not Found</h1>";
        echo "<p style='text-align:center;'><a href='/dashboard.php'>Return to Dashboard</a></p>";
        break;
}