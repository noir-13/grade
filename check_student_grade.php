<?php
require 'db_connect.php';
$school_id = '2024-2-000579';
$stmt = $conn->prepare("SELECT * FROM grades WHERE student_id = (SELECT id FROM users WHERE school_id = ?)");
$stmt->bind_param("s", $school_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
?>
