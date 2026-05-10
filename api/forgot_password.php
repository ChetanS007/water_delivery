<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'send_otp') {
        $mobile = $_POST['mobile'] ?? '';
        
        $stmt = $pdo->prepare("SELECT id, verification_code FROM users WHERE mobile = ?");
        $stmt->execute([$mobile]);
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            $otp = $user['verification_code'];
            
            if (empty($otp)) {
                $otp = rand(1000, 9999);
                $update_stmt = $pdo->prepare("UPDATE users SET verification_code = ? WHERE mobile = ?");
                $update_stmt->execute([$otp, $mobile]);
            }

            $_SESSION['reset_mobile'] = $mobile;
            
            echo json_encode(['success' => true, 'message' => 'OTP sent successfully', 'otp' => $otp]);
        } else {
            echo json_encode(['success' => false, 'message' => 'हा मोबाईल क्रमांक नोंदणीकृत नाही. (Mobile number not registered)']);
        }
        exit;
    }

    if ($action == 'verify_otp') {
        $otp = $_POST['otp'] ?? '';
        $mobile = $_SESSION['reset_mobile'] ?? '';
        
        $stmt = $pdo->prepare("SELECT verification_code FROM users WHERE mobile = ?");
        $stmt->execute([$mobile]);
        $user = $stmt->fetch();
        
        if ($user && $user['verification_code'] == $otp && !empty($otp)) {
            echo json_encode(['success' => true, 'message' => 'OTP verified']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid verification code. Please try again.']);
        }
        exit;
    }

    if ($action == 'reset_password') {
        $mobile = $_SESSION['reset_mobile'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        
        if (empty($mobile) || empty($new_password)) {
            echo json_encode(['success' => false, 'message' => 'अवैध विनंती! (Invalid Request)']);
            exit;
        }

        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        
        $stmt = $pdo->prepare("UPDATE users SET password = ?, verification_code = NULL WHERE mobile = ?");
        if ($stmt->execute([$hashed_password, $mobile])) {
            // Clear session variables
            unset($_SESSION['reset_mobile']);
            echo json_encode(['success' => true, 'message' => 'पासवर्ड यशस्वीरित्या बदलला! (Password updated successfully)']);
        } else {
            echo json_encode(['success' => false, 'message' => 'पासवर्ड अपडेट करण्यात त्रुटी. (Error updating password)']);
        }
        exit;
    }
}
?>
