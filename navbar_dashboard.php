<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="vds-navbar">
    <div class="vds-container vds-nav-content">
        <a href="<?php 
            if ($_SESSION['role'] === 'teacher') echo 'teacher_dashboard.php';
            elseif ($_SESSION['role'] === 'admin') echo 'admin_dashboard.php';
            else echo 'student_dashboard.php'; 
        ?>" class="vds-brand">
            <img src="assets/logo2.png" alt="Logo" height="40">
            KLD Portal
        </a>
        <div class="vds-nav-links">
<?php
            // Announcement Notification Logic
            require_once 'db_connect.php';
            $unread_count = 0;
            if (isset($_SESSION['user_id'])) {
                $uid = $_SESSION['user_id'];
                $u_role = $_SESSION['role'];
                $three_days_ago = date('Y-m-d H:i:s', strtotime('-3 days'));
                
                if ($u_role === 'student') {
                    $stmtAnn = $conn->prepare("
                        SELECT COUNT(*) as count 
                        FROM announcements a 
                        WHERE (a.class_id IS NULL OR a.class_id IN (SELECT class_id FROM enrollments WHERE student_id = ?))
                        AND a.created_at > ?
                    ");
                    $stmtAnn->bind_param("is", $uid, $three_days_ago);
                } elseif ($u_role === 'teacher') {
                    $stmtAnn = $conn->prepare("SELECT COUNT(*) as count FROM announcements WHERE class_id IS NULL AND created_at > ?");
                    $stmtAnn->bind_param("s", $three_days_ago);
                } else {
                    $stmtAnn = $conn->prepare("SELECT COUNT(*) as count FROM announcements WHERE created_at > ?");
                    $stmtAnn->bind_param("s", $three_days_ago);
                }
                
                if (isset($stmtAnn)) {
                    $stmtAnn->execute();
                    $unread_count = $stmtAnn->get_result()->fetch_assoc()['count'];
                }
            }
            ?>

            <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'teacher'): ?>
                <a href="teacher_dashboard.php" class="vds-nav-link">Dashboard</a>
                <a href="my_classes.php" class="vds-nav-link">My Classes</a>
            <?php elseif(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="admin_dashboard.php" class="vds-nav-link">Dashboard</a>
                <a href="admin_students.php" class="vds-nav-link">Students</a>
            <?php else: ?>
                <a href="student_dashboard.php" class="vds-nav-link">Dashboard</a>
                <a href="student_classes.php" class="vds-nav-link">My Classes</a>
                <a href="student_history.php" class="vds-nav-link">My Grades</a>
            <?php endif; ?>
            
            <a href="announcements.php" class="vds-nav-link position-relative">
                Announcements
                <?php if ($unread_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                        <span class="visually-hidden">New alerts</span>
                    </span>
                <?php endif; ?>
            </a>

            <a href="profile.php" class="vds-nav-link">Profile</a>
            <a href="logout.php" class="vds-btn vds-btn-secondary" style="padding: 8px 20px;">Logout</a>
        </div>
    </div>
</nav>
