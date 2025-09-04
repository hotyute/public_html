<?php
require '../session.php';
require '../database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
if (!$user_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$stmt = $pdo->prepare("SELECT t.id, t.test_name FROM tests t JOIN user_tests ut ON t.id = ut.test_id WHERE ut.user_id = ?");
$stmt->execute([$user_id]);
$assigned_tests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT t.id, t.test_name FROM tests t WHERE t.id NOT IN (SELECT test_id FROM user_tests WHERE user_id = ?)");
$stmt->execute([$user_id]);
$available_tests = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['tests' => $assigned_tests, 'available_tests' => $available_tests]);