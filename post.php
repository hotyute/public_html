<?php
include 'header.php';
require 'includes/database.php';

$post_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($post_id > 0) {
    $stmt = $pdo->prepare("SELECT posts.title, posts.content, posts.thumbnail, users.displayname AS author FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($post) {
        echo "<h1>" . htmlspecialchars($post['title']) . "</h1>";
        echo "<h4>By " . htmlspecialchars($post['author']) . "</h4>";
        if ($post['thumbnail']) {
            echo '<img src="' . $post['thumbnail'] . '" alt="Post Image" style="max-width:100%;">';
        }
        echo "<div>" . nl2br(htmlspecialchars($post['content'])) . "</div>";
    } else {
        echo "<p>Post not found.</p>";
    }
} else {
    echo "<p>Invalid post ID.</p>";
}
include 'footer.php';
?>
