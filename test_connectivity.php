<?php
require_once "database.php";

$db = new Database();
$conn = $db->connect();

$stmt = $conn->query("SELECT * FROM users");

while($row = $stmt->fetch()){
    echo $row['first_name'] . " " . $row['last_name'] . "<br>";
}
?>