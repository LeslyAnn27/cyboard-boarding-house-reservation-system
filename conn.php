<?php
$servername = "127.0.0.1:3306";
$username = "u115965728_cyboard";
$password = "Cyboard2025";
$dbname = "u115965728_cyboard";


$conn = new mysqli($servername, $username, $password, $dbname);
$conn->query("SET time_zone = '+08:00'");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
