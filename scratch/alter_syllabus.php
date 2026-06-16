<?php
require_once __DIR__ . '/../database.php';

try {
    $conn = get_db();
    
    // Check if column already exists
    $stmt = $conn->query("SHOW COLUMNS FROM syllabus LIKE 'year_level'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        echo "Adding column 'year_level' to table 'syllabus'...\n";
        $conn->exec("ALTER TABLE syllabus ADD COLUMN year_level VARCHAR(50) NULL AFTER school_year");
        echo "Column added successfully!\n";
    } else {
        echo "Column 'year_level' already exists.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
