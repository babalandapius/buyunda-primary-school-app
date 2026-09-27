<?php

declare(strict_types=1);

$host    = getenv('DB_HOST') ?: 'localhost';
$dbName  = getenv('DB_NAME') ?: 'buyunda_primary_school';
$dbUser  = getenv('DB_USER') ?: 'root';
$dbPass  = getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'pius1234.'; // Set fallback to empty string or your local password
$charset = 'utf8mb4';

$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $host, $dbName, $charset);

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_PERSISTENT         => true,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    error_log('Database connection error: ' . $e->getMessage());
    die('Database connection failed. Please ensure MySQL is running and database settings are correct.');
}

function db(): PDO
{
    global $pdo;
    return $pdo;
}