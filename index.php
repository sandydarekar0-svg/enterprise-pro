<?php
// index.php - Main Router

require 'config.php';

$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Remove base path
$request = str_replace('/whatsapp-bulk-sender', '', $request);
$request = str_replace('/index.php', '', $request);

// API Routes
if (strpos($request, '/api/') === 0) {
    $endpoint = str_replace('/api/', '', $request);
    $endpoint = explode('?', $endpoint)[0];
    
    if (file_exists("api/$endpoint.php")) {
        require "api/$endpoint.php";
    } else {
        json_response(false, 'Endpoint not found');
    }
} else {
    // Web Routes
    if ($request === '/' || $request === '') {
        if (isset($_SESSION['user_id'])) {
            $user = get_user();
            if ($user['role'] === 'admin') {
                header('Location: /admin/dashboard.html');
            } else {
                header('Location: /user/dashboard.html');
            }
        } else {
            header('Location: /admin/index.php');
        }
    } else if ($request === '/logout') {
        session_destroy();
        header('Location: /admin/index.php');
    } else if (file_exists("." . $request)) {
        return false;
    } else {
        header('HTTP/1.0 404 Not Found');
        echo '404 - Page not found';
    }
}
?>
