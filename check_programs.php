<?php
require 'db_connect.php';
$ids = [1, 9];
$ids_str = implode(',', $ids);
$result = $conn->query("SELECT * FROM programs WHERE id IN ($ids_str)");
while ($row = $result->fetch_assoc()) {
    print_r($row);
    echo "\n";
}
?>
