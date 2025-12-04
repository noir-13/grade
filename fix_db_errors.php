<?php
require 'db_connect.php';

echo "<h2>Fixing Database Errors...</h2>";

// 1. Fix 'academic_year' in 'grades' table
$check = $conn->query("SHOW COLUMNS FROM grades LIKE 'academic_year'");
if ($check->num_rows > 0) {
    echo "Column 'academic_year' exists. Modifying to allow NULL... ";
    if ($conn->query("ALTER TABLE grades MODIFY COLUMN academic_year VARCHAR(50) DEFAULT NULL")) {
        echo "✅ Fixed.<br>";
    } else {
        echo "❌ Error: " . $conn->error . "<br>";
    }
} else {
    echo "Column 'academic_year' does not exist in 'grades' table. Skipping.<br>";
}

// 2. Fix 'grade' in 'grades' table
$check = $conn->query("SHOW COLUMNS FROM grades LIKE 'grade'");
if ($check->num_rows > 0) {
    echo "Column 'grade' exists. Modifying to allow NULL... ";
    if ($conn->query("ALTER TABLE grades MODIFY COLUMN grade DECIMAL(5,2) DEFAULT NULL")) {
        echo "✅ Fixed.<br>";
    } else {
        echo "❌ Error: " . $conn->error . "<br>";
    }
} else {
    echo "Column 'grade' does not exist in 'grades' table. Skipping.<br>";
}

// 3. Fix 'semester' in 'grades' table (just in case)
$check = $conn->query("SHOW COLUMNS FROM grades LIKE 'semester'");
if ($check->num_rows > 0) {
    echo "Column 'semester' exists. Modifying to allow NULL... ";
    if ($conn->query("ALTER TABLE grades MODIFY COLUMN semester VARCHAR(50) DEFAULT '1st Sem 2024-2025'")) {
        echo "✅ Fixed.<br>";
    } else {
        echo "❌ Error: " . $conn->error . "<br>";
    }
}

echo "<h3>Database Fixes Complete.</h3>";
?>
