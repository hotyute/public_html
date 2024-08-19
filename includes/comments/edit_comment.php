<?php
require 'includes/session.php'; // Ensure session management is initialized
require 'includes/database.php';
require 'includes/sanitize.php';

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
    $comment_id = filter_var($_POST['comment_id'], FILTER_VALIDATE_INT);
    $content = trim($_POST['content']);

    if ($comment_id && !empty($content)) {
        // Check if the current user is the owner of the comment or an admin
        $comment_owner_query = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
        $comment_owner_query->execute([$comment_id]);
        $comment_owner_id = $comment_owner_query->fetchColumn();

        if ($user_id == $comment_owner_id || $_SESSION['user_role'] === 'admin') {
            $sanitized_content = sanitize_html($content);
            $update_stmt = $pdo->prepare("UPDATE comments SET content = ? WHERE id = ?");
            if ($update_stmt->execute([$sanitized_content, $comment_id])) {
                echo json_encode(['success' => true, 'message' => 'Comment updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update comment']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'You do not have permission to edit this comment']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
    }
}
?>
