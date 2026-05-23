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

if ($user_id && $comment_id) {
    $comment_owner_query = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
    $comment_owner_query->execute([$comment_id]);
    $comment_owner_id = $comment_owner_query->fetchColumn();

    if (!$comment_owner_id) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Comment not found']);
        exit;
    }

    if ($user_id == $comment_owner_id || ($_SESSION['user_role'] ?? '') === 'admin') {
        $pdo->beginTransaction();
        $delete_replies = $pdo->prepare("DELETE FROM comments WHERE parent_id = ?");
        $delete_replies->execute([$comment_id]);
        $delete_stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
        $ok = $delete_stmt->execute([$comment_id]);
        if ($ok) {
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Comment deleted successfully']);
        } else {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Failed to delete comment']);
        }
    } else {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this comment']);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
}
