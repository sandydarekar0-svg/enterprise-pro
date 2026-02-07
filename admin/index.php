<?php
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = $conn->query("SELECT * FROM users WHERE username = '$username' AND role = 'admin' LIMIT 1");
    $user = $result->fetch_assoc();
    
    if ($user && verify_password($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header('Location: dashboard.html');
        exit;
    } else {
        $error = 'Invalid credentials or not an admin';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login - WhatsApp Bulk Sender</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card login-card bg-white">
                <h3 class="text-center mb-4">👨‍💼 Admin Login</h3>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required autofocus>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
                
                <hr>
                <p class="text-center text-muted mb-0">
                    <strong>Demo:</strong> admin / admin
                </p>
            </div>
        </div>
    </div>
</div>

</body>
</html>
