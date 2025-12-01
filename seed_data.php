<?php
require 'db_connect.php';

$institutes = [
    ['code' => 'ION', 'name' => 'Institute of Nursing'],
    ['code' => 'IOM', 'name' => 'Institute of Midwifery'],
    ['code' => 'IOLA', 'name' => 'Institute of Liberal Arts'],
    ['code' => 'IOSM', 'name' => 'Institute of Science and Mathematics']
];

foreach ($institutes as $inst) {
    $code = $inst['code'];
    $name = $inst['name'];
    
    // Check if exists
    $check = $conn->query("SELECT id FROM institutes WHERE code = '$code'");
    if ($check->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO institutes (code, name) VALUES (?, ?)");
        $stmt->bind_param("ss", $code, $name);
        if ($stmt->execute()) {
            echo "Added Institute: $name<br>";
        } else {
            echo "Error adding $name: " . $conn->error . "<br>";
        }
    } else {
        echo "Institute $name already exists.<br>";
    }
}

$programs = [
    ['code' => 'BSN', 'name' => 'Bachelor of Science in Nursing', 'inst_code' => 'ION'],
    ['code' => 'BSM', 'name' => 'Bachelor of Science in Midwifery', 'inst_code' => 'IOM'],
    ['code' => 'BSCE', 'name' => 'Bachelor of Science in Civil Engineering', 'inst_code' => 'IOE'],
    ['code' => 'BSLS', 'name' => 'Bachelor of Science in Life Science', 'inst_code' => 'IOSM'],
    ['code' => 'BSSW', 'name' => 'Bachelor of Science in Social Works', 'inst_code' => 'IOLA'],
    ['code' => 'BSP', 'name' => 'Bachelor of Science in Psychology', 'inst_code' => 'IOLA']
];

foreach ($programs as $prog) {
    $code = $prog['code'];
    $name = $prog['name'];
    $inst_code = $prog['inst_code'];
    
    // Get Institute ID
    $instRes = $conn->query("SELECT id FROM institutes WHERE code = '$inst_code'");
    if ($instRow = $instRes->fetch_assoc()) {
        $inst_id = $instRow['id'];
        
        // Check if program exists
        $check = $conn->query("SELECT id FROM programs WHERE code = '$code'");
        if ($check->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO programs (institute_id, code, name) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $inst_id, $code, $name);
            if ($stmt->execute()) {
                echo "Added Program: $name<br>";
            } else {
                echo "Error adding $name: " . $conn->error . "<br>";
            }
        } else {
            echo "Program $name already exists.<br>";
        }
    } else {
        echo "Institute $inst_code not found for program $name.<br>";
    }
}

echo "Seeding complete.";
?>
