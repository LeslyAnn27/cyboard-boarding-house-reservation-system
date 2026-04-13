<?php
$servername = "YOUR_HOSTNAME";
$username = "YOUR_USERNAME";
$password = "YOUR_PASSWORD";
$dbname = "YOUR_DATABASE_NAME";


$conn = new mysqli($servername, $username, $password, $dbname);
$conn->query("SET time_zone = '+08:00'");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>