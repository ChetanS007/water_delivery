<?php
require_once 'includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action == 'register') {
        $full_name = htmlspecialchars($_POST['full_name']);
        $mobile = htmlspecialchars($_POST['mobile']);
        $customer_type = $_POST['customer_type'];
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $address = htmlspecialchars($_POST['address']);
        $latitude = $_POST['latitude'] ?: 0.0;
        $longitude = $_POST['longitude'] ?: 0.0;

        // Check if mobile exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE mobile = ?");
        $stmt->execute([$mobile]);
        if ($stmt->rowCount() > 0) {
            echo "<script>alert('हा मोबाईल क्रमांक आधीच नोंदणीकृत आहे!'); window.location.href='index.php';</script>";
            exit();
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO users (customer_type, full_name, mobile, address, latitude, longitude, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$customer_type, $full_name, $mobile, $address, $latitude, $longitude, $password]);
            
            $user_id = $pdo->lastInsertId();
            
            // Generate QR Code data (Just a unique string, rendered on frontend)
            $qr_code = "CUST-" . $user_id . "-" . time();
            $update = $pdo->prepare("UPDATE users SET qr_code = ? WHERE id = ?");
            $update->execute([$qr_code, $user_id]);

            echo "<script>alert('नोंदणी यशस्वी झाली! कृपया लॉगिन करा.'); window.location.href='index.php';</script>";
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }

    } elseif ($action == 'login') {
        $password = $_POST['password'];

        // --- Admin Login (Username) ---
        if (isset($_POST['username'])) {
            $username = htmlspecialchars($_POST['username']);
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['role'] = $admin['role'];
                $_SESSION['name'] = $admin['full_name'];
                header("Location: admin/dashboard.php");
                exit();
            }
            echo "<script>alert('अवैध ॲडमिन कडेन्शियल्स!'); window.location.href='admin/login.php';</script>";
            exit();
        } 
        
        // --- Customer / Delivery Login (Mobile) ---
        else {
            $mobile = htmlspecialchars($_POST['mobile']);
            $login_type = $_POST['login_type'] ?? 'customer'; // Default to customer

            // 1. DELIVERY BOY LOGIN (Prioritized if explicitly requested or if found)
            if ($login_type === 'delivery') {
                $stmt = $pdo->prepare("SELECT * FROM delivery_boys WHERE mobile = ?");
                $stmt->execute([$mobile]);
                $boy = $stmt->fetch();

                if ($boy && password_verify($password, $boy['password'])) {
                    $_SESSION['user_id'] = $boy['id'];
                    $_SESSION['role'] = 'Delivery';
                    $_SESSION['name'] = $boy['full_name'];
                    header("Location: delivery/dashboard.php");
                    exit();
                }
                // If failed delivery login, show error specific to delivery
                 echo "<script>alert('अवैध डिलिव्हरी पार्टनर कडेन्शियल्स!'); window.location.href='delivery/login.php';</script>";
                 exit();
            }

            // 2. CUSTOMER LOGIN (Default)
            $stmt = $pdo->prepare("SELECT * FROM users WHERE mobile = ?");
            $stmt->execute([$mobile]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = 'Customer';
                $_SESSION['name'] = $user['full_name'];
                $_SESSION['qr_code'] = $user['qr_code'];
                header("Location: index.php");
                exit();
            }

            // Fallback: If not finding customer, technically could check delivery boy here too if we want a universal login, 
            // but strict separation is better for security and clarity.
            // However, to keep backward compatibility if no hidden field:
            if ($login_type !== 'delivery') {
                 // Try checking delivery boy just in case they used the main login but valid delivery creds? 
                 // No, requested behavior is separating them or making sure delivery redirects to delivery.
                 // Given the specific request: "when i'm login from delivery boy... redirects user landing page"
                 // This implies they might be logging in from the *main* login modal?
                 // Or they are logging in from delivery login page but it's treating them as user?
                 // Let's assume they use delivery login page.
            }

            echo "<script>alert('अवैध मोबाईल क्रमांक किंवा पासवर्ड!'); window.location.href='index.php';</script>";
        }

        echo "<script>alert('अवैध मोबाईल क्रमांक किंवा पासवर्ड!'); window.location.href='index.php';</script>";
    }
}
?>
