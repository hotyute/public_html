<?php
require '../session.php'; // Ensure session management is initialized
require '../database.php'; // Include the database connection

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

    if ($user_id && $comment_id) {
        // Retrieve the owner of the comment
        $comment_owner_query = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
        $comment_owner_query->execute([$comment_id]);
        $comment_owner_id = $comment_owner_query->fetchColumn();

        // Check if the current user is the owner of the comment or an admin
        if ($user_id == $comment_owner_id || $_SESSION['user_role'] === 'admin') {
            // Delete the comment
            $delete_stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
            if ($delete_stmt->execute([$comment_id])) {
                echo json_encode(['success' => true, 'message' => 'Comment deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete comment']);
            }
        } else {
            // User is not authorized to delete this comment
            echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this comment']);
        }
    } else {
        // Invalid input, either user_id or comment_id is missing or invalid
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
    }
}
?>
