<?php
require '../session.php';
require '../database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$raw = json_decode(file_get_contents('php://input'), true) ?? [];
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($raw['csrf_token'] ?? '');
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$userId = isset($raw['userId']) ? (int)$raw['userId'] : 0;
$devotion = $raw['devotion'] ?? '';
$allowedDevotions = ['red', 'blue', 'yellow', 'green'];

if ($userId <= 0 || !in_array($devotion, $allowedDevotions, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

if (($_SESSION['user_role'] ?? '') !== 'admin' && (int)$_SESSION['user_id'] !== $userId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$stmt = $pdo->prepare("UPDATE roster_data SET devotion = ? WHERE user_id = ?");
$success = $stmt->execute([$devotion, $userId]);

echo json_encode(['success' => (bool)$success]);