<?php
// api/payments.php

require '../config.php';

check_auth();
$user = get_user();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'buy_credits') {
    $amount = $_POST['amount'] ?? 0;
    $credits = $_POST['credits'] ?? 0;
    
    $query = "INSERT INTO transactions (user_id, amount, credits, provider, status) 
             VALUES ({$user['id']}, $amount, $credits, 'stripe', 'pending')";
    
    if ($conn->query($query)) {
        $tx_id = $conn->insert_id;
        json_response(true, 'Transaction created', ['transaction_id' => $tx_id]);
    } else {
        json_response(false, 'Error: ' . $conn->error);
    }
}

if ($action === 'balance') {
    json_response(true, 'Balance', ['balance' => $user['credits']]);
}

json_response(false, 'Invalid action');
?>
