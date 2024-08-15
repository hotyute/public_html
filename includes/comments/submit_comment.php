<?php
session_start();
require '../database.php';

header('Content-Type: application/json');

if (isset($_POST['comment'], $_POST['user_id'], $_POST['post_id'])) {
    // Check if this is a reply to another comment
    $parent_id = isset($_POST['parent_id']) && !empty($_POST['parent_id']) ? $_POST['parent_id'] : NULL;

    $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content, parent_id) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$_POST['post_id'], $_POST['user_id'], htmlspecialchars($_POST['comment']), $parent_id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Incomplete data']);
}
?>
