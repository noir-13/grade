<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}
require 'check_profile.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Classes | KLD Grade System</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="verdantDesignSystem.css">
    <style>
        .class-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid transparent;
            height: 100%;
        }
        .class-card:hover {
            transform: translateY(-5px);
            border-color: var(--vds-sage);
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }
        .class-header {
            height: 100px;
            background: linear-gradient(135deg, var(--vds-forest), #0f4c3a);
            border-radius: 16px 16px 0 0;
            padding: 1.5rem;
            color: white;
            position: relative;
        }
        .class-code-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255,255,255,0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-family: monospace;
            font-size: 0.9rem;
            backdrop-filter: blur(4px);
        }
    </style>
</head>
<body class="vds-bg-vapor">

    <?php include 'navbar_dashboard.php'; ?>

    <div class="vds-container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="teacher_dashboard.php" class="vds-text-muted text-decoration-none mb-2 d-inline-block"><i class="bi bi-arrow-left me-1"></i> Back to Dashboard</a>
                <h1 class="vds-h2">My Classes</h1>
                <p class="vds-text-muted">Manage your sections and students.</p>
            </div>
            <button class="vds-btn vds-btn-primary" data-bs-toggle="modal" data-bs-target="#createClassModal">
                <i class="bi bi-plus-lg me-2"></i>Create Class
            </button>
        </div>

        <!-- Classes Grid -->
        <div class="row g-4" id="classesGrid">
            <!-- JS will populate this -->
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>

    </div>

    <!-- Create Class Modal -->
    <div class="modal fade" id="createClassModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0" style="border-radius: 24px;">
                <div class="modal-header border-0 p-4">
                    <h5 class="modal-title fw-bold">Create New Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 pt-0">
                    <form id="createClassForm">
                        <div class="vds-form-group">
                            <label class="vds-label">Subject Code <span class="text-danger">*</span></label>
                            <input type="text" name="subject_code" class="vds-input" placeholder="e.g. IT 101" required>
                        </div>
                        <div class="vds-form-group">
                            <label class="vds-label">Subject Description</label>
                            <input type="text" name="subject_description" class="vds-input" placeholder="e.g. Intro to Computing">
                        </div>
                        <div class="vds-form-group">
                            <label class="vds-label">Section <span class="text-danger">*</span></label>
                            <input type="text" name="section" class="vds-input" placeholder="e.g. BSIS 1-A" required>
                        </div>
                        <div class="vds-form-group">
                            <label class="vds-label">Semester</label>
                            <select name="semester" class="vds-input">
                                <option>1st Sem 2024-2025</option>
                                <option>2nd Sem 2024-2025</option>
                                <option>Summer 2024</option>
                            </select>
                        </div>
                        <div class="vds-form-group">
                            <label class="vds-label">Units <span class="text-danger">*</span></label>
                            <input type="number" name="units" class="vds-input" value="3" min="1" max="10" required>
                        </div>
                        <button type="submit" class="vds-btn vds-btn-primary w-100 mt-3">Create Class</button>
                    </form>
                </div>
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
                        <input type="hidden" name="class_id">
                        <div class="vds-form-group">
                            <label class="vds-label">Subject Code <span class="text-danger">*</span></label>
                            <input type="text" name="subject_code" class="vds-input" required>
                        </div>
                        <div class="vds-form-group">
                            <label class="vds-label">Subject Description</label>
                            <input type="text" name="subject_description" class="vds-input">
                        </div>
                        <div class="vds-form-group">
                            <label class="vds-label">Section <span class="text-danger">*</span></label>
                            <input type="text" name="section" class="vds-input" required>
                        </div>
                        <div class="vds-form-group">
                            <label class="vds-label">Semester</label>
                            <select name="semester" class="vds-input">
                                <option>1st Sem 2024-2025</option>
                                <option>2nd Sem 2024-2025</option>
                                <option>Summer 2024</option>
                            </select>
                        </div>
                        <div class="vds-form-group">
                            <label class="vds-label">Units <span class="text-danger">*</span></label>
                            <input type="number" name="units" class="vds-input" min="1" max="10" required>
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
        const classesGrid = document.getElementById('classesGrid');
        const createClassForm = document.getElementById('createClassForm');
        const createClassModal = new bootstrap.Modal(document.getElementById('createClassModal'));
        const editClassForm = document.getElementById('editClassForm');
        const editClassModal = new bootstrap.Modal(document.getElementById('editClassModal'));

        // Load Classes
        async function loadClasses() {
            try {
                const res = await fetch('api.php?action=get_classes');
                const data = await res.json();
                
                if (data.success) {
                    classesGrid.innerHTML = '';
                    if (data.classes.length === 0) {
                        classesGrid.innerHTML = `
                            <div class="col-12 text-center py-5">
                                <i class="bi bi-journal-x display-1 text-muted opacity-25"></i>
                                <h3 class="vds-h3 mt-3 text-muted">No classes found</h3>
                                <p class="text-muted">Create your first class to get started.</p>
                            </div>
                        `;
                        return;
                    }

                    data.classes.forEach(cls => {
                        const col = document.createElement('div');
                        col.className = 'col-md-4 col-lg-3';
                        col.innerHTML = `
                            <div class="vds-card p-0 class-card overflow-hidden" onclick="window.location.href='class_details.php?id=${cls.id}'">
                                <div class="class-header">
                                    <div class="class-code-badge" title="Class Code: Share this with students">
                                        <i class="bi bi-key me-1"></i>${cls.class_code}
                                    </div>
                                    <button class="btn btn-sm btn-light position-absolute top-0 end-0 m-3 rounded-circle" 
                                            style="width: 32px; height: 32px; z-index: 10;"
                                            onclick="event.stopPropagation(); openEditModal(${JSON.stringify(cls).replace(/"/g, '&quot;')})">
                                        <i class="bi bi-pencil-fill small text-success"></i>
                                    </button>
                                    <h4 class="mb-1 text-truncate" title="${cls.subject_code}">${cls.subject_code}</h4>
                                    <div class="small opacity-75 text-truncate">${cls.section}</div>
                                </div>
                                <div class="p-3">
                                    <p class="text-muted small mb-3 text-truncate">${cls.subject_description || 'No description'}</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="vds-pill vds-pill-pass small">
                                            <i class="bi bi-people-fill me-1"></i>${cls.student_count} Students
                                        </span>
                                    </div>
                                </div>
                            </div>
                        `;
                        classesGrid.appendChild(col);
                    });
                }
            } catch (err) {
                console.error(err);
                classesGrid.innerHTML = '<div class="col-12 text-center text-danger">Failed to load classes</div>';
            }
        }

        // Create Class
        createClassForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const payload = Object.fromEntries(formData.entries());
            const btn = e.target.querySelector('button[type="submit"]');
            
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Creating...';

            try {
                const res = await fetch('api.php?action=create_class', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (data.success) {
                    createClassModal.hide();
                    createClassForm.reset();
                    loadClasses();
                    alert(`Class Created! Code: ${data.class_code}`);
                } else {
                    alert(data.message);
                }
            } catch (err) {
                alert('Error creating class');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Create Class';
            }
        });

        loadClasses();
    </script>
</body>
</html>
