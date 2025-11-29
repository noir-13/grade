<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$school_id = $_SESSION['school_id'];

// Fetch all grades with semester grouping
$stmt = $conn->prepare("
    SELECT g.*, u.full_name as teacher_name, c.units, c.subject_description
    FROM grades g
    LEFT JOIN users u ON g.teacher_id = u.id
    LEFT JOIN classes c ON g.class_id = c.id
    WHERE g.student_id = ?
    ORDER BY g.semester DESC, g.subject_code ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$grades_by_semester = [];
while ($row = $result->fetch_assoc()) {
    $semester = $row['semester'] ?? 'N/A';
    if (!isset($grades_by_semester[$semester])) {
        $grades_by_semester[$semester] = [];
    }
    // Fallback for subject_name if not in grades table (though we added it, class info is reliable)
    if (empty($row['subject_name']) && !empty($row['subject_description'])) {
        $row['subject_name'] = $row['subject_description'];
    }
    $grades_by_semester[$semester][] = $row;
}

// Fetch student section
$stmtUser = $conn->prepare("SELECT section FROM users WHERE id = ?");
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$userSection = $stmtUser->get_result()->fetch_assoc()['section'] ?? 'N/A';

// Calculate overall statistics (GWA)
$total_grade_points = 0;
$total_units = 0;
$total_count = 0;

foreach ($grades_by_semester as $semester => $grades) {
    foreach ($grades as $grade) {
        $units = intval($grade['units'] ?? 3); // Default to 3 if missing
        $gradeVal = floatval($grade['grade']);
        
        // Only include valid grades in GWA (exclude dropped/inc if necessary, but assuming all numeric grades count)
        if ($gradeVal > 0) {
            $total_grade_points += ($gradeVal * $units);
            $total_units += $units;
            $total_count++;
        }
    }
}
$overall_gwa = $total_units > 0 ? number_format($total_grade_points / $total_units, 2) : 'N/A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic History | KLD Grade System</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="verdantDesignSystem.css">
    <script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>
    <style>
        .semester-section {
            margin-bottom: 2rem;
        }
        .semester-header {
            background: linear-gradient(135deg, var(--vds-forest), #0f4c3a);
            color: white;
            padding: 1.5rem;
            border-radius: 16px 16px 0 0;
        }
        .grade-pill {
            min-width: 60px;
            text-align: center;
        }
    </style>
</head>
<body class="vds-bg-vapor">

    <?php include 'navbar_dashboard.php'; ?>

    <div class="vds-container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="student_dashboard.php" class="vds-text-muted text-decoration-none mb-2 d-inline-block">
                    <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                </a>
                <h1 class="vds-h2">Academic History</h1>
                <p class="vds-text-muted">Complete record of your grades and academic performance</p>
            </div>
            <div>
                <button id="downloadExcel" class="vds-btn vds-btn-secondary me-2">
                    <i class="bi bi-file-earmark-excel me-1"></i>Download Excel
                </button>
                <button id="printGrades" class="vds-btn vds-btn-primary">
                    <i class="bi bi-printer me-1"></i>Print
                </button>
            </div>
        </div>

        <!-- Statistics Card -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="vds-card p-4 text-center">
                    <i class="bi bi-trophy-fill display-4 mb-2" style="color: var(--vds-forest);"></i>
                    <h3 class="vds-h3 mb-1"><?php echo $overall_gwa; ?></h3>
                    <p class="vds-text-muted mb-0">Overall GWA</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="vds-card p-4 text-center">
                    <i class="bi bi-journal-bookmark display-4 mb-2" style="color: #0284c7;"></i>
                    <h3 class="vds-h3 mb-1"><?php echo $total_count; ?></h3>
                    <p class="vds-text-muted mb-0">Total Subjects</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="vds-card p-4 text-center">
                    <i class="bi bi-person-badge display-4 mb-2" style="color: #b45309;"></i>
                    <h3 class="vds-h3 mb-1"><?php echo htmlspecialchars($userSection); ?></h3>
                    <p class="vds-text-muted mb-0">Section</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="vds-card p-4 text-center">
                    <i class="bi bi-person-badge display-4 mb-2" style="color: #15803d;"></i>
                    <h3 class="vds-h3 mb-1"><?php echo htmlspecialchars($school_id); ?></h3>
                    <p class="vds-text-muted mb-0">Student ID</p>
                </div>
            </div>
        </div>

        <!-- Grades by Semester -->
        <?php if (count($grades_by_semester) > 0): ?>
            <?php foreach ($grades_by_semester as $semester => $grades): ?>
                <div class="semester-section">
                    <div class="vds-card overflow-hidden">
                        <div class="semester-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="vds-h3 mb-1" style="color: white;"><?php echo htmlspecialchars($semester); ?></h3>
                                    <p class="mb-0" style="color: rgba(255,255,255,0.8);"><?php echo count($grades); ?> subjects</p>
                                </div>
                                <div class="text-end">
                                    <?php
                                        $sem_grade_points = 0;
                                        $sem_units = 0;
                                        foreach ($grades as $g) {
                                            $u = intval($g['units'] ?? 3);
                                            $sem_grade_points += (floatval($g['grade']) * $u);
                                            $sem_units += $u;
                                        }
                                        $sem_gwa = $sem_units > 0 ? number_format($sem_grade_points / $sem_units, 2) : 'N/A';
                                    ?>
                                    <span class="small" style="color: rgba(255,255,255,0.7);">Semester GWA</span>
                                    <h2 class="vds-h2 mb-0" style="color: white;"><?php echo $sem_gwa; ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="vds-table mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Subject Code</th>
                                        <th>Subject Name</th>
                                        <th>Units</th>
                                        <th>Section</th>
                                        <th>Raw Grade</th>
                                        <th>Grade</th>
                                        <th>Remarks</th>
                                        <th class="text-end pe-4">Date Posted</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($grades as $grade): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold" style="color: var(--vds-forest);">
                                                <?php echo htmlspecialchars($grade['subject_code']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($grade['subject_name'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($grade['units'] ?? 3); ?></td>
                                            <td><?php echo htmlspecialchars($grade['section'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($grade['raw_grade'] ?? '-'); ?></td>
                                            <td>
                                                <?php 
                                                    $g = floatval($grade['grade']);
                                                    $color = $g <= 3.0 ? 'text-success' : 'text-danger';
                                                    echo "<span class='fw-bold $color'>" . number_format($g, 2) . "</span>";
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($grade['remarks'] ?? '-'); ?></td>
                                            <td class="text-end pe-4 text-muted">
                                                <?php echo date('M d, Y', strtotime($grade['created_at'])); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="vds-card p-5 text-center">
                <i class="bi bi-inbox display-1 text-muted mb-3" style="opacity: 0.3;"></i>
                <h3 class="vds-h3 text-muted">No Grades Available</h3>
                <p class="vds-text-muted">Your grades will appear here once posted by your teachers.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer_dashboard.php'; ?>

    <script>
        // Download as Excel
        document.getElementById('downloadExcel').addEventListener('click', () => {
            const wb = XLSX.utils.book_new();
            
            // Create summary sheet
            const summaryData = [
                ['KLD Grading System - Student Grade Report'],
                ['Student Name:', '<?php echo addslashes($full_name); ?>'],
                ['Student ID:', '<?php echo addslashes($school_id); ?>'],
                ['Section:', '<?php echo addslashes($userSection); ?>'],
                ['Overall GWA:', '<?php echo $overall_gwa; ?>'],
                ['Generated:', new Date().toLocaleDateString()],
                [],
                ['Semester', 'Subject Code', 'Subject Name', 'Units', 'Section', 'Raw Grade', 'Grade', 'Remarks', 'Date Posted']
            ];

            <?php foreach ($grades_by_semester as $semester => $grades): ?>
                <?php foreach ($grades as $grade): ?>
                summaryData.push([
                    '<?php echo addslashes($semester); ?>',
                    '<?php echo addslashes($grade['subject_code']); ?>',
                    '<?php echo addslashes($grade['subject_name'] ?? ''); ?>',
                    '<?php echo addslashes($grade['units'] ?? 3); ?>',
                    '<?php echo addslashes($grade['section'] ?? ''); ?>',
                    '<?php echo addslashes($grade['raw_grade'] ?? ''); ?>',
                    <?php echo $grade['grade']; ?>,
                    '<?php echo addslashes($grade['remarks'] ?? ''); ?>',
                    '<?php echo date('Y-m-d', strtotime($grade['created_at'])); ?>'
                ]);
                <?php endforeach; ?>
            <?php endforeach; ?>

            const ws = XLSX.utils.aoa_to_sheet(summaryData);
            XLSX.utils.book_append_sheet(wb, ws, "Grades");
            XLSX.writeFile(wb, 'My_Grades_<?php echo $school_id; ?>.xlsx');
        });

        // Print
        document.getElementById('printGrades').addEventListener('click', () => {
            window.print();
        });
    </script>

    <style media="print">
        .vds-btn, .navbar, footer, .no-print {
            display: none !important;
        }
        .vds-container {
            max-width: 100% !important;
        }
        .semester-section {
            page-break-inside: avoid;
        }
    </style>

</body>
</html>
