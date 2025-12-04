<?php
// Debug script to check api.php output
session_start();
// Simulate logged in teacher
$_SESSION['user_id'] = 1; // Assuming ID 1 is a teacher (Admin is 1, but let's check users table)
$_SESSION['role'] = 'teacher';

// Capture output of api.php
ob_start();
$_GET['action'] = 'get_classes';
include 'api.php';
$output = ob_get_clean();

echo "<h1>Raw Output:</h1>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Try to decode
$json = json_decode($output, true);
echo "<h1>JSON Decode:</h1>";
if ($json === null) {
    echo "Failed to decode JSON. Error: " . json_last_error_msg();
} else {
    echo "<pre>" . print_r($json, true) . "</pre>";
}
?>
