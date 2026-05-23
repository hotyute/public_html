<?php
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
$content = trim($_POST['comment'] ?? '');
$parent_id = filter_input(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT, ['options' => ['default' => null]]);

if (!$post_id || $content === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

if ($parent_id) {
    $parent = $pdo->prepare('SELECT id FROM comments WHERE id = ? AND post_id = ?');
    $parent->execute([$parent_id, $post_id]);
    if (!$parent->fetchColumn()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid parent comment']);
        exit;
    }
}

$stmt = $pdo->prepare("INSERT INTO comments (user_id, post_id, content, created_at, parent_id) VALUES (?, ?, ?, NOW(), ?)");
$ok = $stmt->execute([$user_id, $post_id, $content, $parent_id ?: null]);
echo json_encode(['success' => (bool)$ok, 'message' => $ok ? 'Comment added successfully' : 'Failed to add comment']);
