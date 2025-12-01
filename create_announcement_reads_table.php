<?php
require 'db_connect.php';

$sql = "CREATE TABLE IF NOT EXISTS announcement_reads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    announcement_id INT NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
    UNIQUE KEY unique_read (user_id, announcement_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'announcement_reads' created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}
?>
