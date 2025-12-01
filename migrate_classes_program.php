<?php
require 'db_connect.php';

// Add program_id to classes table
$sql = "SHOW COLUMNS FROM classes LIKE 'program_id'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    $alter = "ALTER TABLE classes ADD COLUMN program_id INT NULL AFTER teacher_id";
    if ($conn->query($alter) === TRUE) {
        echo "Added program_id column to classes table successfully.<br>";
    } else {
        echo "Error adding program_id column: " . $conn->error . "<br>";
    }
} else {
    echo "program_id column already exists in classes table.<br>";
}

// Add Foreign Key if possible (optional but good)
// Assuming programs table exists and id is PK
// $alterFK = "ALTER TABLE classes ADD CONSTRAINT fk_classes_program FOREIGN KEY (program_id) REFERENCES programs(id)";
// $conn->query($alterFK);

echo "Migration complete.";
?>
