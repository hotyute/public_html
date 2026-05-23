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

$user_id = $_SESSION['user_id'] ?? null;
$comment_id = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
$content = trim($_POST['content'] ?? '');

if ($user_id && $comment_id && $content !== '') {
    $comment_owner_query = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
    $comment_owner_query->execute([$comment_id]);
    $comment_owner_id = $comment_owner_query->fetchColumn();

    if (!$comment_owner_id) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Comment not found']);
        exit;
    }

    if ($user_id == $comment_owner_id || ($_SESSION['user_role'] ?? '') === 'admin') {
        $update_stmt = $pdo->prepare("UPDATE comments SET content = ? WHERE id = ?");
        if ($update_stmt->execute([$content, $comment_id])) {
            echo json_encode(['success' => true, 'message' => 'Comment updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update comment']);
        }
    } else {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You do not have permission to edit this comment']);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
}
