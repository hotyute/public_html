<?php
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$question_id = filter_input(INPUT_GET, 'question_id', FILTER_VALIDATE_INT);
if (!$question_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
$stmt->execute([$question_id]);
$question = $stmt->fetch(PDO::FETCH_ASSOC);

if ($question) {
    echo json_encode($question);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Question not found']);
}
