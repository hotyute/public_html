<?php
require BASE_URL . 'includes/database.php';

$post_id = $_GET['post_id'] ?? 0;
$post = null;

if ($post_id > 0) {
    $stmt = $pdo->prepare("SELECT title, content, thumbnail FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($post);
}
?>
