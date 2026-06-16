<?php
require_once __DIR__ . '/../database.php';
$conn = get_db();
echo "--- SYLLABUS ---\n";
print_r($conn->query("DESCRIBE syllabus")->fetchAll(PDO::FETCH_ASSOC));
echo "--- COURSES ---\n";
print_r($conn->query("DESCRIBE courses")->fetchAll(PDO::FETCH_ASSOC));
