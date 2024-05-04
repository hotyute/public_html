
<?php
require_once 'config.php';

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;
$options = [
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    // Optionally check for a specific table or database availability
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        // Include setup.php if tables are not set up
        require 'setup.php';
    }
} catch (PDOException $e) {
    // Redirect to setup if database connection fails, implying it might not exist
    header("Location: setup.php");
    exit;
}
?>
