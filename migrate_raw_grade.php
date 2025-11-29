<?php
require 'db_connect.php';

// Add raw_grade column to grades table
$sql = "SHOW COLUMNS FROM grades LIKE 'raw_grade'";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    $alterSql = "ALTER TABLE grades ADD COLUMN raw_grade DECIMAL(5,2) NULL AFTER subject_name";
    if ($conn->query($alterSql) === TRUE) {
        echo "Successfully added raw_grade column to grades table.\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
} else {
    echo "Column raw_grade already exists.\n";
}
?>
