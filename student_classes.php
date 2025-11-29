<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
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
        .teacher-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            color: var(--vds-forest);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            position: absolute;
            bottom: -20px;
            right: 1.5rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="vds-bg-vapor">

    <?php include 'navbar_dashboard.php'; ?>

    <div class="vds-container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="student_dashboard.php" class="vds-text-muted text-decoration-none mb-2 d-inline-block"><i class="bi bi-arrow-left me-1"></i> Back to Dashboard</a>
                <h1 class="vds-h2">My Classes</h1>
                <p class="vds-text-muted">View your enrolled subjects.</p>
            </div>
            <button class="vds-btn vds-btn-primary" data-bs-toggle="modal" data-bs-target="#joinClassModal">
                <i class="bi bi-plus-lg me-2"></i>Join Class
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

    <!-- Join Class Modal -->
    <div class="modal fade" id="joinClassModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0" style="border-radius: 24px;">
                <div class="modal-header border-0 p-4">
                    <h5 class="modal-title fw-bold">Join Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 pt-0">
                    <p class="text-muted mb-4">Ask your teacher for the class code, then enter it here.</p>
                    <form id="joinClassForm">
                        <div class="vds-form-group">
                            <label class="vds-label">Class Code</label>
                            <input type="text" name="class_code" class="vds-input text-uppercase" placeholder="e.g. X7Y2Z9" maxlength="6" required style="letter-spacing: 2px; font-weight: bold;">
                        </div>
                        <button type="submit" class="vds-btn vds-btn-primary w-100 mt-3">Join</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer_dashboard.php'; ?>
    <script src="js/bootstrap.bundle.min.js"></script>

    <script>
        const classesGrid = document.getElementById('classesGrid');
        const joinClassForm = document.getElementById('joinClassForm');
        const joinClassModal = new bootstrap.Modal(document.getElementById('joinClassModal'));

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
                                <h3 class="vds-h3 mt-3 text-muted">No classes enrolled</h3>
                                <p class="text-muted">Click "Join Class" to enroll in a subject.</p>
                            </div>
                        `;
                        return;
                    }

                    data.classes.forEach(cls => {
                        const col = document.createElement('div');
                        col.className = 'col-md-4 col-lg-3';
                        const initials = cls.teacher_name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                        
                        col.innerHTML = `
                            <div class="vds-card p-0 class-card overflow-hidden">
                                <div class="class-header">
                                    <h4 class="mb-1 text-truncate" title="${cls.subject_code}">${cls.subject_code}</h4>
                                    <div class="small opacity-75 text-truncate">${cls.section}</div>
                                    <div class="teacher-avatar" title="${cls.teacher_name}">
                                        ${initials}
                                    </div>
                                </div>
                                <div class="p-3 pt-4">
                                    <p class="text-muted small mb-2 text-truncate">${cls.subject_description || 'No description'}</p>
                                    <p class="small text-muted mb-0"><i class="bi bi-person me-1"></i> ${cls.teacher_name}</p>
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

        // Join Class
        joinClassForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const payload = Object.fromEntries(formData.entries());
            const btn = e.target.querySelector('button[type="submit"]');
            
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Joining...';

            try {
                const res = await fetch('api.php?action=join_class', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (data.success) {
                    joinClassModal.hide();
                    joinClassForm.reset();
                    loadClasses();
                    alert(data.message);
                } else {
                    alert(data.message);
                }
            } catch (err) {
                alert('Error joining class');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Join';
            }
        });

        loadClasses();
    </script>
</body>
</html>
