<?php
include 'header.php';
require 'includes/database.php';

$post_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($post_id > 0) {
    $stmt = $pdo->prepare("SELECT posts.title, posts.content, posts.thumbnail, users.displayname AS author FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($post) {
        echo '<div class="post-container">';
        echo '<h1 class="post-title">' . htmlspecialchars($post['title']) . '</h1>';
        echo '<h4 class="post-author">By ' . htmlspecialchars($post['author']) . '</h4>';
        if ($post['thumbnail']) {
            echo '<img src="' . $post['thumbnail'] . '" alt="Post Image" class="post-thumbnail">';
        }
        echo '<div class="post-content">' . nl2br(htmlspecialchars($post['content'])) . '</div>';
        echo '</div>';
    } else {
        echo '<p class="post-error">Post not found.</p>';
    }
} else {
    echo '<p class="post-error">Invalid post ID.</p>';
}
include 'footer.php';
?>
