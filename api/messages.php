<?php
// api/messages.php

require '../config.php';

check_auth();
$user = get_user();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'send') {
    $phone = $_POST['phone'] ?? '';
    $message = $_POST['message'] ?? '';
    
    if (!$phone || !$message) {
        json_response(false, 'Phone and message required');
    }
    
    // Deduct credits
    $cost = 1;
    
    if ($user['credits'] < $cost) {
        json_response(false, 'Insufficient credits');
    }
    
    // Save message
    $conn->query("INSERT INTO messages (user_id, phone, message, status) 
                 VALUES ({$user['id']}, '$phone', '$message', 'sent')");
    
    // Deduct credits
    $conn->query("UPDATE users SET credits = credits - $cost WHERE id = {$user['id']}");
    
    json_response(true, 'Message sent', ['messageId' => $conn->insert_id]);
}

if ($action === 'list') {
    $result = $conn->query("SELECT * FROM messages WHERE user_id = {$user['id']} ORDER BY created_at DESC LIMIT 100");
    $messages = $result->fetch_all(MYSQLI_ASSOC);
    json_response(true, 'Messages', $messages);
}

json_response(false, 'Invalid action');
?>
