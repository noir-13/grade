<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$class_id_param = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

// Fetch all classes for this teacher
$classes = [];
$stmt = $conn->prepare("SELECT * FROM classes WHERE teacher_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $classes[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Grades | KLD Grade System</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="verdantDesignSystem.css">
    <script src="https://cdn.sheetjs.com/xlsx-0.20.0/package/dist/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .vds-file-drop {
            border: 2px dashed var(--vds-sage);
            border-radius: 16px;
            padding: 3rem;
            text-align: center;
            background: rgba(255,255,255,0.5);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .vds-file-drop:hover, .vds-file-drop.drag-over {
            border-color: var(--vds-forest);
            background: rgba(13, 59, 46, 0.05);
        }
        .validation-icon {
            font-size: 1.2rem;
        }
        .row-valid { background: rgba(34, 197, 94, 0.1); }
        .row-invalid { background: rgba(239, 68, 68, 0.1); }
        .row-duplicate { background: rgba(251, 191, 36, 0.1); }
        .editable-cell {
            border-bottom: 1px dashed var(--vds-sage);
            cursor: text;
            transition: background 0.2s;
        }
        .editable-cell:hover, .editable-cell:focus {
            background: rgba(255, 255, 255, 0.8);
            outline: none;
            border-bottom: 1px solid var(--vds-forest);
        }
    </style>
</head>
<body class="vds-bg-vapor">

    <?php include 'navbar_dashboard.php'; ?>

    <div class="vds-container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="teacher_dashboard.php" class="vds-text-muted text-decoration-none mb-2 d-inline-block"><i class="bi bi-arrow-left me-1"></i> Back to Dashboard</a>
                <h1 class="vds-h2">Upload Grades</h1>
                <p class="vds-text-muted">Import grades via Excel file for fast data encoding.</p>
            </div>
        </div>

        <!-- Step 1: Select Class -->
        <div class="vds-card p-4 mb-4">
            <h2 class="vds-h3 mb-3"><i class="bi bi-info-circle me-2"></i>Step 1: Select Class</h2>
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="vds-label">Select Class to Upload Grades For <span class="text-danger">*</span></label>
                    <select id="classSelect" class="vds-input">
                        <option value="">-- Select a Class --</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>" 
                                data-section="<?php echo htmlspecialchars($class['section']); ?>"
                                data-subject-code="<?php echo htmlspecialchars($class['subject_code']); ?>"
                                data-subject-name="<?php echo htmlspecialchars($class['subject_description']); ?>"
                                data-semester="<?php echo htmlspecialchars($class['semester']); ?>"
                                data-units="<?php echo htmlspecialchars($class['units'] ?? 3); ?>"
                                <?php echo ($class_id_param == $class['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['subject_description'] . ' (' . $class['subject_code'] . ') - ' . $class['section']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Only classes you have created are shown here.</small>
                </div>
            </div>
            
            <!-- Hidden Fields for Compatibility -->
            <input type="hidden" id="section">
            <input type="hidden" id="subjectCode">
            <input type="hidden" id="subjectName">
            <input type="hidden" id="semester">
        </div>

        <!-- Step 2: Upload Excel File -->
        <div class="vds-card p-5 mb-4" id="uploadStep" style="opacity: 0.5; pointer-events: none;">
            <div class="text-center mb-4">
                <h2 class="vds-h3"><i class="bi bi-cloud-arrow-up me-2"></i>Step 2: Upload Excel File</h2>
                <p class="vds-text-muted">Upload an Excel file (.xlsx) with columns: <strong>Student ID</strong>, <strong>Grade</strong>, <strong>Remarks (optional)</strong></p>
                <button id="downloadTemplate" class="vds-btn vds-btn-secondary btn-sm mt-2">
                    <i class="bi bi-download me-1"></i>Download Template
                </button>
            </div>
            
            <div class="vds-file-drop" id="dropZone">
                <i class="bi bi-cloud-arrow-up" style="font-size: 3rem; color: var(--vds-forest); margin-bottom: 1rem;"></i>
                <h4 class="vds-h4">Drag & Drop Excel File Here</h4>
                <p class="vds-text-muted">or click to browse</p>
                <input type="file" id="fileInput" hidden accept=".xlsx, .xls">
            </div>
        </div>

        <!-- Step 3: Preview & Validate -->
        <div id="previewContainer" style="display: none;" class="vds-card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h3 class="vds-h3 mb-1"><i class="bi bi-table me-2"></i>Step 3: Preview & Validate</h3>
                    <p class="vds-text-muted mb-0" id="previewSummary">Review your data before uploading</p>
                </div>
                <div>
                    <button id="validateBtn" class="vds-btn vds-btn-secondary me-2">
                        <i class="bi bi-shield-check me-1"></i>Validate Students
                    </button>
                    <button id="publishBtn" class="vds-btn vds-btn-primary" disabled>
                        <i class="bi bi-check-circle me-2"></i>Publish Grades
                    </button>
                </div>
            </div>

            <div class="alert alert-info d-flex align-items-center mb-3">
                <i class="bi bi-info-circle me-2"></i>
                <div>
                    <strong>Legend:</strong>
                    <span class="ms-3"><i class="bi bi-check-circle-fill text-success"></i> Valid</span>
                    <span class="ms-3"><i class="bi bi-exclamation-circle-fill text-danger"></i> Not Found</span>
                    <span class="ms-3"><i class="bi bi-person-x-fill text-danger"></i> Not Enrolled</span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="vds-table" id="previewTable">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th style="width: 60px;">Status</th>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Grade</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- JS will populate this -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include 'footer_dashboard.php'; ?>

    <script>
        const classSelect = document.getElementById('classSelect');
        const uploadStep = document.getElementById('uploadStep');
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const previewContainer = document.getElementById('previewContainer');
        const previewTableBody = document.querySelector('#previewTable tbody');
        const publishBtn = document.getElementById('publishBtn');
        const validateBtn = document.getElementById('validateBtn');
        const downloadTemplateBtn = document.getElementById('downloadTemplate');
        const previewSummary = document.getElementById('previewSummary');
        
        // Hidden fields
        const sectionInput = document.getElementById('section');
        const subjectCodeInput = document.getElementById('subjectCode');
        const subjectNameInput = document.getElementById('subjectName');
        const semesterInput = document.getElementById('semester');

        let parsedData = [];
        let validationResults = {};
        let currentClassId = 0;

        // Transmutation Logic (Matches PHP)
        function transmuteGrade(raw) {
            raw = parseFloat(raw);
            if (isNaN(raw)) return ['-', ''];
            if (raw >= 97) return ['1.00', 'Passed'];
            if (raw >= 94) return ['1.25', 'Passed'];
            if (raw >= 91) return ['1.50', 'Passed'];
            if (raw >= 88) return ['1.75', 'Passed'];
            if (raw >= 85) return ['2.00', 'Passed'];
            if (raw >= 82) return ['2.25', 'Passed'];
            if (raw >= 79) return ['2.50', 'Passed'];
            if (raw >= 76) return ['2.75', 'Passed'];
            if (raw >= 76) return ['2.75', 'Passed'];
            if (raw >= 70) return ['3.00', 'Passed'];
            return ['5.00', 'Failed'];
        }

        // Initialize state based on selection
        function updateClassState() {
            const selectedOption = classSelect.options[classSelect.selectedIndex];
            if (selectedOption.value) {
                currentClassId = selectedOption.value;
                sectionInput.value = selectedOption.dataset.section;
                subjectCodeInput.value = selectedOption.dataset.subjectCode;
                subjectNameInput.value = selectedOption.dataset.subjectName;
                semesterInput.value = selectedOption.dataset.semester;
                
                uploadStep.style.opacity = '1';
                uploadStep.style.pointerEvents = 'auto';
            } else {
                currentClassId = 0;
                uploadStep.style.opacity = '0.5';
                uploadStep.style.pointerEvents = 'none';
                previewContainer.style.display = 'none';
            }
        }

        classSelect.addEventListener('change', updateClassState);
        
        // Run on load to handle pre-selection
        updateClassState();

        // Download Template
        downloadTemplateBtn.addEventListener('click', () => {
            const wb = XLSX.utils.book_new();
            const wsData = [
                ['Student ID', 'Raw Grade', 'Notes'],
                ['2024-2-000550', '98', 'Excellent work'],
                ['2024-2-000551', '85', ''],
                ['2024-2-000552', '74', 'Needs improvement']
            ];
            const ws = XLSX.utils.aoa_to_sheet(wsData);
            
            // Set column widths
            ws['!cols'] = [
                { wch: 20 }, // Student ID
                { wch: 15 }, // Raw Grade
                { wch: 30 }  // Notes
            ];
            
            XLSX.utils.book_append_sheet(wb, ws, "Template");
            XLSX.writeFile(wb, "grade_upload_template.xlsx");
        });

        // Drag & Drop Events
        dropZone.addEventListener('click', () => fileInput.click());
        
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('drag-over');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            const files = e.dataTransfer.files;
            if (files.length) handleFile(files[0]);
        });

        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length) handleFile(e.target.files[0]);
        });

        function handleFile(file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                parsedData = XLSX.utils.sheet_to_json(firstSheet, { header: 1 });
                
                // Remove header row if it exists
                if (parsedData.length > 0) {
                    parsedData = parsedData.slice(1);
                }

                renderPreview(parsedData);
                previewContainer.style.display = 'block';
            };
            reader.readAsArrayBuffer(file);
        }

        function renderPreview(data) {
            previewTableBody.innerHTML = '';
            validationResults = {};
            publishBtn.disabled = true;
            
            // Update Table Header
            const thead = document.querySelector('#previewTable thead tr');
            thead.innerHTML = `
                <th style="width: 50px;">#</th>
                <th style="width: 60px;">Status</th>
                <th>Student ID <i class="bi bi-pencil-square small text-muted ms-1"></i></th>
                <th>Student Name</th>
                <th>Units</th>
                <th>Raw Grade <i class="bi bi-pencil-square small text-muted ms-1"></i></th>
                <th>Transmuted</th>
                <th>Remarks</th>
            `;
            
            const units = classSelect.options[classSelect.selectedIndex].dataset.units || 3;

            data.forEach((row, index) => {
                // Ensure row has at least 3 elements
                if (!row[0]) row[0] = '';
                if (!row[1]) row[1] = '';
                if (!row[2]) row[2] = '';

                const rawGrade = row[1];
                const [transmuted, remarks] = transmuteGrade(rawGrade);
                
                    const studentId = row[0];
                    const idRegex = /^\d{4}-\d{1}-\d{6}$/;
                    let statusIcon = 'bi-question-circle text-muted';
                    let statusTitle = 'Pending Validation';

                    if (studentId && !idRegex.test(studentId)) {
                        statusIcon = 'bi-exclamation-triangle-fill text-warning';
                        statusTitle = 'Invalid ID Format (xxxx-x-xxxxxx)';
                    }

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${index + 1}</td>
                        <td><i class="bi ${statusIcon} validation-icon" id="status-${index}" title="${statusTitle}"></i></td>
                    <td contenteditable="true" class="editable-cell" onblur="updateData(${index}, 0, this.innerText)" onfocus="highlightRow(this)">${row[0]}</td>
                    <td id="student-name-${index}" class="text-muted">-</td>
                    <td>${units}</td>
                    <td contenteditable="true" class="editable-cell" oninput="handleGradeInput(${index}, this)" onblur="updateData(${index}, 1, this.innerText)" onfocus="highlightRow(this)">${rawGrade}</td>
                    <td id="transmuted-${index}"><span class="fw-bold text-primary">${transmuted}</span></td>
                    <td id="remarks-${index}"><span class="badge ${remarks === 'Passed' ? 'bg-success' : 'bg-danger'}">${remarks}</span> <small class="text-muted">${row[2]}</small></td>
                `;
                previewTableBody.appendChild(tr);
            });

            updateSummary(data.length, 0, 0, 0);
        }

        // Highlight row being edited
        window.highlightRow = function(cell) {
            document.querySelectorAll('tr').forEach(tr => tr.classList.remove('table-active'));
            cell.closest('tr').classList.add('table-active');
        };

        // Handle Real-time Grade Transmutation
        window.handleGradeInput = function(index, cell) {
            const raw = cell.innerText;
            const rawFloat = parseFloat(raw);
            
            if (isNaN(rawFloat) || rawFloat < 0 || rawFloat > 100) {
                 cell.classList.add('text-danger', 'fw-bold');
                 cell.title = "Grade must be between 0 and 100";
            } else {
                 cell.classList.remove('text-danger', 'fw-bold');
                 cell.title = "";
            }

            const [transmuted, remarks] = transmuteGrade(raw);
            
            document.getElementById(`transmuted-${index}`).innerHTML = `<span class="fw-bold text-primary">${transmuted}</span>`;
            
            // Preserve existing notes if any
            const existingNotes = parsedData[index][2] || '';
            document.getElementById(`remarks-${index}`).innerHTML = `<span class="badge ${remarks === 'Passed' ? 'bg-success' : 'bg-danger'}">${remarks}</span> <small class="text-muted">${existingNotes}</small>`;
        };

        // Update parsedData array when cell is blurred
        window.updateData = function(index, colIndex, value) {
            parsedData[index][colIndex] = value.trim();
            
            // If Student ID changed, reset validation status for that row
            if (colIndex === 0) {
                const idRegex = /^\d{4}-\d{1}-\d{6}$/;
                const isValidFormat = idRegex.test(value);
                
                const statusEl = document.getElementById(`status-${index}`);
                if (!isValidFormat && value) {
                    statusEl.className = 'bi bi-exclamation-triangle-fill validation-icon text-warning';
                    statusEl.title = 'Invalid ID Format (xxxx-x-xxxxxx)';
                } else {
                    statusEl.className = 'bi bi-question-circle validation-icon text-muted';
                    statusEl.title = 'Pending Validation';
                }
                
                document.getElementById(`student-name-${index}`).textContent = '-';
                statusEl.closest('tr').className = '';
                
                // Reset summary counts (visual only, real count updates on Validate)
                // We force user to click Validate again to be sure
                publishBtn.disabled = true;
            }
        };

        function updateSummary(total, valid, invalid, duplicates) {
            previewSummary.innerHTML = `
                Total: <strong>${total}</strong> | 
                Valid: <strong class="text-success">${valid}</strong> | 
                Error: <strong class="text-danger">${invalid}</strong> | 
                Auto-Enroll: <strong class="text-info">${duplicates}</strong>
            `;
        }

        // Validate Students
        validateBtn.addEventListener('click', async () => {
            if (!currentClassId) {
                alert("Please select a class first.");
                return;
            }

            validateBtn.disabled = true;
            validateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Validating...';

            const studentIds = parsedData
                .filter(row => row[0])
                .map(row => row[0]);

            try {
                const response = await fetch('api.php?action=validate_students', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        student_ids: studentIds,
                        class_id: currentClassId 
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // Process validation results
                    result.valid.forEach(student => {
                        validationResults[student.school_id] = {
                            valid: true,
                            name: student.name,
                            status: 'valid'
                        };
                    });

                    result.invalid.forEach(school_id => {
                        validationResults[school_id] = {
                            valid: true,
                            status: 'ghost_create'
                        };
                    });

                    if (result.not_enrolled) {
                        result.not_enrolled.forEach(school_id => {
                            validationResults[school_id] = {
                                valid: true, // Treat as valid for auto-enrollment
                                status: 'not_enrolled'
                            };
                        });
                    }

                    // Update UI
                    let validCount = 0;
                    let errorCount = 0;
                    let autoEnrollCount = 0;

                    parsedData.forEach((row, index) => {
                        const schoolId = row[0];
                        const statusIcon = document.getElementById(`status-${index}`);
                        const nameCell = document.getElementById(`student-name-${index}`);
                        const rowElem = statusIcon.closest('tr');

                        if (validationResults[schoolId]) {
                            const res = validationResults[schoolId];
                            if (res.status === 'valid') {
                                statusIcon.className = 'bi bi-check-circle-fill validation-icon text-success';
                                nameCell.textContent = res.name;
                                rowElem.className = 'row-valid';
                                validCount++;
                            } else if (res.status === 'not_enrolled') {
                                statusIcon.className = 'bi bi-person-plus-fill validation-icon text-info';
                                nameCell.textContent = 'Will Auto-Enroll';
                                rowElem.className = 'row-duplicate'; // Using duplicate style for auto-enroll
                                autoEnrollCount++;
                            } else if (res.status === 'ghost_create') {
                                statusIcon.className = 'bi bi-person-badge-fill validation-icon text-warning';
                                nameCell.textContent = 'Will Create Ghost Account';
                                rowElem.className = 'row-duplicate';
                                autoEnrollCount++;
                            } else {
                                statusIcon.className = 'bi bi-exclamation-circle-fill validation-icon text-danger';
                                nameCell.textContent = 'Not Found';
                                rowElem.className = 'row-invalid';
                                errorCount++;
                            }
                        }
                    });

                    updateSummary(parsedData.length, validCount, errorCount, autoEnrollCount);

                    if (validCount > 0 || autoEnrollCount > 0) {
                        publishBtn.disabled = false;
                    }

                    Swal.fire({
                        title: 'Validation Complete',
                        html: `✓ ${validCount} enrolled students<br>ℹ️ ${autoEnrollCount} will be auto-enrolled<br>✗ ${errorCount} errors (not found)`,
                        icon: 'info',
                        confirmButtonColor: '#0D3B2E'
                    });
                }
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error validating students: ' + error.message });
            } finally {
                validateBtn.disabled = false;
                validateBtn.innerHTML = '<i class="bi bi-shield-check me-1"></i>Validate Students';
            }
        });

        // Publish Grades
        publishBtn.addEventListener('click', async () => {
            if (!currentClassId) {
                Swal.fire({ icon: 'warning', title: 'Warning', text: 'Please select a class first.' });
                return;
            }

            const section = sectionInput.value;
            const subjectCode = subjectCodeInput.value;
            const subjectName = subjectNameInput.value;
            const semester = semesterInput.value;

            // Check for invalid grades
            const hasInvalidGrades = parsedData.some(row => {
                const grade = parseFloat(row[1]);
                return isNaN(grade) || grade < 0 || grade > 100;
            });

            if (hasInvalidGrades) {
                Swal.fire({ icon: 'error', title: 'Invalid Grades', text: 'Some grades are invalid (must be 0-100). Please correct them before publishing.' });
                return;
            }

            const result = await Swal.fire({
                title: 'Confirm Upload?',
                html: `Class: ${subjectCode} - ${section}<br>Total Records: ${parsedData.length}<br><br>This will save/update grades in the database.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0D3B2E',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Upload'
            });

            if (!result.isConfirmed) return;

            publishBtn.disabled = true;
            publishBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Publishing...';

            try {
                const response = await fetch('api.php?action=bulk_upload_grades', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        grades: parsedData,
                        section: section,
                        subject_code: subjectCode,
                        subject_name: subjectName,
                        semester: semester,
                        class_id: currentClassId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    let msg = `Inserted: ${result.inserted}<br>Updated: ${result.updated}<br>Errors: ${result.errors.length}`;
                    if (result.auto_enrolled && result.auto_enrolled.length > 0) {
                        msg += `<br><br>ℹ️ ${result.auto_enrolled.length} students were auto-enrolled.`;
                    }
                    Swal.fire({
                        title: 'Success!',
                        html: msg,
                        icon: 'success',
                        confirmButtonColor: '#0D3B2E'
                    });
                    
                    // Reset form
                    previewContainer.style.display = 'none';
                    parsedData = [];
                    fileInput.value = '';
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: result.message });
                }
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Network Error', text: error.message });
            } finally {
                publishBtn.disabled = false;
                publishBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Publish Grades';
            }
        });
    </script>

</body>
</html>
