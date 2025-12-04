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
// Fetch Programs
$programs = [];
$progStmt = $conn->prepare("SELECT * FROM programs ORDER BY code ASC");
$progStmt->execute();
$progRes = $progStmt->get_result();
while ($row = $progRes->fetch_assoc()) {
    $programs[] = $row;
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                <select id="periodFilter" class="form-select">
                    <option value="midterm">Midterm Grade</option>
                    <option value="final">Final Grade</option>
                    <option value="grade" selected>Semestral Grade</option>
                </select>
            </div>
            <div class="col-md-2">
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
                            <label class="vds-label">Program Restriction <span class="text-danger">*</span></label>
                            <select name="program_id" class="vds-select" required>
                                <option value="">Select Program</option>
                                <?php foreach ($programs as $prog): ?>
                                    <option value="<?php echo $prog['id']; ?>" <?php echo ($class['program_id'] == $prog['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($prog['code'] . ' - ' . $prog['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted" style="font-size: 0.8rem;">Only students from this program can enroll.</small>
                        </div>
                        <div class="vds-form-group">
                            <label class="vds-label">Subject Code <span class="text-danger">*</span></label>
                            <input type="text" name="subject_code" class="vds-input" value="<?php echo htmlspecialchars($class['subject_code']); ?>" maxlength="50" required>
                        </div>
                        <div class="vds-form-group">
                            <label class="vds-label">Subject Description</label>
                            <input type="text" name="subject_description" class="vds-input" value="<?php echo htmlspecialchars($class['subject_description']); ?>">
                        </div>
                        <div class="vds-form-group">
                            <label class="vds-label">Section <span class="text-danger">*</span></label>
                            <input type="text" name="section" class="vds-input" value="<?php echo htmlspecialchars($class['section']); ?>" maxlength="50" required>
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
                        <div class="vds-form-group">
                            <label class="vds-label">Schedule</label>
                            <div class="d-flex gap-2">
                                <select class="vds-select sched-day" required>
                                    <option value="">Day</option>
                                    <option value="Mon">Mon</option>
                                    <option value="Tue">Tue</option>
                                    <option value="Wed">Wed</option>
                                    <option value="Thu">Thu</option>
                                    <option value="Fri">Fri</option>
                                    <option value="Sat">Sat</option>
                                </select>
                                <select class="vds-select sched-start" required></select>
                                <select class="vds-select sched-end" required></select>
                            </div>
                            <input type="hidden" name="schedule" value="<?php echo htmlspecialchars($class['schedule'] ?? ''); ?>">
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
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message,
                        confirmButtonColor: '#0D3B2E'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message,
                        confirmButtonColor: '#d33'
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error updating class',
                    confirmButtonColor: '#d33'
                });
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Save Changes';
            }
        });

        let allStudents = [];

        // Filter Elements
        const searchInput = document.getElementById('searchInput');
        const remarksFilter = document.getElementById('remarksFilter');
        const periodFilter = document.getElementById('periodFilter');
        const sortFilter = document.getElementById('sortFilter');

        searchInput.addEventListener('input', renderStudents);
        remarksFilter.addEventListener('change', renderStudents);
        periodFilter.addEventListener('change', renderStudents);
        sortFilter.addEventListener('change', renderStudents);



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

                rawGradeCell = rawGradeVal ? `<span>${rawGradeVal}</span>` : '<span class="text-muted">-</span>';
                gradeCell = gradeVal ? `<span class="fw-bold ${gradeVal <= 3.0 ? 'text-success' : 'text-danger'}">${gradeVal}</span>` : '<span class="text-muted">-</span>';
                remarksCell = remarksVal ? `<span class="small">${remarksVal}</span>` : '<span class="text-muted">-</span>';

                row.innerHTML = `
                    <td>${student.school_id || '-'}</td>
                    <td class="fw-bold">${student.full_name}</td>
                    <td>${student.email}</td>
                    <td>${new Date(student.joined_at).toLocaleDateString()}</td>
                    <td>${rawGradeCell}</td>
                    <td>${gradeCell}</td>
                    <td>${remarksCell}</td>
                    <td><span class="badge bg-success rounded-pill">Enrolled</span></td>
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
                            <input type="text" name="subject_code" class="vds-input" value="<?php echo htmlspecialchars($class['subject_code']); ?>" maxlength="50" required>
                        </div>
                        <div class="vds-form-group">
                            <label class="vds-label">Subject Description</label>
                            <input type="text" name="subject_description" class="vds-input" value="<?php echo htmlspecialchars($class['subject_description']); ?>">
                        </div>
                        <div class="vds-form-group">
                            <label class="vds-label">Section <span class="text-danger">*</span></label>
                            <input type="text" name="section" class="vds-input" value="<?php echo htmlspecialchars($class['section']); ?>" maxlength="50" required>
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

        let allStudents = [];

        // Filter Elements
        const searchInput = document.getElementById('searchInput');
        const remarksFilter = document.getElementById('remarksFilter');
        const sortFilter = document.getElementById('sortFilter');

        searchInput.addEventListener('input', renderStudents);
        remarksFilter.addEventListener('change', renderStudents);
        sortFilter.addEventListener('change', renderStudents);



        async function loadStudents() {
            try {
                const res = await fetch(`api.php?action=get_class_students&class_id=${classId}&t=${new Date().getTime()}`);
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
                const period = periodFilter.value;
                let gradeVal = null;

                if (period === 'midterm') gradeVal = student.midterm;
                else if (period === 'final') gradeVal = student.final;
                else gradeVal = student.grade;

                gradeVal = gradeVal !== null ? parseFloat(gradeVal) : null;
                
                // Auto-calculate remarks
                let calculatedRemarks = '';
                let remarksClass = '';
                
                if (gradeVal !== null) {
                    if (gradeVal <= 3.0) {
                        calculatedRemarks = 'Passed';
                        remarksClass = 'text-success fw-bold';
                    } else {
                        calculatedRemarks = 'Failed';
                        remarksClass = 'text-danger fw-bold';
                    }
                }

                // Append existing remarks if any (e.g. notes)
                // If existing remarks is just "Passed" or "Failed", ignore it to avoid duplication if we want strict auto.
                // But user might have custom notes. Let's show custom notes if they differ from calculated.
                // For now, let's just show calculated + notes if available.
                
                let displayRemarks = calculatedRemarks;
                if (student.remarks && student.remarks !== 'Passed' && student.remarks !== 'Failed') {
                     displayRemarks += ` <small class="text-muted">(${student.remarks})</small>`;
                }

                let rawGradeCell, gradeCell, remarksCell;

                rawGradeCell = rawGradeVal ? `<span>${rawGradeVal}</span>` : '<span class="text-muted">-</span>';
                gradeCell = gradeVal ? `<span class="fw-bold ${gradeVal <= 3.0 ? 'text-success' : 'text-danger'}">${gradeVal.toFixed(2)}</span>` : '<span class="text-muted">-</span>';
                remarksCell = displayRemarks ? `<span class="${remarksClass}">${displayRemarks}</span>` : '<span class="text-muted">-</span>';

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



        loadStudents();

        // Schedule Logic
        function generateTimeOptions() {
            const times = [];
            const startHour = 7; // 7 AM
            const endHour = 19; // 7 PM
            
            for (let h = startHour; h <= endHour; h++) {
                for (let m = 0; m < 60; m += 30) {
                    if (h === endHour && m > 0) break; 
                    
                    const period = h >= 12 ? 'PM' : 'AM';
                    let displayHour = h > 12 ? h - 12 : h;
                    if (displayHour === 0) displayHour = 12;
                    
                    const minStr = m === 0 ? '00' : '30';
                    const timeStr = `${displayHour}:${minStr} ${period}`;
                    times.push(timeStr);
                }
            }
            return times;
        }

        const timeOptions = generateTimeOptions();
        
        function populateTimeSelects() {
            document.querySelectorAll('.sched-start, .sched-end').forEach(select => {
                const isEnd = select.classList.contains('sched-end');
                select.innerHTML = `<option value="">${isEnd ? 'End' : 'Start'}</option>`;
                timeOptions.forEach(time => {
                    const opt = document.createElement('option');
                    opt.value = time;
                    opt.textContent = time;
                    select.appendChild(opt);
                });
            });
        }
        
        populateTimeSelects();

        function updateScheduleInput(form) {
            const day = form.querySelector('.sched-day').value;
            const start = form.querySelector('.sched-start').value;
            const end = form.querySelector('.sched-end').value;
            const hiddenInput = form.querySelector('input[name="schedule"]');
            
            if (day && start && end) {
                hiddenInput.value = `${day} ${start}-${end}`;
            } else {
                hiddenInput.value = '';
            }
        }

        const editForm = document.getElementById('editClassForm');
        const selects = editForm.querySelectorAll('.sched-day, .sched-start, .sched-end');
        selects.forEach(sel => {
            sel.addEventListener('change', () => updateScheduleInput(editForm));
        });

        // Parse existing schedule
        const existingSchedule = editForm.querySelector('input[name="schedule"]').value;
        if (existingSchedule) {
            const parts = existingSchedule.split(' ');
            if (parts.length >= 2) {
                const day = parts[0];
                const timePart = existingSchedule.substring(day.length + 1);
                const times = timePart.split('-');
                if (times.length === 2) {
                    const start = times[0];
                    const end = times[1];
                    
                    const daySel = editForm.querySelector('.sched-day');
                    const startSel = editForm.querySelector('.sched-start');
                    const endSel = editForm.querySelector('.sched-end');

                    if (daySel) daySel.value = day;
                    if (startSel) startSel.value = start;
                    if (endSel) endSel.value = end;
                }
            }
        }
    </script>
</body>
</html>
