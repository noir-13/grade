<?php
require 'db_connect.php';

echo "<h2>Institutes</h2>";
$res = $conn->query("SELECT * FROM institutes");
while ($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} - Code: {$row['code']} - Name: {$row['name']}<br>";
}

echo "<h2>Programs</h2>";
$res = $conn->query("SELECT p.id, p.code, p.name, i.name as institute FROM programs p JOIN institutes i ON p.institute_id = i.id");
while ($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} - Code: {$row['code']} - Name: {$row['name']} (Inst: {$row['institute']})<br>";
}
?>
