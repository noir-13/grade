<?php
/**
 * Migration Script: Update Users Table for Profile Completion
 */

require 'db_connect.php';

echo "=== KLD Grading System - User Profile Migration ===\n";

// Step 1: Add new columns
echo "Step 1: Adding profile columns...\n";

$columns = $conn->query("SHOW COLUMNS FROM users");
$existing_columns = [];
while ($col = $columns->fetch_assoc()) {
    $existing_columns[] = $col['Field'];
}

if (!in_array('phone_number', $existing_columns)) {
    $conn->query("ALTER TABLE users ADD COLUMN phone_number VARCHAR(20) NULL");
    echo "✓ Added phone_number column\n";
}

if (!in_array('address', $existing_columns)) {
    $conn->query("ALTER TABLE users ADD COLUMN address TEXT NULL");
    echo "✓ Added address column\n";
}

if (!in_array('is_profile_complete', $existing_columns)) {
    $conn->query("ALTER TABLE users ADD COLUMN is_profile_complete TINYINT(1) DEFAULT 0");
    echo "✓ Added is_profile_complete column\n";
}

// Step 2: Update existing users
// For now, we can assume existing admins are complete, but let's leave students/teachers as incomplete if they lack info
// Or just set everyone to 0 to force them to check their profile
// Let's set admins to 1
$conn->query("UPDATE users SET is_profile_complete = 1 WHERE role = 'admin'");
echo "✓ Set admins as profile complete\n";

echo "\n=== Migration Complete ===\n";
$conn->close();
?>
