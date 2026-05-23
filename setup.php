<?php
if (getenv('APP_DEBUG')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/schema.php';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    $dbName = str_replace('`', '``', DB_NAME);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbName`");
    app_ensure_schema($pdo);

    echo 'Database and tables are ready.';
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Database setup failed.';
    if (getenv('APP_DEBUG')) {
        echo ' ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}
