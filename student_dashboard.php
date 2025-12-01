<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];

// Check Profile Completion
require 'check_profile.php';


// Fetch Grades with Units
$stmt = $conn->prepare("
    SELECT g.*, c.units 
    FROM grades g 
    LEFT JOIN classes c ON g.class_id = c.id 
    WHERE g.student_id = ? 
    ORDER BY g.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$grades = [];
$total_grade_points = 0;
$total_units = 0;

while ($row = $result->fetch_assoc()) {
    $grades[] = $row;
    $units = intval($row['units'] ?? 3); // Default to 3 if missing
    $gradeVal = floatval($row['grade']);
    
    if ($gradeVal > 0) {
        $total_grade_points += ($gradeVal * $units);
        $total_units += $units;
    }
}

$gwa = $total_units > 0 ? number_format($total_grade_points / $total_units, 2) : 'N/A';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | KLD Grade System</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="verdantDesignSystem.css">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, var(--vds-forest), #0f4c3a);
            color: white;
            border-radius: 24px;
            padding: 3rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(13, 59, 46, 0.15);
        }
        
        .dashboard-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            min-width: 150px;
        }

        .action-card {
            height: 100%;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .action-card:hover {
            transform: translateY(-5px);
            border-color: var(--vds-sage);
            box-shadow: 0 15px 30px rgba(0,0,0,0.05);
        }

        .icon-box {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body class="vds-bg-vapor">

    <?php include 'navbar_dashboard.php'; ?>

    <div class="vds-container py-5">
        
        <!-- Welcome Section -->
        <div class="dashboard-header mb-5 fade-in-up">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-4 position-relative" style="z-index: 2;">
                <div>
                    <span class="vds-pill mb-3" style="background: rgba(255,255,255,0.2); color: white; border: none;">Student Portal</span>
                    <h1 class="vds-h1 mb-2" style="color: white;">Welcome back, <?php echo htmlspecialchars($full_name); ?></h1>
                    <p class="vds-text-lead mb-0" style="color: rgba(255,255,255,0.8);">Track your academic progress and stay updated.</p>
                </div>
                <div class="stat-card">
                    <span class="d-block text-uppercase small letter-spacing-2 mb-1" style="opacity: 0.8;">GWA</span>
                    <span class="d-block display-4 fw-bold"><?php echo $gwa; ?></span>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <!-- Quick Actions -->
            <div class="col-md-4">
                <div class="vds-card p-4 action-card text-center">
                    <div class="icon-box mx-auto" style="background: var(--vds-vapor); color: var(--vds-forest);">
                        <i class="bi bi-journal-bookmark-fill"></i>
                    </div>
                    <h3 class="vds-h3">My Grades</h3>
                    <p class="vds-text-muted mb-4">View your complete academic history and detailed grade reports.</p>
                    <a href="student_history.php" class="vds-btn vds-btn-primary w-100">View Grades</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="vds-card p-4 action-card text-center">
                    <div class="icon-box mx-auto" style="background: #e0f2fe; color: #0284c7;">
                        <i class="bi bi-person-badge-fill"></i>
                    </div>
                    <h3 class="vds-h3">My Profile</h3>
                    <p class="vds-text-muted mb-4">Manage your personal information and account settings.</p>
                    <a href="profile.php" class="vds-btn vds-btn-secondary w-100">Update Profile</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="vds-card p-4 action-card text-center">
                    <div class="icon-box mx-auto" style="background: #f3f4f6; color: #6b7280;">
                        <i class="bi bi-calendar-week-fill"></i>
                    </div>
                    <h3 class="vds-h3">Class Schedule</h3>
                    <p class="vds-text-muted mb-4">View your upcoming classes and examination schedules.</p>
                    <a href="student_schedule.php" class="vds-btn vds-btn-secondary w-100">View Schedule</a>
                </div>
            </div>
        </div>

        <!-- Recent Grades -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="vds-h3 mb-0">Recent Activity</h3>
            <a href="grades.php" class="vds-btn vds-btn-text">View All <i class="bi bi-arrow-right"></i></a>
        </div>

        <div class="vds-card overflow-hidden">
            <div class="table-responsive">
                <table class="vds-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Subject Code</th>
                            <th>Grade</th>
                            <th>Remarks</th>
                            <th class="text-end pe-4">Date Posted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($grades) > 0): ?>
                            <?php foreach (array_slice($grades, 0, 5) as $grade): ?>
                                <tr>
                                    <td class="ps-4 fw-bold" style="color: var(--vds-forest);"><?php echo htmlspecialchars($grade['subject_code']); ?></td>
                                    <td>
                                        <?php 
                                            $gradeVal = floatval($grade['grade']);
                                            $badgeClass = 'vds-pill-pass';
                                            if ($gradeVal > 3.0) $badgeClass = 'vds-pill-fail';
                                            elseif ($gradeVal >= 2.5) $badgeClass = 'vds-pill-warn';
                                        ?>
                                        <span class="vds-pill <?php echo $badgeClass; ?>">
                                            <?php echo htmlspecialchars($grade['grade']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($grade['remarks']); ?></td>
                                    <td class="text-end pe-4 text-muted"><?php echo date('M d, Y', strtotime($grade['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center p-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox display-4 d-block mb-3" style="opacity: 0.3;"></i>
                                        No grades have been posted yet.
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <?php include 'footer_dashboard.php'; ?>

</body>
</html>
