<?php
session_start();
require 'db_connect.php';
require 'email_helper.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$full_name = $_SESSION['full_name'];

// Handle Fetch Students for Class (AJAX)
if ($role === 'teacher' && isset($_GET['action']) && $_GET['action'] === 'get_students' && isset($_GET['class_id'])) {
    $class_id = intval($_GET['class_id']);
    $stmt = $conn->prepare("
        SELECT u.id, u.full_name 
        FROM enrollments e 
        JOIN users u ON e.student_id = u.id 
        WHERE e.class_id = ?
        ORDER BY u.full_name ASC
    ");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    echo json_encode($students);
    exit();
}

// Handle Post Announcement (Teacher Only)
if ($role === 'teacher' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_announcement'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $class_id = !empty($_POST['class_id']) ? intval($_POST['class_id']) : NULL;
    $priority = $_POST['priority'];

    if (empty($title) || empty($content)) {
        $error = "Title and Content are required.";
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO announcements (class_id, teacher_id, title, content, priority) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iisss", $class_id, $user_id, $title, $content, $priority);
            $stmt->execute();
            $announcement_id = $conn->insert_id;

            $recipients_list = []; // For email
            
            // Handle Recipients
            $recipient_type = $_POST['recipient_type'] ?? 'all';
            if ($class_id && $recipient_type === 'selected' && !empty($_POST['student_ids'])) {
                $student_ids = $_POST['student_ids'];
                $stmtR = $conn->prepare("INSERT INTO announcement_recipients (announcement_id, student_id) VALUES (?, ?)");
                foreach ($student_ids as $sid) {
                    $sid = intval($sid);
                    $stmtR->bind_param("ii", $announcement_id, $sid);
                    $stmtR->execute();
                }
                
                // Fetch emails for selected students
                if (count($student_ids) > 0) {
                    $ids_placeholder = implode(',', array_fill(0, count($student_ids), '?'));
                    $stmtEmail = $conn->prepare("SELECT email FROM users WHERE id IN ($ids_placeholder)");
                    $stmtEmail->bind_param(str_repeat('i', count($student_ids)), ...$student_ids);
                    $stmtEmail->execute();
                    $resEmail = $stmtEmail->get_result();
                    while ($rowE = $resEmail->fetch_assoc()) {
                        if (!empty($rowE['email'])) $recipients_list[] = $rowE['email'];
                    }
                }
            } elseif ($class_id) {
                // All students in class
                $stmtS = $conn->prepare("SELECT u.email FROM enrollments e JOIN users u ON e.student_id = u.id WHERE e.class_id = ?");
                $stmtS->bind_param("i", $class_id);
                $stmtS->execute();
                $resS = $stmtS->get_result();
                while ($rowS = $resS->fetch_assoc()) {
                    if (!empty($rowS['email'])) {
                        $recipients_list[] = $rowS['email'];
                    }
                }
            }

            $conn->commit();
            $success = "Announcement posted successfully!";

            // Send Email Notifications
            if (!empty($recipients_list)) {
                $emailBody = "
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
                            .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
                            .header { background-color: #0D3B2E; color: #ffffff; padding: 20px; text-align: center; }
                            .header h1 { margin: 0; font-size: 24px; }
                            .content { padding: 30px; }
                            .announcement-title { color: #0D3B2E; font-size: 20px; margin-bottom: 15px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
                            .announcement-text { font-size: 16px; color: #555; white-space: pre-wrap; }
                            .footer { background-color: #f9f9f9; padding: 15px; text-align: center; font-size: 12px; color: #888; border-top: 1px solid #eee; }
                            .btn { display: inline-block; padding: 10px 20px; background-color: #0D3B2E; color: #ffffff; text-decoration: none; border-radius: 5px; margin-top: 20px; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h1>KLD Grade System</h1>
                            </div>
                            <div class='content'>
                                <p>Dear Student,</p>
                                <p>A new announcement has been posted for your class.</p>
                                
                                <h2 class='announcement-title'>$title</h2>
                                <div class='announcement-text'>$content</div>
                                
                                <center>
                                    <a href='http://localhost/grade/login.php' class='btn'>Log in to Portal</a>
                                </center>
                            </div>
                            <div class='footer'>
                                <p>&copy; " . date('Y') . " KLD Grade System. All rights reserved.</p>
                                <p>This is an automated message, please do not reply.</p>
                            </div>
                        </div>
                    </body>
                    </html>
                ";
                // Send batch email
                sendEmailBCC($recipients_list, "New Announcement: $title", $emailBody);
            }

        } catch (Exception $e) {
            $conn->rollback();
            $error = "Failed to post announcement: " . $e->getMessage();
        }
    }
}

// Handle Mark as Read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $announcement_id = intval($_POST['announcement_id']);
    $stmt = $conn->prepare("INSERT IGNORE INTO announcement_reads (user_id, announcement_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $announcement_id);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit();
}

// Fetch Announcements
$announcements = [];
if ($role === 'student') {
    // Student View: 
    // 1. General announcements (class_id IS NULL) BUT only from teachers they have classes with.
    // 2. Class-specific announcements (class_id IN enrolled classes).
    //    AND (No specific recipients defined OR Student is in recipients)
    $stmt = $conn->prepare("
        SELECT a.*, u.full_name as teacher_name, c.subject_code, c.section,
               CASE WHEN ar.id IS NOT NULL THEN 1 ELSE 0 END as is_read
        FROM announcements a 
        LEFT JOIN users u ON a.teacher_id = u.id 
        LEFT JOIN classes c ON a.class_id = c.id
        LEFT JOIN announcement_reads ar ON a.id = ar.announcement_id AND ar.user_id = ?
        WHERE 
            (
                -- General Announcements from my teachers
                (a.class_id IS NULL AND a.teacher_id IN (
                    SELECT DISTINCT teacher_id FROM classes 
                    JOIN enrollments ON classes.id = enrollments.class_id 
                    WHERE enrollments.student_id = ?
                ))
            )
            OR 
            (
                -- Class Announcements I am enrolled in
                a.class_id IN (SELECT class_id FROM enrollments WHERE student_id = ?)
                AND (
                    -- Either no specific recipients (sent to all)
                    NOT EXISTS (SELECT 1 FROM announcement_recipients WHERE announcement_id = a.id)
                    -- OR I am a recipient
                    OR EXISTS (SELECT 1 FROM announcement_recipients WHERE announcement_id = a.id AND student_id = ?)
                )
            )
        ORDER BY a.created_at DESC
    ");
    $stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
} elseif ($role === 'teacher') {
    $stmt = $conn->prepare("
        SELECT a.*, u.full_name as teacher_name, c.subject_code, c.section, 1 as is_read
        FROM announcements a 
        LEFT JOIN users u ON a.teacher_id = u.id 
        LEFT JOIN classes c ON a.class_id = c.id
        WHERE a.teacher_id = ?
        ORDER BY a.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
} else {
    // Admin View: See all
    $stmt = $conn->prepare("
        SELECT a.*, u.full_name as teacher_name, c.subject_code, c.section, 1 as is_read
        FROM announcements a 
        LEFT JOIN users u ON a.teacher_id = u.id 
        LEFT JOIN classes c ON a.class_id = c.id
        ORDER BY a.created_at DESC
    ");
}

if (isset($stmt)) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
}

// Fetch Classes for Teacher Dropdown
$teacher_classes = [];
if ($role === 'teacher') {
    $stmtC = $conn->prepare("SELECT id, subject_code, section FROM classes WHERE teacher_id = ? ORDER BY created_at DESC");
    $stmtC->bind_param("i", $user_id);
    $stmtC->execute();
    $resC = $stmtC->get_result();
    while ($rowC = $resC->fetch_assoc()) {
        $teacher_classes[] = $rowC;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements | KLD Grade System</title>
    <link rel="icon" type="image/png" href="assets/logo2.png">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="verdantDesignSystem.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .announcement-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .announcement-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .priority-high { border-left-color: #ef4444; }
        .priority-urgent { border-left-color: #ef4444; background-color: #fef2f2; }
        .priority-normal { border-left-color: var(--vds-forest); }
    </style>
</head>
<body class="vds-bg-vapor">

    <?php include 'navbar_dashboard.php'; ?>

    <div class="vds-container py-5">
        
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <span class="vds-pill mb-2" style="background: var(--vds-sage); color: var(--vds-forest);">News & Updates</span>
                <h1 class="vds-h2">Announcements</h1>
            </div>
            <div class="d-flex gap-2">
                <?php if ($role === 'teacher'): ?>
                <button class="vds-btn vds-btn-primary" data-bs-toggle="modal" data-bs-target="#postModal">
                    <i class="bi bi-plus-lg me-2"></i>Post Announcement
                </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4">
            <?php if (count($announcements) > 0): ?>
                <?php foreach ($announcements as $ann): ?>
                    <?php 
                        $priorityClass = 'priority-normal';
                        if ($ann['priority'] === 'high') $priorityClass = 'priority-high';
                        if ($ann['priority'] === 'urgent') $priorityClass = 'priority-urgent';
                        
                        $badgeClass = 'vds-pill-pass';
                        if ($ann['priority'] === 'high') $badgeClass = 'vds-pill-warn';
                        if ($ann['priority'] === 'urgent') $badgeClass = 'vds-pill-fail';
                    ?>
                    <div class="col-12">
                        <div class="vds-card p-4 announcement-card <?php echo $priorityClass; ?>" 
                             onclick="markAsRead(this, <?php echo $ann['id']; ?>)"
                             style="cursor: pointer;">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <?php if (!$ann['is_read']): ?>
                                        <span class="vds-pill vds-pill-fail" id="badge-<?php echo $ann['id']; ?>">NEW</span>
                                    <?php endif; ?>
                                    <span class="vds-pill <?php echo $badgeClass; ?>"><?php echo ucfirst($ann['priority']); ?></span>
                                    <span class="vds-text-muted small">
                                        <i class="bi bi-clock me-1"></i><?php echo date('M d, Y h:i A', strtotime($ann['created_at'])); ?>
                                    </span>
                                    <?php if ($ann['subject_code']): ?>
                                        <span class="vds-pill" style="background: #e0f2fe; color: #0284c7;">
                                            <?php echo htmlspecialchars($ann['subject_code'] . ' - ' . $ann['section']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="vds-pill" style="background: #f3f4f6; color: #6b7280;">General</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted small">
                                    Posted by: <?php echo htmlspecialchars($ann['teacher_name']); ?>
                                </div>
                            </div>
                            <h3 class="vds-h3 mb-2"><?php echo htmlspecialchars($ann['title']); ?></h3>
                            <p class="vds-text-lead mb-0" style="font-size: 1rem;"><?php echo nl2br(htmlspecialchars($ann['content'])); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-megaphone display-1 text-muted mb-3" style="opacity: 0.3;"></i>
                    <p class="vds-text-muted">No announcements yet.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Post Announcement Modal -->
    <?php if ($role === 'teacher'): ?>
    <div class="modal fade" id="postModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content vds-card">
                <div class="modal-header border-0">
                    <h5 class="modal-title vds-h3">Post Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="vds-form-group">
                            <label class="vds-label">Title</label>
                            <input type="text" name="title" class="vds-input" required>
                        </div>
                        <div class="vds-form-group">
                            <label class="vds-label">Class (Optional)</label>
                            <select name="class_id" id="classSelect" class="vds-select" onchange="toggleRecipients()">
                                <option value="">General (All Students)</option>
                                <?php foreach ($teacher_classes as $cls): ?>
                                    <option value="<?php echo $cls['id']; ?>">
                                        <?php echo htmlspecialchars($cls['subject_code'] . ' - ' . $cls['section']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="recipientSection" class="vds-form-group" style="display: none;">
                            <label class="vds-label">Recipients</label>
                            <div class="d-flex gap-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="recipient_type" value="all" id="recipientAll" checked onchange="toggleStudentList()">
                                    <label class="form-check-label" for="recipientAll">All Students in Class</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="recipient_type" value="selected" id="recipientSelected" onchange="toggleStudentList()">
                                    <label class="form-check-label" for="recipientSelected">Selected Students</label>
                                </div>
                            </div>
                            
                            <div id="studentListContainer" class="border rounded p-3" style="display: none; max-height: 200px; overflow-y: auto;">
                                <div class="text-center text-muted"><small>Select a class to load students...</small></div>
                            </div>
                        </div>
                        <div class="vds-form-group">
                            <label class="vds-label">Priority</label>
                            <select name="priority" class="vds-select">
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="vds-form-group">
                            <label class="vds-label">Content</label>
                            <textarea name="content" class="vds-input" rows="5" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="vds-btn vds-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="post_announcement" class="vds-btn vds-btn-primary">Post</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php include 'footer_dashboard.php'; ?>
    <script src="js/bootstrap.bundle.min.js"></script>
    
    <?php if(isset($success)): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: '<?php echo $success; ?>',
            confirmButtonColor: '#0D3B2E'
        });
    </script>
    <?php endif; ?>

    <?php if(isset($error)): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '<?php echo $error; ?>',
            confirmButtonColor: '#0D3B2E'
        });
    </script>
    <?php endif; ?>

    <script>
        function markAsRead(element, id) {
            const badge = document.getElementById('badge-' + id);
            if (badge) {
                // Remove badge immediately for UI responsiveness
                badge.style.display = 'none';
                
                // Send AJAX request
                const formData = new FormData();
                formData.append('mark_read', '1');
                formData.append('announcement_id', id);

                fetch('announcements.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status !== 'success') {
                        console.error('Failed to mark as read');
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }

        function toggleRecipients() {
            const classSelect = document.getElementById('classSelect');
            const recipientSection = document.getElementById('recipientSection');
            const studentListContainer = document.getElementById('studentListContainer');
            
            if (classSelect.value) {
                recipientSection.style.display = 'block';
                // Load students
                fetch(`announcements.php?action=get_students&class_id=${classSelect.value}`)
                    .then(res => res.json())
                    .then(data => {
                        let html = '';
                        if (data.length > 0) {
                            data.forEach(student => {
                                html += `
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="student_ids[]" value="${student.id}" id="student-${student.id}">
                                        <label class="form-check-label" for="student-${student.id}">
                                            ${student.full_name}
                                        </label>
                                    </div>
                                `;
                            });
                        } else {
                            html = '<div class="text-muted text-center">No students found in this class.</div>';
                        }
                        studentListContainer.innerHTML = html;
                    });
            } else {
                recipientSection.style.display = 'none';
                document.getElementById('recipientAll').checked = true;
                toggleStudentList();
            }
        }

        function toggleStudentList() {
            const isSelected = document.getElementById('recipientSelected').checked;
            const list = document.getElementById('studentListContainer');
            list.style.display = isSelected ? 'block' : 'none';
        }
    </script>
</body>
</html>
