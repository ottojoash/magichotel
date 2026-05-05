<?php

date_default_timezone_set('Africa/Nairobi');

$hostName = strtolower((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
$isProductionHost = $hostName !== '' && $hostName !== 'localhost' && $hostName !== '127.0.0.1';

if ($isProductionHost) {
    $servername = 'sql110.infinityfree.com';
    $username = 'if0_41838481';
    $password = 'Joash@outlook1';
    $dbname = 'if0_41838481_magic_hotel';
    $port = 3306;
} else {
    $servername = '127.0.0.1';
    $username = 'root';
    $password = 'Joash@outlook1';
    $dbname = 'magic_hotel';
    $port = 3306;
}

if (!extension_loaded('mysqli')) {
    die('The mysqli extension is not loaded. Run the app with XAMPP PHP or enable mysqli first.');
}

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    if ($isProductionHost) {
        die('Database connection failed. Update config.php with your InfinityFree MySQL credentials (DB host, DB name, DB username, and DB password from InfinityFree control panel).');
    }

    die('Connection failed: ' . $conn->connect_error . '. If this is a fresh local setup, run setup_database.php first.');
}

$conn->set_charset('utf8mb4');
