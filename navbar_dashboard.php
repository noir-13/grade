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
            
            <a href="profile.php" class="vds-nav-link">Profile</a>
            <a href="logout.php" class="vds-btn vds-btn-secondary" style="padding: 8px 20px;">Logout</a>
        </div>
    </div>
</nav>
