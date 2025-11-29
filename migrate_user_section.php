<?php
require 'db_connect.php';

// Add section column to users table
$sql = "SHOW COLUMNS FROM users LIKE 'section'";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    $alterSql = "ALTER TABLE users ADD COLUMN section VARCHAR(50) NULL AFTER school_id";
    if ($conn->query($alterSql) === TRUE) {
        echo "Successfully added section column to users table.\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
} else {
    echo "Column section already exists.\n";
}
?>
