<?php
include 'header.php';
require 'includes/database.php';  // Make sure the database connection is available

// Get the post ID from the URL
$post_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Fetch the post from the database
if ($post_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($post) {
        echo "<h1>" . htmlspecialchars($post['title']) . "</h1>";
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
