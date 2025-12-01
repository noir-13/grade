<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Fetch user data
$stmt = $conn->prepare("SELECT full_name, email, role, phone_number, is_profile_complete, section FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = trim($_POST['section'] ?? '');
    
    if ($user['role'] === 'student' && !empty($section) && preg_match('/[^a-zA-Z0-9]/', $section)) {
        $message = "Section must be alphanumeric (e.g., 209, A, B1).";
        $messageType = "danger";
    } else {
        // Update User
        $stmtUpdate = $conn->prepare("UPDATE users SET section = ?, is_profile_complete = 1 WHERE id = ?");
        $stmtUpdate->bind_param("si", $section, $user_id);
        
        if ($stmtUpdate->execute()) {
            $_SESSION['is_profile_complete'] = 1;
            $message = "Profile updated successfully! Redirecting...";
            $messageType = "success";
            
            // Redirect based on role
            $redirect = ($user['role'] === 'teacher') ? 'teacher_dashboard.php' : 'student_dashboard.php';
            header("Refresh: 2; url=$redirect");
        } else {
            $message = "Error updating profile: " . $conn->error;
            $messageType = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Profile | KLD Grade System</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="verdantDesignSystem.css">
</head>
<body class="vds-bg-vapor d-flex align-items-center justify-content-center min-vh-100">

    <div class="vds-card p-5" style="max-width: 500px; width: 100%;">
        <div class="text-center mb-4">
            <img src="assets/logo2.png" alt="Logo" height="60" class="mb-3">
            <h1 class="vds-h3">Complete Your Profile</h1>
            <p class="vds-text-muted">Please update your contact information to continue.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> mb-4" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="vds-label">Full Name</label>
                <input type="text" class="vds-input" value="<?php echo htmlspecialchars($user['full_name']); ?>" disabled>
            </div>
            <div class="mb-3">
                <label class="vds-label">Email Address</label>
                <input type="email" class="vds-input" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
            </div>
            
            <?php if ($user['role'] === 'student'): ?>
            <div class="mb-4">
                <label class="vds-label">Section</label>
                <input type="text" name="section" class="vds-input" placeholder="e.g. 209" value="<?php echo htmlspecialchars($user['section'] ?? ''); ?>">
            </div>
            <?php endif; ?>

            <button type="submit" class="vds-btn vds-btn-primary w-100">
                Save & Continue <i class="bi bi-arrow-right ms-2"></i>
            </button>
        </form>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
