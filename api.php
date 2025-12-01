<?php
require 'db_connect.php';
require 'email_helper.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'get_institutes') {
    $sql = "SELECT * FROM institutes ORDER BY name ASC";
    $result = $conn->query($sql);
    $data = [];
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
    exit;
}

if ($action === 'get_programs') {
    $institute_id = isset($_GET['institute_id']) ? intval($_GET['institute_id']) : 0;
    if ($institute_id > 0) {
        $sql = "SELECT * FROM programs WHERE institute_id = $institute_id ORDER BY name ASC";
    } else {
        $sql = "SELECT * FROM programs ORDER BY name ASC";
    }
    $result = $conn->query($sql);
    $data = [];
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);
    exit;
}

// Helper: Transmute Grade
function transmuteGrade($raw) {
    if ($raw >= 97) return [1.00, 'Passed'];
    if ($raw >= 94) return [1.25, 'Passed'];
    if ($raw >= 91) return [1.50, 'Passed'];
    if ($raw >= 88) return [1.75, 'Passed'];
    if ($raw >= 85) return [2.00, 'Passed'];
    if ($raw >= 82) return [2.25, 'Passed'];
    if ($raw >= 79) return [2.50, 'Passed'];
    if ($raw >= 76) return [2.75, 'Passed'];
    if ($raw >= 70) return [3.00, 'Passed'];
    return [5.00, 'Failed'];
}

