<?php
// config.php - Database configuration
// Save this file in your project root folder

$servername = "localhost";
$username = "root";
$password = "";  // Empty for XAMPP default
$dbname = "magic_hotel";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to UTF-8
$conn->set_charset("utf8mb4");

// For testing (remove after confirmation)
// echo "Connected successfully";
?>