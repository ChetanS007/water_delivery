<?php
session_start();
if(isset($_SESSION['user_id'])) {
    if($_SESSION['role'] == 'Admin' || $_SESSION['role'] == 'Superadmin') {
        header("Location: dashboard.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - AquaFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/water_delivery/assets/css/style.css">
    <style>
        body {
            background-color: var(--dark-bg);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            max-width: 400px;
            width: 100%;
            background: white;
            border-radius: 20px;
            padding: 30px;
        }
    </style>
</head>
<body>

<div class="login-card shadow-lg">
    <div class="text-center mb-4">
        <h3 class="fw-bold text-primary">Admin Portal</h3>
        <p class="text-muted">Login to manage the system</p>
    </div>
    <form action="/water_delivery/auth_action.php" method="POST">
        <input type="hidden" name="action" value="login">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control" required placeholder="Enter Username">
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required placeholder="******">
        </div>
        <button type="submit" class="btn btn-primary w-100 mb-3">Login</button>
        <div class="text-center">
            <a href="/water_delivery/index.php" class="text-muted small">Back to Home</a>
        </div>
    </form>
</div>

</body>
</html>
