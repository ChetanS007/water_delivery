<?php
session_start();
if(isset($_SESSION['user_id'])) {
    if($_SESSION['role'] == 'Delivery') {
        header("Location: dashboard.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delivery Partner Login - Sudha Jal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        html, body {
            height: 100%;
        }
        body {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('../assets/img/hero-water.jpg') no-repeat center center/cover;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            max-width: 400px;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 20px;
            padding: 30px;
        }
    </style>
</head>
<body>

<div class="login-card shadow-lg">
    <div class="text-center mb-4">
        <h3 class="fw-bold text-success">Delivery Partner</h3>
        <p class="text-muted">Login to view tasks</p>
    </div>
    <form action="../auth_action.php" method="POST">
        <input type="hidden" name="action" value="login">
        <input type="hidden" name="login_type" value="delivery">
        <div class="mb-3">
            <label class="form-label">Mobile Number</label>
            <input type="text" name="mobile" class="form-control" required placeholder="Mobile">
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required placeholder="******">
        </div>
        <button type="submit" class="btn btn-success w-100 mb-3" style="border-radius: 50px;">Login</button>
        <div class="text-center">
            <a href="index.php" class="text-muted small">Back to Home</a>
        </div>
    </form>
</div>

</body>
</html>
