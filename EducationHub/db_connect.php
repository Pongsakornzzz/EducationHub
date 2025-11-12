<?php
$config = include __DIR__ . '/config.php';
$conn = new mysqli($config['host'], $config['user'], $config['pass'], $config['name']);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    error_log("Database error: " . $conn->connect_error);
    die("<h3>❌ Database connection failed.</h3>");
}
?>
