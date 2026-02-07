<?php
// api/campaigns.php

require '../config.php';

check_auth();
$user = get_user();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'create') {
    $name = $_POST['campaign_name'] ?? '';
    $template_id = $_POST['template_id'] ?? 0;
    
    $query = "INSERT INTO campaigns (user_id, campaign_name, template_id, status) 
             VALUES ({$user['id']}, '$name', '$template_id', 'draft')";
    
    if ($conn->query($query)) {
        $campaign_id = $conn->insert_id;
        json_response(true, 'Campaign created', ['campaign_id' => $campaign_id]);
    } else {
        json_response(false, 'Error: ' . $conn->error);
    }
}

if ($action === 'list') {
    $result = $conn->query("SELECT * FROM campaigns WHERE user_id = {$user['id']} ORDER BY created_at DESC");
    $campaigns = $result->fetch_all(MYSQLI_ASSOC);
    json_response(true, 'Campaigns', $campaigns);
}

json_response(false, 'Invalid action');
?>
