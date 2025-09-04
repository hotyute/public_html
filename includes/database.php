<?php
require_once 'config.php';

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;
$options = [
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        require_once dirname(__DIR__) . '/setup.php';
    }
} catch (PDOException $e) {
    header("Location: /setup.php");
    exit;
}