<?php
require '../session.php';
require '../database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token validation to prevent CSRF attacks
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }

    // Check if the user is logged in
    $user_id = $_SESSION['user_id'] ?? null;
    $comment_id = filter_var($_POST['comment_id'], FILTER_VALIDATE_INT);
    $content = trim($_POST['content']);

    if ($user_id && $comment_id && !empty($content)) {
        // Retrieve the owner of the comment
        $comment_owner_query = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
        $comment_owner_query->execute([$comment_id]);
        $comment_owner_id = $comment_owner_query->fetchColumn();

        // Check if the current user is the owner of the comment or an admin
        if ($user_id == $comment_owner_id || $_SESSION['user_role'] === 'admin') {
            // Sanitize and update the comment
            $sanitized_content = sanitize_html($content);
            $update_stmt = $pdo->prepare("UPDATE comments SET content = ? WHERE id = ?");
            if ($update_stmt->execute([$sanitized_content, $comment_id])) {
                echo json_encode(['success' => true, 'message' => 'Comment updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update comment']);
            }
        } else {
            // User is not authorized to edit this comment
            echo json_encode(['success' => false, 'message' => 'You do not have permission to edit this comment']);
        }
    } else {
        // Invalid input, either user_id, comment_id or content is missing or invalid
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
    }
}
?>
