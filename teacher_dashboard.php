<?php
session_start();

// Ensure user is logged in and is a teacher
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$first_name = $_SESSION['full_name'] ?? 'Teacher';

// Check Profile Completion
require 'check_profile.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard | KLD Grade System</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="verdantDesignSystem.css">
    <!-- SheetJS for Excel Parsing -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>
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

        .vds-file-drop {
            border: 2px dashed var(--vds-sage);
            border-radius: 16px;
            padding: 3rem;
            text-align: center;
            background: rgba(255,255,255,0.5);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .vds-file-drop:hover {
            border-color: var(--vds-forest);
            background: rgba(13, 59, 46, 0.05);
        }
    </style>
</head>
<body class="vds-bg-vapor">

    <?php include 'navbar_dashboard.php'; ?>

    <div class="vds-container py-5">
        
        <!-- Welcome Section -->
        <div class="dashboard-header mb-5 fade-in-up">
            <div class="position-relative" style="z-index: 2;">
                <span class="vds-pill mb-3" style="background: rgba(255,255,255,0.2); color: white; border: none;">Faculty Portal</span>
                <h1 class="vds-h1 mb-2" style="color: white;">Welcome, <?php echo htmlspecialchars($first_name); ?></h1>
                <p class="vds-text-lead mb-0" style="color: rgba(255,255,255,0.8);">Manage your classes and submit grades efficiently.</p>
            </div>
        </div>

        <!-- Actions Grid -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="vds-card p-4 action-card text-center">
                    <div class="icon-box mx-auto" style="background: var(--vds-vapor); color: var(--vds-forest);">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <h3 class="vds-h3">My Classes</h3>
                    <p class="vds-text-muted mb-4">View student lists and class schedules.</p>
                    <a href="my_classes.php" class="vds-btn vds-btn-secondary w-100">View Classes</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="vds-card p-4 action-card text-center">
                    <div class="icon-box mx-auto" style="background: #dcfce7; color: #15803d;">
                        <i class="bi bi-file-earmark-spreadsheet-fill"></i>
                    </div>
                    <h3 class="vds-h3">Upload Grades</h3>
                    <p class="vds-text-muted mb-4">Drag & drop Excel files to publish grades.</p>
                    <a href="grade_upload.php" class="vds-btn vds-btn-primary w-100">Upload Now</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="vds-card p-4 action-card text-center">
                    <div class="icon-box mx-auto" style="background: #f3f4f6; color: #4b5563;">
                        <i class="bi bi-journal-text"></i>
                    </div>
                    <h3 class="vds-h3">Class Records</h3>
                    <p class="vds-text-muted mb-4">Manage and edit student grades manually.</p>
                    <a href="teacher_records.php" class="vds-btn vds-btn-secondary w-100">View Records</a>
                </div>
            </div>
        </div>

    </div>

    <?php include 'footer_dashboard.php'; ?>

</body>
</html>
