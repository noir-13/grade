<?php
require 'db_connect.php';
require 'email_helper.php';
require 'csrf_helper.php';
header('Content-Type: application/json');

// Disable error display to prevent HTML output
ini_set('display_errors', 0);
error_reporting(E_ALL);

$action = $_GET['action'] ?? '';

// Debug Logging
$logFile = 'debug_log.txt';
$logMessage = date('Y-m-d H:i:s') . " - Action: $action\n";
file_put_contents($logFile, $logMessage, FILE_APPEND);

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
    if ($raw >= 75) return [3.00, 'Passed']; // Adjusted to include 75 as passing if needed, or stick to 75=3.00
    // Previous code had 70=3.00, let's stick to standard if possible, but user code had 70.
    // Let's use the logic from the previous file:
    if ($raw >= 70) return [3.00, 'Passed'];
    return [5.00, 'Failed'];
}

// Bulk Upload Grades
if ($action === 'bulk_upload_grades') {
    
    try {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
            throw new Exception('Unauthorized. Only teachers can upload grades.');
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $grades = $input['grades'] ?? [];
        $class_id = isset($input['class_id']) ? intval($input['class_id']) : 0;
        $grading_period = $input['grading_period'] ?? 'grade'; 
        $create_ghosts = $input['create_ghosts'] ?? false; 
        $csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($input['csrf_token'] ?? '');

        if (!verify_csrf_token($csrf_token)) {
            throw new Exception('Invalid CSRF Token');
        }
        
        // Map grading period to column name
        $target_column = 'grade';
        if ($grading_period === 'midterm') $target_column = 'midterm';
        if ($grading_period === 'final') $target_column = 'final';
        if ($grading_period === 'grade') $target_column = 'grade'; 
        
        $section = '';
        $subject_code = '';
        $subject_name = '';
        $semester = '';

        if ($class_id > 0) {
            $stmtClass = $conn->prepare("SELECT * FROM classes WHERE id = ? AND teacher_id = ?");
            if (!$stmtClass) throw new Exception("Prepare failed for class check: " . $conn->error);
            
            $stmtClass->bind_param("ii", $class_id, $_SESSION['user_id']);
            $stmtClass->execute();
            $classRes = $stmtClass->get_result();
            if ($classRow = $classRes->fetch_assoc()) {
                $section = $classRow['section'];
                $subject_code = $classRow['subject_code'];
                $subject_name = $classRow['subject_description'];
                $semester = $classRow['semester'];
            } else {
                throw new Exception('Invalid Class ID');
            }
        } else {
            $section = trim($input['section'] ?? '');
            $subject_code = trim($input['subject_code'] ?? '');
            $subject_name = trim($input['subject_name'] ?? '');
            $semester = trim($input['semester'] ?? '1st Sem 2024-2025');
        }
        
        $teacher_id = $_SESSION['user_id'];
        
        if (empty($grades)) {
            throw new Exception('No grade data provided');
        }
        
        if (empty($section) || empty($subject_code)) {
            throw new Exception('Section and Subject Code are required');
        }
        
        $successCount = 0;
        $updateCount = 0;
        $errors = [];
        $notFound = [];
        $autoEnrolled = [];
        
        $conn->begin_transaction();
        
        $stmtFindStudent = $conn->prepare("
            SELECT u.id, u.full_name, u.email, u.role, g.midterm, g.final 
            FROM users u 
            LEFT JOIN grades g ON u.id = g.student_id AND g.class_id = ?
            WHERE u.school_id = ?
        ");
        if (!$stmtFindStudent) throw new Exception("Prepare failed for find student: " . $conn->error);

        $stmtUpsert = $conn->prepare("
            INSERT INTO grades (student_id, subject_code, subject_name, $target_column, raw_grade, remarks, teacher_id, section, semester, class_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                $target_column = VALUES($target_column), 
                raw_grade = VALUES(raw_grade),
                remarks = VALUES(remarks),
                subject_name = VALUES(subject_name),
                class_id = VALUES(class_id),
                updated_at = CURRENT_TIMESTAMP
        ");

        if (!$stmtUpsert) {
            throw new Exception("Prepare failed for grades insert: " . $conn->error);
        }

        $stmtCreateGhost = $conn->prepare("INSERT INTO users (school_id, full_name, email, password_hash, role, status, is_verified) VALUES (?, ?, ?, ?, 'student', 'ghost', 0)");
        
        if (!$stmtCreateGhost) {
            throw new Exception("Prepare failed for ghost user insert: " . $conn->error);
        }
        
        // $stmtEnroll removed as per user request (no auto-enroll)

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
            
            $stmtFindStudent->bind_param("is", $class_id, $student_school_id);
            $stmtFindStudent->execute();
            $res = $stmtFindStudent->get_result();
            
            if ($student = $res->fetch_assoc()) {
                if ($student['role'] !== 'student') {
                    $errors[] = "User $student_school_id exists but is a {$student['role']}, not a student.";
                    continue;
                }

                $student_id = $student['id'];
                
                // Calculate Semestral Grade if Midterm and Final are available
                $midterm = $student['midterm'];
                $final = $student['final'];
                
                // Update current period value
                if ($grading_period === 'midterm') $midterm = $transmuted_grade;
                if ($grading_period === 'final') $final = $transmuted_grade;
                
                $semestral_grade = null;
                if ($midterm > 0 && $final > 0) {
                    $semestral_grade = ($midterm + $final) / 2;
                }

                // Auto-enroll removed

                // If Semestral Grade is calculated, update it too
                if ($semestral_grade !== null) {
                     $stmtUpdateGrade = $conn->prepare("UPDATE grades SET grade = ? WHERE student_id = ? AND class_id = ?");
                     $stmtUpdateGrade->bind_param("dii", $semestral_grade, $student_id, $class_id);
                     $stmtUpdateGrade->execute();
                }

                $stmtUpsert->bind_param("issddssssi", $student_id, $subject_code, $subject_name, $transmuted_grade, $raw_grade, $final_remarks, $teacher_id, $section, $semester, $class_id);
                
                if ($stmtUpsert->execute()) {
                    if ($stmtUpsert->affected_rows === 1) {
                        $successCount++;
                    } else {
                        $updateCount++;
                    }
                } else {
                    $errors[] = "Error saving grade for $student_school_id";
                }
                
            } else {
                if ($create_ghosts) {
                    $ghost_email = "ghost_" . $student_school_id . "@kld.edu.ph";
                    $ghost_pass = password_hash("ghost", PASSWORD_DEFAULT);
                    $ghost_name = "Student " . $student_school_id; 
                    
                    $stmtCreateGhost->bind_param("ssss", $student_school_id, $ghost_name, $ghost_email, $ghost_pass);
                    
                    if ($stmtCreateGhost->execute()) {
                        $student_id = $stmtCreateGhost->insert_id;
                        
                        // Auto-enroll removed
                        
                         $stmtUpsert->bind_param("issddssssi", $student_id, $subject_code, $subject_name, $transmuted_grade, $raw_grade, $final_remarks, $teacher_id, $section, $semester, $class_id);
                         if ($stmtUpsert->execute()) {
                             $successCount++;
                             $autoEnrolled[] = $student_school_id . " (Ghost)";
                         }
                    } else {
                         $errors[] = "Failed to create ghost user for $student_school_id: " . $stmtCreateGhost->error;
                    }
                } else {
                    $notFound[] = $student_school_id;
                    $errors[] = "Student $student_school_id not found (Ghost creation disabled)";
                }
            }
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'inserted' => $successCount,
            'updated' => $updateCount,
            'errors' => $errors,
            'not_found' => $notFound,
            'auto_enrolled' => $autoEnrolled
        ]);

    } catch (Exception $e) {
        if ($conn->in_transaction) {
            $conn->rollback();
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Validate Students
if ($action === 'validate_students') {
    
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
    
    $stmt = $conn->prepare("SELECT id, school_id, full_name, role FROM users WHERE school_id = ?");
    $stmtCheckEnrollment = $conn->prepare("SELECT id FROM enrollments WHERE class_id = ? AND student_id = ?");
    
    foreach ($student_ids as $school_id) {
        $school_id = trim($school_id);
        if (empty($school_id)) continue;
        
        $stmt->bind_param("s", $school_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if ($row['role'] !== 'student') {
                $invalid[] = [
                    'school_id' => $school_id,
                    'error' => "User is a {$row['role']}"
                ];
                continue;
            }

            if ($class_id > 0) {
                $stmtCheckEnrollment->bind_param("ii", $class_id, $row['id']);
                $stmtCheckEnrollment->execute();
                if ($stmtCheckEnrollment->get_result()->num_rows === 0) {
                    $not_enrolled[] = $school_id;
                }
            }

            $valid[] = [
                'school_id' => $school_id,
                'name' => $row['full_name']
            ];
        } else {
            if ($input['create_ghosts'] ?? false) {
                 $valid[] = [
                    'school_id' => $school_id,
                    'status' => 'ghost_create'
                ];
            } else {
                $invalid[] = [
                    'school_id' => $school_id,
                    'error' => 'Not Found'
                ];
            }
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
    $csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($input['csrf_token'] ?? '');

    if (!verify_csrf_token($csrf_token)) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF Token']);
        exit;
    }

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
    $csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($input['csrf_token'] ?? '');

    if (!verify_csrf_token($csrf_token)) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF Token']);
        exit;
    }

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
    if ($_SESSION['role'] !== 'student') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $class_code = trim($input['class_code']);
    $student_id = $_SESSION['user_id'];
    $csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($input['csrf_token'] ?? '');

    if (!verify_csrf_token($csrf_token)) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF Token']);
        exit;
    }

    if (empty($class_code)) {
        echo json_encode(['success' => false, 'message' => 'Class Code is required']);
        exit;
    }

    // Get Class Info with Program Code
    $stmt = $conn->prepare("
        SELECT c.id, c.subject_code, c.section, c.program_id, p.code as program_code 
        FROM classes c 
        LEFT JOIN programs p ON c.program_id = p.id 
        WHERE c.class_code = ?
    ");
    $stmt->bind_param("s", $class_code);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        $class_id = $row['id'];
        $class_section = $row['section'];
        $class_program_code = $row['program_code'];

        // Fetch Student Info with Program Code
        $stmtUser = $conn->prepare("
            SELECT u.section, u.program_id, p.code as program_code 
            FROM users u 
            LEFT JOIN programs p ON u.program_id = p.id 
            WHERE u.id = ?
        ");
        $stmtUser->bind_param("i", $student_id);
        $stmtUser->execute();
        $student = $stmtUser->get_result()->fetch_assoc();

        // Check Restrictions
        // DEBUG LOGGING
        file_put_contents('debug_log.txt', "Join Class Debug:\nClass ID: $class_id\nClass Program: $class_program_code\nStudent ID: $student_id\nStudent Program: {$student['program_code']}\n", FILE_APPEND);

        // Compare CODES instead of IDs to handle duplicate program entries
        if (!empty($class_program_code) && $class_program_code !== $student['program_code']) {
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
        SELECT u.id, u.school_id, u.full_name, u.email, e.joined_at, g.grade, g.midterm, g.final, g.raw_grade, g.remarks
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
    $csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($input['csrf_token'] ?? '');

    if (!verify_csrf_token($csrf_token)) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF Token']);
        exit;
    }

    // Transmute
    list($transmuted_grade, $status_remarks) = transmuteGrade($raw_grade);
    
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
