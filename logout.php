<?php
session_start();

$redirect = 'index.php';

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'Delivery') {
        $redirect = 'delivery/login.php';
    } elseif ($_SESSION['role'] === 'Admin') {
        $redirect = 'admin/login.php';
    }
}

session_destroy();
header("Location: " . $redirect);
exit();
?>
