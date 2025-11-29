<?php
require 'db_connect.php';

echo "=== Migration: Add Units to Classes ===\n";

// Check if units column exists
$columns = $conn->query("SHOW COLUMNS FROM classes");
$has_units = false;
while ($col = $columns->fetch_assoc()) {
    if ($col['Field'] === 'units') {
        $has_units = true;
        break;
    }
}

if (!$has_units) {
    echo "Adding 'units' column to classes table...\n";
    if ($conn->query("ALTER TABLE classes ADD COLUMN units INT DEFAULT 3 AFTER subject_description")) {
        echo "✓ Successfully added 'units' column.\n";
    } else {
        echo "✗ Error adding column: " . $conn->error . "\n";
    }
} else {
    echo "✓ 'units' column already exists.\n";
}

echo "\n=== Migration Complete ===\n";
?>
