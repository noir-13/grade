<?php
/**
 * Migration Script: Update Grades Table Schema
 * 
 * This script updates the grades table to support:
 * - Proper student_id foreign key
 * - Section information
 * - Remarks field
 * - Unique constraints to prevent duplicates
 */

require 'db_connect.php';

echo "=== KLD Grading System - Database Migration ===\n";
echo "Starting migration...\n\n";

// Step 1: Backup existing data
echo "Step 1: Creating backup of existing grades table...\n";
$backup = $conn->query("CREATE TABLE IF NOT EXISTS grades_backup AS SELECT * FROM grades");
if ($backup) {
    echo "✓ Backup created successfully\n\n";
} else {
    die("✗ Error creating backup: " . $conn->error . "\n");
}

// Step 2: Check if migration is needed
echo "Step 2: Checking current table structure...\n";
$columns = $conn->query("SHOW COLUMNS FROM grades");
$existing_columns = [];
while ($col = $columns->fetch_assoc()) {
    $existing_columns[] = $col['Field'];
}
echo "Debug: Existing columns: " . implode(', ', $existing_columns) . "\n\n";

$needs_migration = !in_array('student_id', $existing_columns) || 
                   in_array('student_school_id', $existing_columns) ||
                   !in_array('remarks', $existing_columns);

if (!$needs_migration) {
    echo "✓ Table already migrated. No action needed.\n";
    exit;
}

echo "Migration needed. Proceeding...\n\n";

// Step 3: Add new columns if they don't exist
echo "Step 3: Adding new columns...\n";

if (!in_array('student_id', $existing_columns)) {
    $conn->query("ALTER TABLE grades ADD COLUMN student_id INT NULL AFTER id");
    echo "✓ Added student_id column\n";
}

if (!in_array('subject_name', $existing_columns)) {
    $conn->query("ALTER TABLE grades ADD COLUMN subject_name VARCHAR(100) NULL AFTER subject_code");
    echo "✓ Added subject_name column\n";
}

if (!in_array('remarks', $existing_columns)) {
    $conn->query("ALTER TABLE grades ADD COLUMN remarks TEXT NULL");
    echo "✓ Added remarks column\n";
}

if (!in_array('section', $existing_columns)) {
    $conn->query("ALTER TABLE grades ADD COLUMN section VARCHAR(50) NULL");
    echo "✓ Added section column\n";
}

if (!in_array('academic_year', $existing_columns)) {
    $conn->query("ALTER TABLE grades ADD COLUMN academic_year VARCHAR(20) DEFAULT '2024-2025'");
    echo "✓ Added academic_year column\n";
}

if (!in_array('updated_at', $existing_columns)) {
    $conn->query("ALTER TABLE grades ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    echo "✓ Added updated_at column\n";
}

echo "\n";

// Step 4: Migrate data from student_school_id to student_id
if (in_array('student_school_id', $existing_columns)) {
    echo "Step 4: Migrating student_school_id to student_id...\n";
    
    $result = $conn->query("
        UPDATE grades g
        INNER JOIN users u ON g.student_school_id = u.school_id
        SET g.student_id = u.id
        WHERE g.student_id IS NULL
    ");
    
    if ($result) {
        $affected = $conn->affected_rows;
        echo "✓ Migrated $affected records\n\n";
    } else {
        echo "✗ Error migrating data: " . $conn->error . "\n\n";
    }
} else {
    echo "Step 4: Skipped (student_school_id column not found)\n\n";
}

// Step 5: Remove old column and add constraints
echo "Step 5: Cleaning up and adding constraints...\n";

// Make student_id NOT NULL
$conn->query("ALTER TABLE grades MODIFY student_id INT NOT NULL");
echo "✓ Set student_id as NOT NULL\n";

// Drop old column if exists
if (in_array('student_school_id', $existing_columns)) {
    $conn->query("ALTER TABLE grades DROP COLUMN student_school_id");
    echo "✓ Dropped student_school_id column\n";
}

// Add foreign key constraints
$conn->query("ALTER TABLE grades DROP FOREIGN KEY IF EXISTS grades_student_fk");
$conn->query("
    ALTER TABLE grades 
    ADD CONSTRAINT grades_student_fk 
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
");
echo "✓ Added foreign key constraint for student_id\n";

// Add unique constraint for preventing duplicates
$conn->query("ALTER TABLE grades DROP INDEX IF EXISTS unique_grade");
$conn->query("
    ALTER TABLE grades 
    ADD UNIQUE KEY unique_grade (student_id, subject_code, semester)
");
echo "✓ Added unique constraint to prevent duplicate grades\n";

// Add index for performance
$conn->query("CREATE INDEX idx_teacher_id ON grades(teacher_id)");
$conn->query("CREATE INDEX idx_section ON grades(section)");
$conn->query("CREATE INDEX idx_semester ON grades(semester)");
echo "✓ Added performance indexes\n\n";

// Step 6: Verify migration
echo "Step 6: Verifying migration...\n";
$count = $conn->query("SELECT COUNT(*) as total FROM grades")->fetch_assoc()['total'];
echo "✓ Total grades in table: $count\n";

$with_students = $conn->query("
    SELECT COUNT(*) as total FROM grades g
    INNER JOIN users u ON g.student_id = u.id
")->fetch_assoc()['total'];
echo "✓ Grades with valid students: $with_students\n\n";

echo "=== Migration Complete ===\n";
echo "Database schema has been successfully updated.\n";
echo "Backup table 'grades_backup' is available for rollback if needed.\n";

$conn->close();
?>
