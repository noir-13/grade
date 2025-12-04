<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$school_id = $_SESSION['school_id'] ?? 'N/A';

// Fetch all grades with semester grouping
$stmt = $conn->prepare("
    SELECT g.*, u.full_name as teacher_name, c.units, c.subject_description, c.section as class_section
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

// Fetch student section and program
$stmtUser = $conn->prepare("
    SELECT u.section, p.code as program_code 
    FROM users u 
    LEFT JOIN programs p ON u.program_id = p.id 
    WHERE u.id = ?
");
$stmtUser->bind_param("i", $user_id);
$stmtUser->execute();
$userRow = $stmtUser->get_result()->fetch_assoc();
$userSection = $userRow['section'] ?? 'N/A';
$programCode = $userRow['program_code'] ?? '';
$fullSection = trim("$programCode $userSection");

// Calculate overall statistics (GWA)
$total_grade_points = 0;
$total_units = 0;
$total_count = 0;

$total_subjects_count = 0;

foreach ($grades_by_semester as $semester => $grades) {
    foreach ($grades as $grade) {
        $units = intval($grade['units'] ?? 3); // Default to 3 if missing
        $gradeVal = floatval($grade['grade']);
        
        $total_subjects_count++;

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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
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
        @media print {
            @page { size: A4; margin: 15mm; }
            body { 
                background: white !important; 
                font-family: 'Times New Roman', Times, serif; /* Formal font for print */
                color: black !important;
            }
            .vds-container { max-width: 100% !important; width: 100% !important; padding: 0 !important; }
            .vds-card { 
                box-shadow: none !important; 
                border: none !important; 
                padding: 0 !important;
                margin-bottom: 20px !important;
            }
            .semester-header { 
                background: none !important; 
                color: black !important; 
                border-bottom: 2px solid #000;
                padding: 10px 0 !important;
                border-radius: 0 !important;
            }
            .semester-header h3, .semester-header h2, .semester-header p, .semester-header span {
                color: black !important;
            }
            .table-responsive { overflow: visible !important; }
            .vds-table th { 
                background-color: #f0f0f0 !important; 
                color: black !important; 
                border: 1px solid #000 !important;
            }
            .vds-table td {
                border: 1px solid #000 !important;
            }
            .d-print-none { display: none !important; }
            
            /* Hide URL and date headers/footers added by browser if possible */
            a[href]:after { content: none !important; }
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
            <div class="d-flex align-items-center gap-2">
                <select id="gradeDisplayFilter" class="form-select w-auto d-print-none">
                    <option value="all">Show All Grades</option>
                    <option value="midterm">Midterm Only</option>
                    <option value="final">Final Only</option>
                    <option value="semestral">Semestral Only</option>
                </select>
                <button id="downloadPdf" class="vds-btn vds-btn-secondary">
                    <i class="bi bi-file-earmark-pdf me-1"></i>Download PDF
                </button>
                <button id="printGrades" class="vds-btn vds-btn-primary">
                    <i class="bi bi-printer me-1"></i>Print
                </button>
            </div>
        </div>

        <div id="gradeReport">
            <!-- Header for PDF only -->
            <div class="d-none d-print-block mb-4 text-center" id="pdfHeader">
                <h2 class="vds-h2">KLD Grade System</h2>
                <p class="mb-1">Student Grade Report</p>
                <p class="small text-muted">Generated on <?php echo date('F d, Y'); ?></p>
            </div>
            
            <div class="d-none d-print-block mb-4" id="studentInfoPdf">
                <div class="vds-card p-3">
                    <div class="row">
                        <div class="col-6">
                            <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($full_name); ?></p>
                            <p class="mb-0"><strong>Student ID:</strong> <?php echo htmlspecialchars($school_id); ?></p>
                        </div>
                        <div class="col-6 text-end">
                            <p class="mb-1"><strong>Section:</strong> <?php echo htmlspecialchars($fullSection); ?></p>
                            <p class="mb-0"><strong>Overall GWA:</strong> <?php echo $overall_gwa; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Card -->
            <div class="row g-4 mb-5 d-print-none">
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
                        <h3 class="vds-h3 mb-1"><?php echo $total_subjects_count; ?></h3>
                        <p class="vds-text-muted mb-0">Total Subjects</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="vds-card p-4 text-center">
                        <i class="bi bi-person-badge display-4 mb-2" style="color: #b45309;"></i>
                        <h3 class="vds-h3 mb-1"><?php echo htmlspecialchars(($fullSection === '0' || empty($fullSection)) ? 'N/A' : $fullSection); ?></h3>
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
                <?php foreach ($grades_by_semester as $semester => $grades): 
                    $semId = 'sem-' . md5($semester);
                ?>
                    <div class="semester-section" id="<?php echo $semId; ?>">
                        <div class="vds-card overflow-hidden">
                            <div class="semester-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="vds-h3 mb-1" style="color: white;"><?php echo htmlspecialchars($semester); ?></h3>
                                        <p class="mb-0" style="color: rgba(255,255,255,0.8);"><?php echo count($grades); ?> subjects</p>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
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
                                        <button class="btn btn-sm btn-outline-light download-sem-btn d-print-none" data-sem-id="<?php echo $semId; ?>" data-sem-name="<?php echo htmlspecialchars($semester); ?>" title="Download Semester PDF">
                                            <i class="bi bi-download"></i>
                                        </button>
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
                                            <th class="col-midterm">Midterm</th>
                                            <th class="col-final">Final</th>
                                            <th class="col-semestral">Semestral</th>
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
                                                <td><?php 
                                                    $sec = $grade['section'];
                                                    if (empty($sec) || $sec === '0') {
                                                        $sec = $grade['class_section'] ?? '-';
                                                    }
                                                    if (is_numeric($sec) && !empty($programCode)) {
                                                        $sec = "$programCode $sec";
                                                    }
                                                    echo htmlspecialchars($sec); 
                                                ?></td>
                                                <td class="col-midterm"><?php echo $grade['midterm'] ? number_format($grade['midterm'], 2) : '-'; ?></td>
                                                <td class="col-final"><?php echo $grade['final'] ? number_format($grade['final'], 2) : '-'; ?></td>
                                                <td class="col-semestral">
                                                    <?php 
                                                        $g = floatval($grade['grade']);
                                                        if ($g > 0) {
                                                            $color = $g <= 3.0 ? 'text-success' : 'text-danger';
                                                            echo "<span class='fw-bold $color'>" . number_format($g, 2) . "</span>";
                                                        } else {
                                                            echo "-";
                                                        }
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
    </div>

    <?php include 'footer_dashboard.php'; ?>

    <script>
        // Download Full PDF
        document.getElementById('downloadPdf').addEventListener('click', () => {
            const element = document.getElementById('gradeReport');
            const header = document.getElementById('pdfHeader');
            const info = document.getElementById('studentInfoPdf');
            
            // Temporarily show header/info for PDF generation
            header.classList.remove('d-none');
            info.classList.remove('d-none');
            
            const opt = {
                margin: [10, 10, 10, 10],
                filename: 'My_Grades_Full_<?php echo $school_id; ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, scrollY: 0 },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            // Generate PDF
            html2pdf().set(opt).from(element).save().then(() => {
                // Hide header again
                header.classList.add('d-none');
                info.classList.add('d-none');
            });
        });

        // Download Semester PDF
        document.querySelectorAll('.download-sem-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const semId = btn.dataset.semId;
                const semName = btn.dataset.semName;
                const semCard = document.getElementById(semId).cloneNode(true);
                
                // Remove the download button from the clone
                const btnInClone = semCard.querySelector('.download-sem-btn');
                if(btnInClone) btnInClone.remove();

                // Create container
                const container = document.createElement('div');
                container.innerHTML = `
                    <div class="mb-4 text-center">
                        <h2 class="vds-h2">KLD Grade System</h2>
                        <p class="mb-1">Semester Grade Report</p>
                        <p class="small text-muted">Generated on <?php echo date('F d, Y'); ?></p>
                    </div>
                `;
                
                // Clone student info
                const infoClone = document.getElementById('studentInfoPdf').cloneNode(true);
                infoClone.classList.remove('d-none');
                container.appendChild(infoClone);
                
                // Append semester card
                container.appendChild(semCard);

                const opt = {
                    margin: [10, 10, 10, 10],
                    filename: `Grade_Report_${semName.replace(/[^a-z0-9]/gi, '_')}.pdf`,
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2, useCORS: true, scrollY: 0 },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
                };

                html2pdf().set(opt).from(container).save();
            });
        });

        // Print
        document.getElementById('printGrades').addEventListener('click', () => {
            window.print();
        });

        // Grade Filter Logic
        const gradeFilter = document.getElementById('gradeDisplayFilter');
        if (gradeFilter) {
            gradeFilter.addEventListener('change', (e) => {
                const val = e.target.value;
                const midterms = document.querySelectorAll('.col-midterm');
                const finals = document.querySelectorAll('.col-final');
                const semestrals = document.querySelectorAll('.col-semestral');
                const rows = document.querySelectorAll('tbody tr');
                const sections = document.querySelectorAll('.semester-section');

                // Reset all visibility
                midterms.forEach(el => el.style.display = '');
                finals.forEach(el => el.style.display = '');
                semestrals.forEach(el => el.style.display = '');
                rows.forEach(row => row.style.display = '');
                sections.forEach(sec => sec.style.display = '');

                if (val === 'all') return;

                // Hide columns
                if (val === 'midterm') {
                    finals.forEach(el => el.style.display = 'none');
                    semestrals.forEach(el => el.style.display = 'none');
                } else if (val === 'final') {
                    midterms.forEach(el => el.style.display = 'none');
                    semestrals.forEach(el => el.style.display = 'none');
                } else if (val === 'semestral') {
                    midterms.forEach(el => el.style.display = 'none');
                    finals.forEach(el => el.style.display = 'none');
                }

                // Filter rows based on content
                rows.forEach(row => {
                    const midCell = row.querySelector('.col-midterm');
                    const finCell = row.querySelector('.col-final');
                    const semCell = row.querySelector('.col-semestral');
                    
                    let shouldShow = true;
                    
                    if (val === 'midterm') {
                        if (!midCell || midCell.textContent.trim() === '-') shouldShow = false;
                    } else if (val === 'final') {
                        if (!finCell || finCell.textContent.trim() === '-') shouldShow = false;
                    } else if (val === 'semestral') {
                        if (!semCell || semCell.textContent.trim() === '-') shouldShow = false;
                    }
                    
                    if (!shouldShow) row.style.display = 'none';
                });

                // Hide empty semester sections
                sections.forEach(section => {
                    const visibleRows = section.querySelectorAll('tbody tr:not([style*="display: none"])');
                    if (visibleRows.length === 0) {
                        section.style.display = 'none';
                    }
                });
            });
        }
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
