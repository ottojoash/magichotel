<?php

date_default_timezone_set('Africa/Nairobi');

$servername = '127.0.0.1';
$username = 'root';
$password = 'Joash@outlook1';
$dbname = 'magic_hotel';
$port = 3306;

if (!extension_loaded('mysqli')) {
    die('The mysqli extension is not loaded. Run the app with XAMPP PHP or enable mysqli first.');
}

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error . '. If this is a fresh setup, run setup_database.php first.');
}

$conn->set_charset('utf8mb4');
