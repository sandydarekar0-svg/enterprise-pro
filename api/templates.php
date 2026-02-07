<?php
// api/templates.php

require '../config.php';

check_auth();
$user = get_user();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'create') {
    $name = $_POST['template_name'] ?? '';
    $content = $_POST['template_content'] ?? '';
    
    $query = "INSERT INTO templates (user_id, template_name, template_content) 
             VALUES ({$user['id']}, '$name', '$content')";
    
    if ($conn->query($query)) {
        json_response(true, 'Template created');
    } else {
        json_response(false, 'Error: ' . $conn->error);
    }
}

if ($action === 'list') {
    $result = $conn->query("SELECT * FROM templates WHERE user_id = {$user['id']}");
    $templates = $result->fetch_all(MYSQLI_ASSOC);
    json_response(true, 'Templates', $templates);
}

json_response(false, 'Invalid action');
?>
