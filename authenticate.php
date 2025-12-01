<?php
$lifetime = 86400 * 7; // 7 days
session_set_cookie_params($lifetime);
session_start();
require 'db_connect.php';
require 'PHPMailer-7.0.0/src/PHPMailer.php';
require 'PHPMailer-7.0.0/src/SMTP.php';
require 'PHPMailer-7.0.0/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function resendOTP($email) {
    // Re-use logic or include from a helper file. 
    // For simplicity, I'll inline a basic version or assume the user will request it via UI if I redirect them.
    // Actually, the requirement says "another code will be sent to their email".
    // So I must generate and send here.
    
    global $conn;
    $otp = rand(100000, 999999);
    $expires_at = date('Y-m-d H:i:s', time() + 600);
    
    $stmtOtp = $conn->prepare("INSERT INTO verification_codes (email, code, expires_at) VALUES (?, ?, ?)");
    $stmtOtp->bind_param("sss", $email, $otp, $expires_at);
    $stmtOtp->execute();

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'kevinselibio10@gmail.com'; 
        $mail->Password   = 'ruxmlcupgdicyywc';   
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->setFrom('kevinselibio10@gmail.com', 'KLD Grade System');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'KLD Verification Code';
        $mail->Body    = "Your new OTP code is <b>$otp</b>.";
        $mail->send();
    } catch (Exception $e) {
        // Log error
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate Limiting Check
    if (isset($_SESSION['lockout_time']) && time() < $_SESSION['lockout_time']) {
        $remaining = ceil(($_SESSION['lockout_time'] - time()) / 60);
        $_SESSION['login_error'] = "Too many failed attempts. Please try again in $remaining minutes.";
        header("Location: login.php");
        exit();
    }

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, role, full_name, password_hash, email, is_verified, school_id, status, is_profile_complete FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    $login_successful = false;
    $user = null;

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password_hash'])) {
            $login_successful = true;
        }
    }

    if ($login_successful) {
        // 1. Check Verification
        if ($user['is_verified'] == 0) {
            $_SESSION['verify_email'] = $email;
            $_SESSION['register_step'] = 2;
            resendOTP($email);
            $_SESSION['login_error'] = "Email not verified. A new code has been sent.";
            header("Location: register.php?step=2");
            exit();
        }

        // 1.5 Check Approval Status (For Teachers)
        if ($user['status'] === 'pending') {
            $_SESSION['login_error'] = "Your account is awaiting approval from the Institute Head.";
            $_SESSION['login_email'] = $email;
            header("Location: login.php");
            exit();
        }

        // 2. Login Success
        session_regenerate_id(true); // Prevent Session Fixation
        $_SESSION['login_attempts'] = 0; // Reset attempts
        unset($_SESSION['lockout_time']);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['school_id'] = $user['school_id'];
        $_SESSION['is_profile_complete'] = $user['is_profile_complete'];

        if ($user['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } elseif ($user['role'] === 'teacher') {
            header("Location: teacher_dashboard.php");
        } else {
            header("Location: student_dashboard.php");
        }
        exit();

    } else {
        // Failed Login
        if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
        $_SESSION['login_attempts']++;
        
        if ($_SESSION['login_attempts'] >= 5) {
            $_SESSION['lockout_time'] = time() + (15 * 60); // 15 minutes
            $_SESSION['login_error'] = "Too many failed attempts. Please try again in 15 minutes.";
        } else {
            $_SESSION['login_error'] = "Incorrect email or password.";
        }
        
        $_SESSION['login_email'] = $email;
        header("Location: login.php");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>
