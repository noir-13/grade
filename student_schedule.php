<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch Enrolled Classes
$stmt = $conn->prepare("
    SELECT c.*, u.full_name as teacher_name
    FROM enrollments e
    JOIN classes c ON e.class_id = c.id
    LEFT JOIN users u ON c.teacher_id = u.id
    WHERE e.student_id = ?
    ORDER BY c.subject_code
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$classes = [];
while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Schedule | KLD Grade System</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="verdantDesignSystem.css">
</head>
<body class="vds-bg-vapor">

    <?php include 'navbar_dashboard.php'; ?>

    <div class="vds-container py-5">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <span class="vds-pill mb-2" style="background: var(--vds-sage); color: var(--vds-forest);">Academics</span>
                <h1 class="vds-h2">Class Schedule</h1>
            </div>
        </div>

        <div class="vds-card overflow-hidden">
            <div class="table-responsive">
                <table class="vds-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Subject Code</th>
                            <th>Description</th>
                            <th>Section</th>
                            <th>Units</th>
                            <th>Instructor</th>
                            <th class="text-end pe-4">Schedule</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($classes) > 0): ?>
                            <?php foreach ($classes as $cls): ?>
                                <tr>
                                    <td class="ps-4 fw-bold" style="color: var(--vds-forest);"><?php echo htmlspecialchars($cls['subject_code']); ?></td>
                                    <td><?php echo htmlspecialchars($cls['subject_description']); ?></td>
                                    <td><?php echo htmlspecialchars($cls['section']); ?></td>
                                    <td><?php echo htmlspecialchars($cls['units']); ?></td>
                                    <td><?php echo htmlspecialchars($cls['teacher_name']); ?></td>
                                    <td class="text-end pe-4 text-muted"><?php echo htmlspecialchars($cls['schedule'] ?? 'TBA'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center p-5">
                                    <div class="text-muted">
                                        <i class="bi bi-calendar-x display-4 d-block mb-3" style="opacity: 0.3;"></i>
                                        You are not enrolled in any classes yet.
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
    <script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
