<?php
require 'db_connect.php';

$ids = ['2024-2-000574', '2024-2-000575', '2024-2-000576'];

foreach ($ids as $id) {
    $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE school_id = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        echo "Found $id: " . $row['full_name'] . "\n";
    } else {
        echo "Not Found $id\n";
    }
}
?>
