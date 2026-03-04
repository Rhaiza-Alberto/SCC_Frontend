<?php
$conn = new mysqli("127.0.0.1", "root", "", "scc_database");

if ($conn->connect_error) {
    die("Failed: " . $conn->connect_error);
}

echo "Connected successfully!";
?>