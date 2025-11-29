<?php
// Middleware to check if user profile is complete
// Should be included in dashboard pages AFTER session_start()

if (isset($_SESSION['user_id']) && $_SESSION['role'] !== 'admin') {
    // Check if profile is complete
    if (!isset($_SESSION['is_profile_complete']) || $_SESSION['is_profile_complete'] != 1) {
        // Double check from database to be sure (in case session is stale)
        require_once 'db_connect.php';
        $stmt = $conn->prepare("SELECT is_profile_complete FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $_SESSION['is_profile_complete'] = $row['is_profile_complete'];
            
            if ($row['is_profile_complete'] == 0) {
                // Redirect to profile page if not complete
                // Prevent redirect loop if already on profile.php
                $current_page = basename($_SERVER['PHP_SELF']);
                if ($current_page !== 'profile.php' && $current_page !== 'logout.php') {
                    header("Location: profile.php?notice=complete_profile");
                    exit();
                }
            }
        }
    }
}
?>
