<?php
// api/admin.php

require '../config.php';

check_auth();
$user = get_user();

if ($user['role'] !== 'admin') {
    json_response(false, 'Admin only');
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'users') {
    $result = $conn->query("SELECT id, username, email, role, credits, status, created_at FROM users");
    $users = $result->fetch_all(MYSQLI_ASSOC);
    json_response(true, 'Users', $users);
}

if ($action === 'stats') {
    $total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
    $total_messages = $conn->query("SELECT COUNT(*) as count FROM messages")->fetch_assoc()['count'];
    $total_campaigns = $conn->query("SELECT COUNT(*) as count FROM campaigns")->fetch_assoc()['count'];
    $total_revenue = $conn->query("SELECT SUM(amount) as total FROM transactions WHERE status = 'completed'")->fetch_assoc()['total'];
    
    json_response(true, 'Stats', [
        'total_users' => $total_users,
        'total_messages' => $total_messages,
        'total_campaigns' => $total_campaigns,
        'total_revenue' => $total_revenue ?? 0
    ]);
}

if ($action === 'add_credits') {
    $user_id = $_POST['user_id'] ?? 0;
    $credits = $_POST['credits'] ?? 0;
    
    $conn->query("UPDATE users SET credits = credits + $credits WHERE id = $user_id");
    $conn->query("INSERT INTO credits (user_id, amount, type) VALUES ($user_id, $credits, 'purchase')");
    
    json_response(true, 'Credits added');
}

json_response(false, 'Invalid action');
?>
