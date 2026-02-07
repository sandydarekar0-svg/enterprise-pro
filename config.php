<?php
// config.php - Main Configuration File

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Configuration
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'whatsapp_sender';

// Database Connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
}

$conn->set_charset("utf8mb4");

// Session Start
session_start();

// Global Constants
define('WHATSAPP_API_TOKEN', getenv('WHATSAPP_API_TOKEN') ?: '');
define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: '');
define('PAYPAL_CLIENT_ID', getenv('PAYPAL_CLIENT_ID') ?: '');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost');
define('NODEJS_URL', getenv('NODEJS_URL') ?: 'http://localhost:3001');

// Helper Functions

function json_response($success, $message = '', $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function check_auth() {
    if (!isset($_SESSION['user_id'])) {
        json_response(false, 'Not authenticated');
    }
}

function get_user() {
    global $conn;
    $user_id = $_SESSION['user_id'];
    $result = $conn->query("SELECT * FROM users WHERE id = $user_id");
    return $result->fetch_assoc();
}

function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

function generate_api_key() {
    return 'sk_' . bin2hex(random_bytes(32));
}

function send_whatsapp_message($phone, $message) {
    $ch = curl_init();
    
    $url = 'https://graph.instagram.com/v18.0/YOUR_PHONE_ID/messages';
    
    $data = [
        'messaging_product' => 'whatsapp',
        'to' => $phone,
        'type' => 'text',
        'text' => ['body' => $message]
    ];
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . WHATSAPP_API_TOKEN
        ]
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

?>
