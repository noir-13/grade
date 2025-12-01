<?php
require 'db_connect.php';

$sql = "ALTER TABLE classes ADD COLUMN schedule VARCHAR(255) NULL AFTER units";

if ($conn->query($sql) === TRUE) {
    echo "Column 'schedule' added successfully";
} else {
    echo "Error adding column: " . $conn->error;
}
?>
