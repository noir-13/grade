<?php
require 'db_connect.php';

// Fetch all institutes
$institutes = [];
$res = $conn->query("SELECT * FROM institutes");
while ($row = $res->fetch_assoc()) {
    $institutes[] = $row;
}

$password = password_hash('admin123', PASSWORD_DEFAULT);

foreach ($institutes as $inst) {
    $code = strtolower($inst['code']);
    $email = "admin_{$code}@kld.edu.ph";
    $full_name = "Admin " . $inst['code'];
    $inst_id = $inst['id'];
    
    $school_id = "ADMIN-" . str_pad($inst_id, 3, "0", STR_PAD_LEFT);
    
    // Check if exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, role, institute_id, status, school_id) VALUES (?, ?, ?, 'admin', ?, 'active', ?)");
        $stmt->bind_param("sssis", $full_name, $email, $password, $inst_id, $school_id);
        if ($stmt->execute()) {
            echo "Created Admin: $email (Institute: {$inst['name']})<br>";
        } else {
            echo "Error creating $email: " . $conn->error . "<br>";
        }
    } else {
        echo "Admin $email already exists.<br>";
    }
}

echo "Admin seeding complete.";
?>
