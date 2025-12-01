<?php
require 'db_connect.php';

$sql = "ALTER TABLE users MODIFY COLUMN status ENUM('active', 'pending', 'ghost') DEFAULT 'active'";
if ($conn->query($sql) === TRUE) {
    echo "Successfully added 'ghost' to status enum.";
} else {
    echo "Error updating status enum: " . $conn->error;
}
?>
