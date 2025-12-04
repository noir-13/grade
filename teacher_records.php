<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Records | KLD Grade System</title>
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="verdantDesignSystem.css">
</head>
<body class="vds-bg-vapor">

    <?php include 'navbar_dashboard.php'; ?>

    <div class="vds-container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="teacher_dashboard.php" class="vds-text-muted text-decoration-none mb-2 d-inline-block"><i class="bi bi-arrow-left me-1"></i> Back to Dashboard</a>
                <h1 class="vds-h2">Class Records</h1>
                <p class="vds-text-muted">Manage and edit student grades.</p>
            </div>
            <div>
                <button class="vds-btn vds-btn-secondary" onclick="toggleEditMode()" id="editGradesBtn" disabled>
                    <i class="bi bi-pencil-square me-2"></i>Edit Grades
                </button>
            </div>
        </div>

        <!-- Controls -->
        <div class="vds-card p-4 mb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small text-muted">Select Class</label>
                    <select id="classSelect" class="form-select">
                        <option value="">Loading classes...</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Search</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Name or ID...">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Remarks</label>
                    <select id="remarksFilter" class="form-select">
                        <option value="">All</option>
                        <option value="Passed">Passed</option>
                        <option value="Failed">Failed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Sort</label>
                    <select id="sortFilter" class="form-select">
                        <option value="name_asc">Name (A-Z)</option>
                        <option value="name_desc">Name (Z-A)</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="vds-card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="vds-table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Raw Grade</th>
                            <th>Transmuted</th>
                            <th>Remarks</th>
                            <th class="text-end pe-4">Status</th>
                        </tr>
                    </thead>
                    <tbody id="studentsTableBody">
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">Please select a class to view records.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include 'footer_dashboard.php'; ?>

    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        let classes = [];
        let allStudents = [];
        let isEditMode = false;
        const editGradesBtn = document.getElementById('editGradesBtn');
        const classSelect = document.getElementById('classSelect');
        const tbody = document.getElementById('studentsTableBody');
        
        // Filters
        const searchInput = document.getElementById('searchInput');
        const remarksFilter = document.getElementById('remarksFilter');
        const sortFilter = document.getElementById('sortFilter');

        // Init
        document.addEventListener('DOMContentLoaded', loadClasses);
        classSelect.addEventListener('change', () => {
            if (classSelect.value) {
                loadStudents(classSelect.value);
                editGradesBtn.disabled = false;
            } else {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted">Please select a class.</td></tr>';
                editGradesBtn.disabled = true;
                allStudents = [];
            }
        });

        searchInput.addEventListener('input', renderStudents);
        remarksFilter.addEventListener('change', renderStudents);
        sortFilter.addEventListener('change', renderStudents);

        async function loadClasses() {
            try {
                const res = await fetch('api.php?action=get_classes');
                const data = await res.json();
                
                classSelect.innerHTML = '<option value="">Select a Class...</option>';
                
                if (data.success && data.classes.length > 0) {
                    classes = data.classes;
                    classes.forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.id;
                        opt.textContent = `${c.subject_code} - ${c.section} (${c.subject_description})`;
                        classSelect.appendChild(opt);
                    });
                } else {
                    classSelect.innerHTML = '<option value="">No classes found</option>';
                }
            } catch (e) {
                console.error(e);
                classSelect.innerHTML = '<option value="">Error loading classes</option>';
            }
        }

        async function loadStudents(classId) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted">Loading students...</td></tr>';
            try {
                const res = await fetch(`api.php?action=get_class_students&class_id=${classId}`);
                const data = await res.json();

                if (data.success) {
                    allStudents = data.students;
                    renderStudents();
                } else {
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted">No students found.</td></tr>';
                    allStudents = [];
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
                tbody.innerHTML = `<tr><td colspan="7" class="text-center py-5 text-muted">No students match your filters.</td></tr>`;
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
                    <td class="ps-4">${student.school_id || '-'}</td>
                    <td class="fw-bold">${student.full_name}</td>
                    <td>${student.email}</td>
                    <td>${rawGradeCell}</td>
                    <td>${gradeCell}</td>
                    <td>${remarksCell}</td>
                    <td class="text-end pe-4"><span class="badge bg-success rounded-pill">Enrolled</span></td>
                `;
                tbody.appendChild(row);
            });
        }

        function toggleEditMode() {
            if (!classSelect.value) return;
            isEditMode = !isEditMode;
            editGradesBtn.classList.toggle('vds-btn-primary');
            editGradesBtn.classList.toggle('vds-btn-secondary');
            editGradesBtn.innerHTML = isEditMode ? '<i class="bi bi-check-lg me-2"></i>Done Editing' : '<i class="bi bi-pencil-square me-2"></i>Edit Grades';
            renderStudents();
        }

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

        function updateTransmuted(studentId, raw) {
            const [transmuted, remarks] = transmuteGrade(raw);
            const transmutedEl = document.getElementById(`transmuted-${studentId}`);
            const remarksEl = document.getElementById(`remarks-${studentId}`);
            
            if (transmutedEl) transmutedEl.textContent = transmuted;
            if (remarksEl && remarksEl.value === '') { // Only auto-fill if empty? Or always? 
                // Let's match previous logic: update remarks if it matches a standard status or if we want to force it.
                // Actually, let's just update it if the user hasn't typed a custom remark? 
                // For simplicity and consistency with class_details.php, let's update it.
                remarksEl.value = remarks;
            }
        }

        async function saveGrade(studentId) {
            const rawInput = document.getElementById(`raw-grade-${studentId}`);
            const remarksInput = document.getElementById(`remarks-${studentId}`);
            
            if (!rawInput || !remarksInput) return;

            const rawGrade = rawInput.value;
            const remarks = remarksInput.value;
            const classId = classSelect.value;

            try {
                const res = await fetch('api.php?action=update_single_grade', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        student_id: studentId,
                        class_id: classId,
                        raw_grade: rawGrade,
                        remarks: remarks,
                        csrf_token: document.querySelector('meta[name="csrf-token"]').content
                    })
                });
                const data = await res.json();

                if (data.success) {
                    // Update local data
                    const student = allStudents.find(s => s.id == studentId);
                    if (student) {
                        student.raw_grade = rawGrade;
                        student.remarks = remarks;
                        // Also update transmuted grade in local data if needed, but we rely on server or re-calc
                        const [t, r] = transmuteGrade(rawGrade);
                        student.grade = t === '-' ? null : t;
                    }
                    console.log('Saved');
                } else {
                    console.error('Save failed', data.message);
                }
            } catch (e) {
                console.error(e);
            }
        }
    </script>
</body>
</html>
