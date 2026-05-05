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

mysqli_report(MYSQLI_REPORT_OFF);

if ($isProductionHost && ($password === '' || $password === 'PASTE_YOUR_INFINITYFREE_PASSWORD')) {
    die('Database password is missing in config.php. Set your real InfinityFree MySQL password for production.');
}


$conn = @new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    if ($isProductionHost) {
        die('Database connection failed on production: ' . $conn->connect_error . '. Check InfinityFree MySQL host, username, password, and database name in config.php.');
    }

    die('Connection failed: ' . $conn->connect_error . '. If this is a fresh local setup, run setup_database.php first.');
}

$conn->set_charset('utf8mb4');
