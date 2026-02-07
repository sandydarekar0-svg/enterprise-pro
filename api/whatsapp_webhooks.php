<?php
// api/whatsapp_webhooks.php

require '../config.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid input']));
}

$user_id = $input['userId'] ?? 0;
$message_id = $input['messageId'] ?? '';
$type = $input['type'] ?? '';
$body = $input['body'] ?? '';
$from = $input['from'] ?? '';

$query = "INSERT INTO messages (user_id, phone, message, status, external_id, type) 
         VALUES ($user_id, '$from', '$body', '$type', '$message_id', 'whatsapp')";

if ($conn->query($query)) {
    http_response_code(200);
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $conn->error]);
}
?>
