<?php

declare(strict_types=1);

$host = '127.0.0.1';
$username = 'root';
$password = 'Joash@outlook1';
$port = 3306;
$schemaFile = __DIR__ . DIRECTORY_SEPARATOR . 'magic_hotel.sql';

function outputLine(string $message): void
{
    if (PHP_SAPI === 'cli') {
        echo $message . PHP_EOL;
        return;
    }

    echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "<br>\n";
}

if (!extension_loaded('mysqli')) {
    http_response_code(500);
    outputLine('The mysqli extension is not available. Run this project with XAMPP PHP or enable mysqli first.');
    exit(1);
}

if (!file_exists($schemaFile)) {
    http_response_code(500);
    outputLine('Could not find magic_hotel.sql in the project folder.');
    exit(1);
}

$conn = new mysqli($host, $username, $password, '', $port);

if ($conn->connect_error) {
    http_response_code(500);
    outputLine('Database connection failed: ' . $conn->connect_error);
    exit(1);
}

$conn->set_charset('utf8mb4');
$sql = file_get_contents($schemaFile);

if ($sql === false) {
    http_response_code(500);
    outputLine('Failed to read the database schema file.');
    $conn->close();
    exit(1);
}

if (!$conn->multi_query($sql)) {
    http_response_code(500);
    outputLine('Setup failed: ' . $conn->error);
    $conn->close();
    exit(1);
}

do {
    $result = $conn->store_result();
    if ($result instanceof mysqli_result) {
        $result->free();
    }
} while ($conn->more_results() && $conn->next_result());

if ($conn->error) {
    http_response_code(500);
    outputLine('Setup failed while finishing SQL statements: ' . $conn->error);
    $conn->close();
    exit(1);
}

$checkConn = new mysqli($host, $username, $password, 'magic_hotel', $port);

if ($checkConn->connect_error) {
    http_response_code(500);
    outputLine('Database was created, but the verification connection failed: ' . $checkConn->connect_error);
    $conn->close();
    exit(1);
}

$summary = [
    'services' => 0,
    'staff' => 0,
    'clients' => 0,
];

foreach ($summary as $table => $count) {
    $tableName = $table === 'staff' ? 'admins' : ($table === 'clients' ? 'users' : 'services');
    $result = $checkConn->query("SELECT COUNT(*) AS total FROM $tableName");
    if ($result instanceof mysqli_result) {
        $row = $result->fetch_assoc();
        $summary[$table] = (int) ($row['total'] ?? 0);
        $result->free();
    }
}

outputLine('Magic Hotel database setup completed successfully.');
outputLine('Services loaded: ' . $summary['services']);
outputLine('Staff accounts loaded: ' . $summary['staff']);
outputLine('Client accounts loaded: ' . $summary['clients']);
outputLine('Default admin login: admin@magichotel.com / admin123');
outputLine('Default client login: guest@magichotel.com / guest123');

$checkConn->close();
$conn->close();
