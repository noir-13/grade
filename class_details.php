<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}
require 'check_profile.php';
require 'db_connect.php';

$class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$teacher_id = $_SESSION['user_id'];

// Fetch Class Details
$stmt = $conn->prepare("SELECT * FROM classes WHERE id = ? AND teacher_id = ?");
$stmt->bind_param("ii", $class_id, $teacher_id);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();

if (!$class) {
    echo "Class not found or unauthorized.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($class['subject_code']); ?> | KLD Grade System</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="verdantDesignSystem.css">
</head>
<body class="vds-bg-vapor">

    <?php include 'navbar_dashboard.php'; ?>

    <div class="vds-container py-5">
        
        <!-- Header -->
        <div class="mb-5">
            <a href="my_classes.php" class="vds-text-muted text-decoration-none mb-2 d-inline-block"><i class="bi bi-arrow-left me-1"></i> Back to Classes</a>
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h1 class="vds-h2 mb-1"><?php echo htmlspecialchars($class['subject_code']); ?> - <?php echo htmlspecialchars($class['section']); ?></h1>
                    <p class="vds-text-muted mb-0"><?php echo htmlspecialchars($class['subject_description']); ?></p>
                    <span class="vds-pill vds-pill-pass mt-2">
                        Code: <span class="font-monospace fw-bold ms-1"><?php echo htmlspecialchars($class['class_code']); ?></span>
                    </span>
                </div>
                <div>
                    <a href="grade_upload.php?class_id=<?php echo $class_id; ?>" class="vds-btn vds-btn-primary">
                        <i class="bi bi-upload me-2"></i>Upload Grades
                    </a>
                    <button class="vds-btn vds-btn-secondary ms-2" data-bs-toggle="modal" data-bs-target="#editClassModal">
                        <i class="bi bi-pencil me-2"></i>Edit Class
                    </button>
                    <button class="vds-btn vds-btn-secondary ms-2" onclick="toggleEditMode()" id="editGradesBtn">
                        <i class="bi bi-pencil-square me-2"></i>Edit Grades
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Search student name or ID...">
                </div>
            </div>
            <div class="col-md-3">
                <select id="remarksFilter" class="form-select">
                    <option value="">All Remarks</option>
                    <option value="Passed">Passed</option>
                    <option value="Failed">Failed</option>
                </select>
            </div>
            <div class="col-md-3">
                <select id="sortFilter" class="form-select">
                    <option value="name_asc">Name (A-Z)</option>
                    <option value="name_desc">Name (Z-A)</option>
                </select>
            </div>
        </div>

        <!-- Students List -->
        <div class="vds-card p-4">
            <h3 class="vds-h3 mb-4">Enrolled Students</h3>
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="studentsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Date Joined</th>
                            <th>Raw Grade</th>
                            <th>Transmuted</th>
                            <th>Remarks</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <div class="spinner-border text-success spinner-border-sm" role="status"></div> Loading...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Edit Class Modal -->
    <div class="modal fade" id="editClassModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0" style="border-radius: 24px;">
                <div class="modal-header border-0 p-4">
                    <h5 class="modal-title fw-bold">Edit Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 pt-0">
                    <form id="editClassForm">
                        <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                        <div class="vds-form-group">
                            <label class="vds-label">Subject Code <span class="text-danger">*</span></label>
                            <input type="text" name="subject_code" class="vds-input" value="<?php echo htmlspecialchars($class['subject_code']); ?>" required>
                        </div>
                        <div class="vds-form-group">
                            <label class="vds-label">Subject Description</label>
                            <input type="text" name="subject_description" class="vds-input" value="<?php echo htmlspecialchars($class['subject_description']); ?>">
                        </div>
                        <div class="vds-form-group">
                            <label class="vds-label">Section <span class="text-danger">*</span></label>
                            <input type="text" name="section" class="vds-input" value="<?php echo htmlspecialchars($class['section']); ?>" required>
                        </div>
                        <div class="vds-form-group">
                            <label class="vds-label">Semester</label>
                            <select name="semester" class="vds-input">
                                <option <?php echo $class['semester'] == '1st Sem 2024-2025' ? 'selected' : ''; ?>>1st Sem 2024-2025</option>
                                <option <?php echo $class['semester'] == '2nd Sem 2024-2025' ? 'selected' : ''; ?>>2nd Sem 2024-2025</option>
                                <option <?php echo $class['semester'] == 'Summer 2024' ? 'selected' : ''; ?>>Summer 2024</option>
                            </select>
                        </div>
                        <div class="vds-form-group">
                            <label class="vds-label">Units <span class="text-danger">*</span></label>
                            <input type="number" name="units" class="vds-input" value="<?php echo htmlspecialchars($class['units'] ?? 3); ?>" min="1" max="10" required>
                        </div>
                        <button type="submit" class="vds-btn vds-btn-primary w-100 mt-3">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer_dashboard.php'; ?>
    <script src="js/bootstrap.bundle.min.js"></script>

    <script>
        const classId = <?php echo $class_id; ?>;
        const tbody = document.querySelector('#studentsTable tbody');
        const editClassForm = document.getElementById('editClassForm');
        const editClassModal = new bootstrap.Modal(document.getElementById('editClassModal'));

        // Edit Class
        editClassForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const payload = Object.fromEntries(formData.entries());
            const btn = e.target.querySelector('button[type="submit"]');
            
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

            try {
                const res = await fetch('api.php?action=edit_class', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message);
                }
            } catch (err) {
                alert('Error updating class');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Save Changes';
            }
        });

        // Transmutation Logic
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
            if (raw >= 75) return ['3.00', 'Passed'];
            return ['5.00', 'Failed'];
        }

        let isEditMode = false;
        const editGradesBtn = document.getElementById('editGradesBtn');
        let allStudents = [];

        // Filter Elements
        const searchInput = document.getElementById('searchInput');
        const remarksFilter = document.getElementById('remarksFilter');
        const sortFilter = document.getElementById('sortFilter');

        searchInput.addEventListener('input', renderStudents);
        remarksFilter.addEventListener('change', renderStudents);
        sortFilter.addEventListener('change', renderStudents);

        function toggleEditMode() {
            isEditMode = !isEditMode;
            editGradesBtn.classList.toggle('vds-btn-primary');
            editGradesBtn.classList.toggle('vds-btn-secondary');
            editGradesBtn.innerHTML = isEditMode ? '<i class="bi bi-check-lg me-2"></i>Done Editing' : '<i class="bi bi-pencil-square me-2"></i>Edit Grades';
            renderStudents();
        }

        async function loadStudents() {
            try {
                const res = await fetch(`api.php?action=get_class_students&class_id=${classId}`);
                const data = await res.json();

                if (data.success) {
                    allStudents = data.students;
                    renderStudents();
                }
            } catch (err) {
                console.error(err);
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Failed to load students</td></tr>';
            }
        }

        function renderStudents() {
            let filtered = [...allStudents];

            // Search
            const search = searchInput.value.toLowerCase();
            if (search) {
                filtered = filtered.filter(s => 
                    s.full_name.toLowerCase().includes(search) || 
                    (s.school_id && s.school_id.toLowerCase().includes(search))
                );
            }

            // Remarks
            const remark = remarksFilter.value;
            if (remark) {
                filtered = filtered.filter(s => (s.remarks || '').includes(remark));
            }

            // Sort
            const sort = sortFilter.value;
            filtered.sort((a, b) => {
                if (sort === 'name_asc') return a.full_name.localeCompare(b.full_name);
                if (sort === 'name_desc') return b.full_name.localeCompare(a.full_name);
                return 0;
            });

            tbody.innerHTML = '';
            if (filtered.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            No students found.
                        </td>
                    </tr>
                `;
                return;
            }

            filtered.forEach(student => {
                const row = document.createElement('tr');
                const rawGradeVal = student.raw_grade !== null ? student.raw_grade : '';
                const gradeVal = student.grade !== null ? student.grade : '';
                const remarksVal = student.remarks || '';
                
                let rawGradeCell, gradeCell, remarksCell;

                if (isEditMode) {
                    rawGradeCell = `<input type="number" id="raw-grade-${student.id}" class="form-control form-control-sm" style="width: 80px" value="${rawGradeVal}" oninput="updateTransmuted(${student.id}, this.value)" onblur="saveGrade(${student.id})">`;
                    gradeCell = `<span id="transmuted-${student.id}" class="fw-bold text-muted">${gradeVal || '-'}</span>`;
                    remarksCell = `<input type="text" id="remarks-${student.id}" class="form-control form-control-sm" value="${remarksVal}" onblur="saveGrade(${student.id})">`;
                } else {
                    rawGradeCell = rawGradeVal ? `<span>${rawGradeVal}</span>` : '<span class="text-muted">-</span>';
                    gradeCell = gradeVal ? `<span class="fw-bold ${gradeVal <= 3.0 ? 'text-success' : 'text-danger'}">${gradeVal}</span>` : '<span class="text-muted">-</span>';
                    remarksCell = remarksVal ? `<span class="small">${remarksVal}</span>` : '<span class="text-muted">-</span>';
                }

                row.innerHTML = `
                    <td>${student.school_id || '-'}</td>
                    <td class="fw-bold">${student.full_name}</td>
                    <td>${student.email}</td>
                    <td>${new Date(student.joined_at).toLocaleDateString()}</td>
                    <td>${rawGradeCell}</td>
                    <td>${gradeCell}</td>
                    <td>${remarksCell}</td>
                    <td><span class="badge bg-success rounded-pill">Enrolled</span></td>
                `;
                tbody.appendChild(row);
            });
        }

        function updateTransmuted(studentId, raw) {
            const [transmuted, remarks] = transmuteGrade(raw);
            const transmutedEl = document.getElementById(`transmuted-${studentId}`);
            const remarksInput = document.getElementById(`remarks-${studentId}`);
            
            if (transmutedEl) transmutedEl.textContent = transmuted;
            // Only auto-update remarks if it's empty or matches standard passed/failed
            // For simplicity, let's just update it if the user hasn't manually typed something custom?
            // Or just update it always? The user can edit it back.
            if (remarksInput) remarksInput.value = remarks;
        }

        async function saveGrade(studentId) {
            const rawGradeInput = document.getElementById(`raw-grade-${studentId}`);
            const remarksInput = document.getElementById(`remarks-${studentId}`);
            
            const rawGrade = rawGradeInput ? rawGradeInput.value : '';
            const remarks = remarksInput ? remarksInput.value : '';

            if (!rawGrade && !remarks) return; 
            
            try {
                const res = await fetch('api.php?action=update_single_grade', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        class_id: classId,
                        student_id: studentId,
                        raw_grade: rawGrade,
                        remarks: remarks
                    })
                });
                const data = await res.json();
                if (!data.success) {
                    alert('Failed to save grade: ' + data.message);
                }
            } catch (err) {
                console.error(err);
                alert('Error saving grade');
            }
        }

        loadStudents();
    </script>
</body>
</html>