// Bulk Upload Grades
if ($action === 'bulk_upload_grades') {
    session_start();
    
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized. Only teachers can upload grades.']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $grades = $input['grades'] ?? [];
    $class_id = isset($input['class_id']) ? intval($input['class_id']) : 0;
    
    $section = '';
    $subject_code = '';
    $subject_name = '';
    $semester = '';

    if ($class_id > 0) {
        $stmtClass = $conn->prepare("SELECT * FROM classes WHERE id = ? AND teacher_id = ?");
        $stmtClass->bind_param("ii", $class_id, $_SESSION['user_id']);
        $stmtClass->execute();
        $classRes = $stmtClass->get_result();
        if ($classRow = $classRes->fetch_assoc()) {
            $section = $classRow['section'];
            $subject_code = $classRow['subject_code'];
            $subject_name = $classRow['subject_description'];
            $semester = $classRow['semester'];
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid Class ID']);
            exit;
        }
    } else {
        $section = trim($input['section'] ?? '');
        $subject_code = trim($input['subject_code'] ?? '');
        $subject_name = trim($input['subject_name'] ?? '');
        $semester = trim($input['semester'] ?? '1st Sem 2024-2025');
    }
    
    $teacher_id = $_SESSION['user_id'];
    
    if (empty($grades)) {
        echo json_encode(['success' => false, 'message' => 'No grade data provided']);
        exit;
    }
    
    if (empty($section) || empty($subject_code)) {
        echo json_encode(['success' => false, 'message' => 'Section and Subject Code are required']);
        exit;
    }
    
    $successCount = 0;
    $updateCount = 0;
    $errors = [];
    $notFound = [];
    $autoEnrolled = [];
    
    $conn->begin_transaction();
    
    try {
        $stmtFindStudent = $conn->prepare("SELECT id, full_name, email FROM users WHERE school_id = ? AND role = 'student'");
        $stmtCheckEnrollment = $conn->prepare("SELECT id FROM enrollments WHERE class_id = ? AND student_id = ?");
        $stmtEnroll = $conn->prepare("INSERT INTO enrollments (class_id, student_id) VALUES (?, ?)");

        $studentsToNotify = [];

        // Note: We are storing the TRANSMUTED grade in the 'grade' column and RAW in 'raw_grade'.
        $stmtUpsert = $conn->prepare("
            INSERT INTO grades (student_id, subject_code, subject_name, grade, raw_grade, remarks, teacher_id, section, semester, class_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                grade = VALUES(grade), 
                raw_grade = VALUES(raw_grade),
                remarks = VALUES(remarks),
                subject_name = VALUES(subject_name),
                class_id = VALUES(class_id),
                updated_at = CURRENT_TIMESTAMP
        ");

        $stmtCreateGhost = $conn->prepare("INSERT INTO users (school_id, full_name, email, password_hash, role, status, is_verified) VALUES (?, ?, ?, ?, 'student', 'ghost', 0)");
        
        foreach ($grades as $row) {
            $student_school_id = trim($row[0] ?? '');
            $raw_grade = floatval($row[1] ?? 0);
            $notes = trim($row[2] ?? ''); 

            if ($raw_grade < 0 || $raw_grade > 100) {
                $errors[] = "Grade for $student_school_id must be between 0 and 100";
                continue;
            } 
            
            list($transmuted_grade, $status_remarks) = transmuteGrade($raw_grade);
            
            $final_remarks = $status_remarks;
            if (!empty($notes)) {
                $final_remarks .= " - " . $notes;
            }
            
            if (empty($student_school_id)) continue;
            
            $stmtFindStudent->bind_param("s", $student_school_id);
            $stmtFindStudent->execute();
            $res = $stmtFindStudent->get_result();
            
            if ($student = $res->fetch_assoc()) {
                $student_id = $student['id'];

                if ($class_id > 0) {
                    $stmtCheckEnrollment->bind_param("ii", $class_id, $student_id);
                    $stmtCheckEnrollment->execute();
                    if ($stmtCheckEnrollment->get_result()->num_rows === 0) {
                        // Auto-Enroll
                        $stmtEnroll->bind_param("ii", $class_id, $student_id);
                        if ($stmtEnroll->execute()) {
                            $autoEnrolled[] = $student_school_id;
                        } else {
                            $errors[] = "Failed to auto-enroll $student_school_id";
                            continue;
                        }
                    }
                }

                $stmtUpsert->bind_param("issddssisi", $student_id, $subject_code, $subject_name, $transmuted_grade, $raw_grade, $final_remarks, $teacher_id, $section, $semester, $class_id);
                
                if ($stmtUpsert->execute()) {
                    if ($stmtUpsert->affected_rows === 1) {
                        $successCount++;
                    } else {
                        $updateCount++;
                    }
                    // Add to notify list
                    $studentsToNotify[] = [
                        'email' => $student['email'],
                        'name' => $student['full_name'],
                        'subject' => $subject_code,
                        'grade' => $transmuted_grade
                    ];
                } else {
                    $errors[] = "Error saving grade for $student_school_id";
                }
                
            } else {
                // Ghost Student Logic
                $ghost_email = "ghost_" . $student_school_id . "@kld.edu.ph";
                $ghost_pass = password_hash("ghost", PASSWORD_DEFAULT);
                $ghost_name = "Student " . $student_school_id; 
                
                $stmtCreateGhost->bind_param("ssss", $student_school_id, $ghost_name, $ghost_email, $ghost_pass);
                
                if ($stmtCreateGhost->execute()) {
                    $student_id = $stmtCreateGhost->insert_id;
                    
                    // Auto-Enroll Ghost
                    if ($class_id > 0) {
                        $stmtEnroll->bind_param("ii", $class_id, $student_id);
                        $stmtEnroll->execute();
                    }
                    
                    // Insert Grade
                     $stmtUpsert->bind_param("issddssisi", $student_id, $subject_code, $subject_name, $transmuted_grade, $raw_grade, $final_remarks, $teacher_id, $section, $semester, $class_id);
                     if ($stmtUpsert->execute()) {
                         $successCount++;
                         $autoEnrolled[] = $student_school_id . " (Ghost)";
                     }
                } else {
                     $errors[] = "Failed to create ghost user for $student_school_id: " . $stmtCreateGhost->error;
                }
            }
        }
        
        $conn->commit();

        // Send Emails (After commit to ensure data is saved)
        foreach ($studentsToNotify as $s) {
            $emailBody = "
                <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
                    <h2 style='color: #0D3B2E;'>Grade Update</h2>
                    <p>Dear {$s['name']},</p>
                    <p>A new grade has been posted for <strong>{$s['subject']}</strong>.</p>
                    <p>Grade: <strong>{$s['grade']}</strong></p>
                    <hr>
                    <p>Please log in to the portal to view full details.</p>
                </div>
            ";
            sendEmail($s['email'], "Grade Update: " . $s['subject'], $emailBody);
        }
        
        echo json_encode([
            'success' => true,
            'inserted' => $successCount,
            'updated' => $updateCount,
            'not_found' => $notFound,
            'auto_enrolled' => $autoEnrolled,
            'errors' => $errors
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    
    exit;
}

// Validate Students
if ($action === 'validate_students') {
    session_start();
    
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $student_ids = $input['student_ids'] ?? [];
    $class_id = isset($input['class_id']) ? intval($input['class_id']) : 0;
    
    $valid = [];
    $invalid = [];
    $not_enrolled = [];
    
    $stmt = $conn->prepare("SELECT id, school_id, full_name FROM users WHERE school_id = ? AND role = 'student'");
    $stmtCheckEnrollment = $conn->prepare("SELECT id FROM enrollments WHERE class_id = ? AND student_id = ?");
    
    foreach ($student_ids as $school_id) {
        $school_id = trim($school_id);
        if (empty($school_id)) continue;
        
        $stmt->bind_param("s", $school_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if ($class_id > 0) {
                $stmtCheckEnrollment->bind_param("ii", $class_id, $row['id']);
                $stmtCheckEnrollment->execute();
                if ($stmtCheckEnrollment->get_result()->num_rows === 0) {
                    $not_enrolled[] = $school_id;
                    continue;
                }
            }

            $valid[] = [
                'school_id' => $school_id,
                'name' => $row['full_name']
            ];
        } else {
            $invalid[] = $school_id;
        }
    }
    
    echo json_encode([
        'success' => true,
        'valid' => $valid,
        'invalid' => $invalid,
        'not_enrolled' => $not_enrolled
    ]);
    exit;
}

// Create Class
if ($action === 'create_class') {
    session_start();
    if ($_SESSION['role'] !== 'teacher') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $subject_code = trim($input['subject_code']);
    $subject_desc = trim($input['subject_description'] ?? '');
    $section = trim($input['section']);
    $semester = trim($input['semester']);
    $units = intval($input['units'] ?? 3);
    $schedule = trim($input['schedule'] ?? 'TBA');
    $program_id = !empty($input['program_id']) ? intval($input['program_id']) : null;
    $teacher_id = $_SESSION['user_id'];

    if (empty($subject_code) || empty($section)) {
        echo json_encode(['success' => false, 'message' => 'Subject Code and Section are required']);
        exit;
    }

    if (preg_match('/[^a-zA-Z0-9]/', $section)) {
        echo json_encode(['success' => false, 'message' => 'Section must be alphanumeric (e.g., 209, A, B1). No special characters or dashes allowed.']);
        exit;
    }

    $class_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
    
    $stmtCheck = $conn->prepare("SELECT id FROM classes WHERE class_code = ?");
    $stmtCheck->bind_param("s", $class_code);
    $stmtCheck->execute();
    if ($stmtCheck->get_result()->num_rows > 0) {
        $class_code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
    }

    $stmt = $conn->prepare("INSERT INTO classes (teacher_id, subject_code, subject_description, section, class_code, semester, units, schedule, program_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssisi", $teacher_id, $subject_code, $subject_desc, $section, $class_code, $semester, $units, $schedule, $program_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'class_code' => $class_code, 'message' => 'Class created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }
    exit;
}

if ($action === 'edit_class') {
    session_start();
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $class_id = intval($input['class_id']);
    $subject_code = trim($input['subject_code']);
    $subject_desc = trim($input['subject_description'] ?? '');
    $section = trim($input['section']);
    $semester = trim($input['semester']);
    $units = intval($input['units'] ?? 3);
    $schedule = trim($input['schedule'] ?? 'TBA');
    $program_id = !empty($input['program_id']) ? intval($input['program_id']) : null;
    $teacher_id = $_SESSION['user_id'];

    if (empty($class_id) || empty($subject_code) || empty($section)) {
        echo json_encode(['success' => false, 'message' => 'Class ID, Subject Code and Section are required']);
        exit;
    }

    if (preg_match('/[^a-zA-Z0-9]/', $section)) {
        echo json_encode(['success' => false, 'message' => 'Section must be alphanumeric (e.g., 209, A, B1). No special characters or dashes allowed.']);
        exit;
    }

    // Verify ownership
    $stmtCheck = $conn->prepare("SELECT id FROM classes WHERE id = ? AND teacher_id = ?");
    $stmtCheck->bind_param("ii", $class_id, $teacher_id);
    $stmtCheck->execute();
    if ($stmtCheck->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Class not found or unauthorized']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE classes SET subject_code = ?, subject_description = ?, section = ?, semester = ?, units = ?, schedule = ?, program_id = ? WHERE id = ?");
    $stmt->bind_param("ssssisii", $subject_code, $subject_desc, $section, $semester, $units, $schedule, $program_id, $class_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Class updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }
    exit;
}

// Join Class
if ($action === 'join_class') {
    session_start();
    if ($_SESSION['role'] !== 'student') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $class_code = trim($input['class_code']);
    $student_id = $_SESSION['user_id'];

    if (empty($class_code)) {
        echo json_encode(['success' => false, 'message' => 'Class Code is required']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, subject_code, section, program_id FROM classes WHERE class_code = ?");
    $stmt->bind_param("s", $class_code);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        $class_id = $row['id'];
        $class_section = $row['section'];
        $class_program_id = $row['program_id'];

        // Fetch Student Info
        $stmtUser = $conn->prepare("SELECT section, program_id FROM users WHERE id = ?");
        $stmtUser->bind_param("i", $student_id);
        $stmtUser->execute();
        $student = $stmtUser->get_result()->fetch_assoc();

        // Check Restrictions
        if ($class_program_id && $class_program_id != $student['program_id']) {
             // Fetch program name for better error message? For now generic.
             echo json_encode(['success' => false, 'message' => 'You cannot join this class. Program restriction mismatch.']);
             exit;
        }
        
        if (!empty($class_section) && strcasecmp($class_section, $student['section']) !== 0) {
             echo json_encode(['success' => false, 'message' => "You cannot join this class. Section restriction mismatch (Required: $class_section)."]);
             exit;
        }

        $stmtCheck = $conn->prepare("SELECT id FROM enrollments WHERE class_id = ? AND student_id = ?");
        $stmtCheck->bind_param("ii", $class_id, $student_id);
        $stmtCheck->execute();
        if ($stmtCheck->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'You are already enrolled in this class']);
            exit;
        }

        $stmtEnroll = $conn->prepare("INSERT INTO enrollments (class_id, student_id) VALUES (?, ?)");
        $stmtEnroll->bind_param("ii", $class_id, $student_id);
        
        if ($stmtEnroll->execute()) {
            echo json_encode(['success' => true, 'message' => "Successfully joined {$row['subject_code']} - {$row['section']}"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Enrollment failed']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid Class Code']);
    }
    exit;
}

// Get Classes
if ($action === 'get_classes') {
    session_start();
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    $data = [];

    if ($role === 'teacher') {
        $stmt = $conn->prepare("SELECT * FROM classes WHERE teacher_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $stmtCount = $conn->prepare("SELECT COUNT(*) as count FROM enrollments WHERE class_id = ?");
            $stmtCount->bind_param("i", $row['id']);
            $stmtCount->execute();
            $row['student_count'] = $stmtCount->get_result()->fetch_assoc()['count'];
            $data[] = $row;
        }
    } elseif ($role === 'student') {
        $stmt = $conn->prepare("
            SELECT c.*, u.full_name as teacher_name 
            FROM enrollments e 
            JOIN classes c ON e.class_id = c.id 
            JOIN users u ON c.teacher_id = u.id 
            WHERE e.student_id = ? 
            ORDER BY e.joined_at DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $data[] = $row;
        }
    }

    echo json_encode(['success' => true, 'classes' => $data]);
    exit;
}

// Get Class Students
if ($action === 'get_class_students') {
    session_start();
    $class_id = intval($_GET['class_id']);
    $teacher_id = $_SESSION['user_id'];

    $stmtCheck = $conn->prepare("SELECT id FROM classes WHERE id = ? AND teacher_id = ?");
    $stmtCheck->bind_param("ii", $class_id, $teacher_id);
    $stmtCheck->execute();
    if ($stmtCheck->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT u.id, u.school_id, u.full_name, u.email, e.joined_at, g.grade, g.raw_grade, g.remarks
        FROM enrollments e 
        JOIN users u ON e.student_id = u.id 
        LEFT JOIN grades g ON g.student_id = u.id AND g.class_id = ?
        WHERE e.class_id = ? 
        ORDER BY u.full_name ASC
    ");
    $stmt->bind_param("ii", $class_id, $class_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $students = [];
    while ($row = $res->fetch_assoc()) {
        $students[] = $row;
    }
    
    echo json_encode(['success' => true, 'students' => $students]);
    exit;
}

if ($action === 'update_single_grade') {
    session_start();
    if ($_SESSION['role'] !== 'teacher') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $class_id = intval($input['class_id']);
    $student_id = intval($input['student_id']);
    $raw_grade = floatval($input['raw_grade']);
    
    if ($raw_grade < 0 || $raw_grade > 100) {
        echo json_encode(['success' => false, 'message' => 'Grade must be between 0 and 100']);
        exit;
    }
    $remarks = trim($input['remarks']);
    $teacher_id = $_SESSION['user_id'];

    // Transmute
    list($transmuted_grade, $status_remarks) = transmuteGrade($raw_grade);
    
    // If remarks is empty, use status remarks. If not, append or keep? 
    // User might want to override remarks. Let's keep user remarks if provided, else status.
    if (empty($remarks)) {
        $remarks = $status_remarks;
    }

    // Verify ownership
    $stmtCheck = $conn->prepare("SELECT * FROM classes WHERE id = ? AND teacher_id = ?");
    $stmtCheck->bind_param("ii", $class_id, $teacher_id);
    $stmtCheck->execute();
    $classInfo = $stmtCheck->get_result()->fetch_assoc();

    if (!$classInfo) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    // Upsert Grade
    $stmt = $conn->prepare("
        INSERT INTO grades (student_id, subject_code, subject_name, grade, raw_grade, remarks, teacher_id, section, semester, class_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            grade = VALUES(grade), 
            raw_grade = VALUES(raw_grade),
            remarks = VALUES(remarks),
            updated_at = CURRENT_TIMESTAMP
    ");
    
    $stmt->bind_param("issddssisi", 
        $student_id, 
        $classInfo['subject_code'], 
        $classInfo['subject_description'], 
        $transmuted_grade,
        $raw_grade, 
        $remarks, 
        $teacher_id, 
        $classInfo['section'], 
        $classInfo['semester'], 
        $class_id
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Grade updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }
    exit;
}
?>
