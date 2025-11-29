<?php
/**
 * Migration Script: Class Enrollment System
 */

require 'db_connect.php';

echo "=== KLD Grading System - Enrollment Migration ===\n";

// 1. Create Classes Table
echo "Step 1: Creating classes table...\n";
$sql = "CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    subject_code VARCHAR(50) NOT NULL,
    subject_description VARCHAR(255),
    section VARCHAR(50) NOT NULL,
    class_code VARCHAR(10) NOT NULL UNIQUE,
    semester VARCHAR(50) DEFAULT '1st Sem 2024-2025',
    academic_year VARCHAR(20) DEFAULT '2024-2025',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql)) {
    echo "✓ classes table created/verified\n";
} else {
    echo "✗ Error creating classes table: " . $conn->error . "\n";
}

// 2. Create Enrollments Table
echo "Step 2: Creating enrollments table...\n";
$sql = "CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    student_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (class_id, student_id)
)";

if ($conn->query($sql)) {
    echo "✓ enrollments table created/verified\n";
} else {
    echo "✗ Error creating enrollments table: " . $conn->error . "\n";
}

// 3. Update Grades Table to link to Class (Optional but good for integrity)
// For now, we'll keep grades linked to student+subject, but we can enforce validation in PHP.
// Adding class_id to grades would be ideal for the future, but might complicate the migration of existing grades.
// Let's add it as nullable for now.
echo "Step 3: Updating grades table...\n";
$columns = $conn->query("SHOW COLUMNS FROM grades");
$existing_columns = [];
while ($col = $columns->fetch_assoc()) {
    $existing_columns[] = $col['Field'];
}

if (!in_array('class_id', $existing_columns)) {
    $conn->query("ALTER TABLE grades ADD COLUMN class_id INT NULL AFTER id");
    $conn->query("ALTER TABLE grades ADD CONSTRAINT grades_class_fk FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL");
    echo "✓ Added class_id to grades table\n";
}

echo "\n=== Migration Complete ===\n";
$conn->close();
?>
