<?php
session_start();
require '../database.php';

header('Content-Type: application/json');

if (isset($_POST['comment'], $_POST['user_id'], $_POST['post_id'])) {
    // Assume $pdo is your database connection from a PDO instance
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
    if ($stmt->execute([$_POST['post_id'], $_POST['user_id'], htmlspecialchars($_POST['comment'])])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Incomplete data']);
}
?>
