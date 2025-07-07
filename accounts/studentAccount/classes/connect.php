<?php
$servername = "sql100.infinityfree.com";
$username = "if0_39366221";
$password = "x46MLovtW2m9R";
$dbname = "if0_39366221_erm";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>