CREATE DATABASE IF NOT EXISTS gradingSystem;
USE gradingSystem;

-- 1. INSTITUTES: The top-level colleges
CREATE TABLE IF NOT EXISTS institutes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL
);

-- 2. PROGRAMS: Linked to Institutes
CREATE TABLE IF NOT EXISTS programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    institute_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL,
    FOREIGN KEY (institute_id) REFERENCES institutes(id) ON DELETE CASCADE
);

-- 3. USERS: Stores both Students and Teachers
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    school_id VARCHAR(50) DEFAULT NULL UNIQUE, -- KLD-2024-XXXX (Nullable during registration)
    full_name VARCHAR(100) DEFAULT NULL, -- Nullable during registration
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'teacher', 'admin') DEFAULT 'student',
    program_id INT DEFAULT NULL, -- Nullable for teachers/admins
    institute_id INT DEFAULT NULL, -- For Teachers/Admins
    is_verified TINYINT(1) DEFAULT 0, -- 0 = Pending, 1 = Verified
    status ENUM('active', 'pending') DEFAULT 'active', -- For Teacher approval
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id),
    FOREIGN KEY (institute_id) REFERENCES institutes(id)
);

-- 4. VERIFICATION CODES: Temporary storage for OTPs
CREATE TABLE IF NOT EXISTS verification_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    code VARCHAR(6) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    INDEX (email)
);

-- 5. GRADES: The academic records
CREATE TABLE IF NOT EXISTS grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL, -- Changed from student_school_id to student_id (FK)
    subject_code VARCHAR(50) NOT NULL,
    subject_name VARCHAR(100) DEFAULT NULL,
    grade DECIMAL(5,2) DEFAULT NULL, -- Transmuted
    raw_grade DECIMAL(5,2) DEFAULT NULL,
    midterm DECIMAL(5,2) DEFAULT NULL,
    final DECIMAL(5,2) DEFAULT NULL,
    remarks VARCHAR(255) DEFAULT NULL,
    teacher_id INT NOT NULL,
    section VARCHAR(50) DEFAULT NULL,
    semester VARCHAR(50) DEFAULT '1st Sem 2024-2025',
    class_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id),
    FOREIGN KEY (class_id) REFERENCES classes(id)
);

-- 6. CLASSES: Managed by Teachers
CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    subject_code VARCHAR(50) NOT NULL,
    subject_description VARCHAR(255),
    section VARCHAR(50) NOT NULL,
    class_code VARCHAR(10) NOT NULL UNIQUE, -- For students to join
    semester VARCHAR(50) DEFAULT '1st Sem 2024-2025',
    units INT DEFAULT 3,
    schedule VARCHAR(100),
    program_id INT DEFAULT NULL, -- Restriction
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id),
    FOREIGN KEY (program_id) REFERENCES programs(id)
);

-- 7. ENROLLMENTS: Students joining classes
CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    student_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (class_id, student_id)
);

-- 8. ANNOUNCEMENTS
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    author_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id)
);

-- 9. ANNOUNCEMENT RECIPIENTS (Targeting)
CREATE TABLE IF NOT EXISTS announcement_recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    recipient_group ENUM('all', 'teachers', 'students', 'program', 'institute') NOT NULL,
    target_id INT DEFAULT NULL, -- program_id or institute_id if applicable
    FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE
);

-- 10. ANNOUNCEMENT READS (Tracking)
CREATE TABLE IF NOT EXISTS announcement_reads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT NOT NULL,
    user_id INT NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- --- SEED DATA (KLD CONFIGURATION) ---
-- Clear existing data to avoid duplicates if re-running (optional, be careful in prod)
-- TRUNCATE TABLE programs;
-- TRUNCATE TABLE institutes;

INSERT INTO institutes (name, code) VALUES 
('Institute of Computing and Digital Innovation', 'ICDI'),
('Institute of Business and Management', 'IBM'),
('Institute of Engineering', 'IOE')
ON DUPLICATE KEY UPDATE code=code;

-- Programs for ICDI (Institute ID 1)
INSERT INTO programs (institute_id, name, code) VALUES 
(1, 'Bachelor of Science in Information Systems', 'BSIS'),
(1, 'Bachelor of Science in Information Technology', 'BSIT'),
(1, 'Bachelor of Science in Computer Science', 'BSCS'),
(2, 'Bachelor of Science in Accountancy', 'BSA'),
(2, 'Bachelor of Science in Business Admin', 'BSBA')
ON DUPLICATE KEY UPDATE code=code;

-- Seed Admins (Heads of Institute)
-- Password is 'admin123' (hashed)
INSERT INTO users (school_id, full_name, email, password_hash, role, institute_id, is_verified, status) VALUES 
('ADMIN-ICDI', 'Head of ICDI', 'admin.icdi@kld.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, 1, 'active'),
('ADMIN-IBM', 'Head of IBM', 'admin.ibm@kld.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 2, 1, 'active'),
('ADMIN-IOE', 'Head of IOE', 'admin.ioe@kld.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 3, 1, 'active')
ON DUPLICATE KEY UPDATE role='admin';
