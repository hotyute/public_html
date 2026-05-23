<?php
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'editor'], true)) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$post_id = filter_input(INPUT_GET, 'post_id', FILTER_VALIDATE_INT);
if (!$post_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid post id']);
    exit;
}

$stmt = $pdo->prepare("SELECT title, content, thumbnail FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    http_response_code(404);
    echo json_encode(['error' => 'Post not found']);
    exit;
}

echo json_encode($post);
