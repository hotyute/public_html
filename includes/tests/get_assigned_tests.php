<?php
require '../includes/config.php';

if (!isset($_GET['user_id'])) {
    die("Invalid request.");
}

$user_id = $_GET['user_id'];

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT t.id, t.test_name FROM tests t JOIN user_tests ut ON t.id = ut.test_id WHERE ut.user_id = ?");
    $stmt->execute([$user_id]);
    $assigned_tests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT t.id, t.test_name FROM tests t WHERE t.id NOT IN (SELECT test_id FROM user_tests WHERE user_id = ?)");
    $stmt->execute([$user_id]);
    $available_tests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['tests' => $assigned_tests, 'available_tests' => $available_tests]);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
