<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require 'includes/config.php';  // Include the configuration file

try {
    // Create a new PDO instance to connect to MySQL
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);  // Make sure DB_PASSWORD is the correct constant name
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if the database already exists
    $result = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    if ($result->fetchColumn() > 0) {
        echo "Database already exists. Setup is not required.";
    } else {
        // SQL commands to create database and tables
        $sql = <<<SQL
CREATE DATABASE IF NOT EXISTS `DB_NAME`;
USE `DB_NAME`;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    displayname VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'editor', 'member') DEFAULT 'member'
);

CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
SQL;

        // Replace placeholders with actual constant values
        $sql = str_replace("DB_NAME", DB_NAME, $sql);

        // Execute SQL commands
        $pdo->exec($sql);
        echo "Database and tables created successfully!";
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

