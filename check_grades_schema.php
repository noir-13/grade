<?php
require 'db_connect.php';
$columns = ['grade', 'midterm', 'final', 'raw_grade'];
foreach ($columns as $col) {
    $result = $conn->query("SHOW COLUMNS FROM grades LIKE '$col'");
    if ($row = $result->fetch_assoc()) {
        print_r($row);
    } else {
        echo "$col not found\n";
    }
}
?>
