<?php
require 'db_connect.php';

echo "<h2>Applying Schema Updates...</h2>";

// 1. Run SQL file to create missing tables
$sql = file_get_contents('kld_schema_final.sql');
if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
    echo "✅ Tables created (if missing).<br>";
} else {
    echo "❌ Error running SQL file: " . $conn->error . "<br>";
}

// Re-connect to ensure sync
$conn->close();
require 'db_connect.php';

// 2. Check for class_id in grades
$result = $conn->query("SHOW COLUMNS FROM grades LIKE 'class_id'");
if ($result->num_rows == 0) {
    echo "⚠️ 'class_id' missing in 'grades'. Adding it...<br>";
    if ($conn->query("ALTER TABLE grades ADD COLUMN class_id INT DEFAULT NULL")) {
        echo "✅ 'class_id' added to 'grades'.<br>";
        // Try adding FK
        try {
            $conn->query("ALTER TABLE grades ADD CONSTRAINT fk_grades_classes FOREIGN KEY (class_id) REFERENCES classes(id)");
            echo "✅ FK constraint added.<br>";
        } catch (Exception $e) {
            echo "⚠️ Could not add FK constraint (might be data mismatch): " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ Error adding column: " . $conn->error . "<br>";
    }
} else {
    echo "✅ 'class_id' already exists in 'grades'.<br>";
}

echo "<h3>Done.</h3>";
?>
