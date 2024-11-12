<?php
require '../session.php'; // Ensure session management is initialized
require '../database.php';
require '../sanitize.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }

    // Check if the user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'User not authenticated']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $post_id = filter_var($_POST['post_id'], FILTER_VALIDATE_INT);
    $content = trim($_POST['comment']);
    $parent_id = $_POST['parent_id'];

    if ($post_id && !empty($content)) {
        $sanitized_content = $content;
        $stmt = $pdo->prepare("INSERT INTO comments (user_id, post_id, content, created_at, parent_id) VALUES (?, ?, ?, NOW(), ?)");
        if ($stmt->execute([$user_id, $post_id, $sanitized_content, $parent_id])) {
            echo json_encode(['success' => true, 'message' => 'Comment added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add comment']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
    }
}
?>
