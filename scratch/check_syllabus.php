<?php
require_once 'database.php';
$conn = get_db();
$cols = $conn->query("DESCRIBE syllabus")->fetchAll(PDO::FETCH_COLUMN);
print_r($cols);
?>
