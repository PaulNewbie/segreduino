<?php
// /var/www/html/segreduino/dashboard/index.php

// 1. Tell local PHP server to serve static assets (CSS, JS, Images) normally
if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file(__DIR__ . $path)) {
        return false; 
    }
}

// 2. Get the requested URL path
$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 3. Routing Logic looking for .php URLs
switch ($request) {
    case '/':
    case '/index.php':
    case '/dashboard.php':
        // Note: Change 'index.php' to 'dashboard.php' below if you renamed the file!
        require __DIR__ . '/views/Pages/index.php'; 
        break;
        
    case '/login.php':
        require __DIR__ . '/views/Auth/login.php';
        break;
        
    case '/register.php':
        require __DIR__ . '/views/Auth/register.php';
        break;
        
    case '/history.php':
        require __DIR__ . '/views/Pages/history.php';
        break;
        
    case '/bin.php':
    case '/bins.php':
        require __DIR__ . '/views/Pages/bin.php';
        break;

    // Add your other pages here following the same pattern:
    // case '/schedules.php': 
    //     require __DIR__ . '/views/Pages/schedules.php'; break;

    default:
        http_response_code(404);
        echo "<h1>404 - Page Not Found</h1>";
        echo "<p>The requested URL '$request' was not found.</p>";
        break;
}