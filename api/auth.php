<?php
// api/auth.php

require '../config.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = $conn->query("SELECT * FROM users WHERE (username = '$username' OR email = '$username') LIMIT 1");
    $user = $result->fetch_assoc();
    
    if ($user && verify_password($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        json_response(true, 'Login successful', ['user_id' => $user['id'], 'role' => $user['role']]);
    } else {
        json_response(false, 'Invalid credentials');
    }
}

if ($action === 'logout') {
    session_destroy();
    json_response(true, 'Logged out');
}

if ($action === 'profile') {
    check_auth();
    $user = get_user();
    json_response(true, 'Profile', $user);
}

if ($action === 'register') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    
    $hashed = hash_password($password);
    $api_key = generate_api_key();
    
    $query = "INSERT INTO users (username, email, password, role, api_key, credits) 
             VALUES ('$username', '$email', '$hashed', '$role', '$api_key', 100)";
    
    if ($conn->query($query)) {
        json_response(true, 'User created successfully', ['api_key' => $api_key]);
    } else {
        json_response(false, 'Error: ' . $conn->error);
    }
}

json_response(false, 'Invalid action');
?>
