<?php
$servername = "localhost";
$username = "root";       // your MySQL username
$password = "";           // your MySQL password
$database = "carserv_db"; // your database name

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
