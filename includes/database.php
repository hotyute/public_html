<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/schema.php';

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    app_ensure_schema($pdo);
} catch (PDOException $e) {
    header("Location: /setup.php");
    exit;
}
