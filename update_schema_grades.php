<?php
require 'db_connect.php';

echo "<h2>Updating Grades Table Schema...</h2>";

// Columns to add
$columns = [
    "student_id" => "INT NOT NULL",
    "subject_name" => "VARCHAR(100) DEFAULT NULL",
    "raw_grade" => "DECIMAL(5,2) DEFAULT NULL",
    "remarks" => "VARCHAR(255) DEFAULT NULL",
    "section" => "VARCHAR(50) DEFAULT NULL",
    "midterm" => "DECIMAL(5,2) DEFAULT NULL",
    "final" => "DECIMAL(5,2) DEFAULT NULL"
];

foreach ($columns as $col => $def) {
    $check = $conn->query("SHOW COLUMNS FROM grades LIKE '$col'");
    if ($check->num_rows == 0) {
        echo "Adding column '$col'... ";
        if ($conn->query("ALTER TABLE grades ADD COLUMN $col $def")) {
            echo "✅ Done.<br>";
        } else {
            echo "❌ Error: " . $conn->error . "<br>";
        }
    } else {
        echo "Column '$col' already exists.<br>";
    }
}

// Add Foreign Key for student_id if not exists
// Check if constraint exists (hard to check portably, so we try-catch)
try {
    $conn->query("ALTER TABLE grades ADD CONSTRAINT fk_grades_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE");
    echo "✅ FK constraint for student_id added.<br>";
} catch (Exception $e) {
    echo "ℹ️ FK constraint for student_id might already exist or failed: " . $e->getMessage() . "<br>";
}

echo "<h3>Schema Update Complete.</h3>";
?>
