<?php
// api/whatsapp_qr.php

require '../config.php';

check_auth();
$user = get_user();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$nodejs_url = getenv('NODEJS_URL') ?: 'http://localhost:3001';

if ($action === 'login') {
    try {
        $response = file_get_contents("$nodejs_url/api/whatsapp/login", false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode(['userId' => $user['id']])
            ]
        ]));
        
        $result = json_decode($response, true);
        
        if (isset($result['qrCode'])) {
            $conn->query("UPDATE users SET whatsapp_status = 'waiting_scan' WHERE id = {$user['id']}");
        }
        
        json_response(true, 'QR Code generated', $result);
    } catch (Exception $e) {
        json_response(false, 'Error: ' . $e->getMessage());
    }
}

if ($action === 'status') {
    try {
        $response = file_get_contents("$nodejs_url/api/whatsapp/status/{$user['id']}");
        $result = json_decode($response, true);
        
        if (isset($result['status']) && $result['status'] === 'ready') {
            $conn->query("UPDATE users SET whatsapp_status = 'connected', whatsapp_connected_at = NOW() WHERE id = {$user['id']}");
        }
        
        json_response(true, 'Status', $result);
    } catch (Exception $e) {
        json_response(false, 'Error: ' . $e->getMessage());
    }
}

if ($action === 'send') {
    $phone = $_POST['phone'] ?? '';
    $message = $_POST['message'] ?? '';
    
    if (!$phone || !$message) {
        json_response(false, 'Phone and message required');
    }
    
    try {
        $response = file_get_contents("$nodejs_url/api/whatsapp/send-message", false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode([
                    'userId' => $user['id'],
                    'phone' => $phone,
                    'message' => $message
                ])
            ]
        ]));
        
        $result = json_decode($response, true);
        
        if ($result['success']) {
            $conn->query("INSERT INTO messages (user_id, phone, message, status, external_id, type) 
                         VALUES ({$user['id']}, '$phone', '$message', 'sent', '{$result['messageId']}', 'whatsapp')");
            
            $conn->query("UPDATE users SET credits = credits - 1 WHERE id = {$user['id']}");
        }
        
        json_response($result['success'], $result['message'], $result);
    } catch (Exception $e) {
        json_response(false, 'Error: ' . $e->getMessage());
    }
}

if ($action === 'contacts') {
    try {
        $response = file_get_contents("$nodejs_url/api/whatsapp/contacts/{$user['id']}");
        $result = json_decode($response, true);
        json_response(true, 'Contacts', $result['contacts'] ?? []);
    } catch (Exception $e) {
        json_response(false, 'Error: ' . $e->getMessage());
    }
}

if ($action === 'logout') {
    try {
        file_get_contents("$nodejs_url/api/whatsapp/logout/{$user['id']}", false, stream_context_create([
            'http' => ['method' => 'POST']
        ]));
        
        $conn->query("UPDATE users SET whatsapp_status = 'disconnected' WHERE id = {$user['id']}");
        json_response(true, 'Logged out');
    } catch (Exception $e) {
        json_response(false, 'Error: ' . $e->getMessage());
    }
}

json_response(false, 'Invalid action');
?>
