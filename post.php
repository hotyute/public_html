<?php
include 'header.php';
require 'includes/database.php';

$post_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_comment']) && isset($_POST['comment_id'])) {
    // Delete comment
    $comment_id = $_POST['comment_id'];
    $delete_stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
    if ($delete_stmt->execute([$comment_id])) {
        echo "<p>Comment deleted successfully!</p>";
    } else {
        echo "<p>Failed to delete comment.</p>";
    }
}

if ($post_id > 0) {
    $pdo->exec("UPDATE posts SET views = views + 1 WHERE id = $post_id");

    $stmt = $pdo->prepare("SELECT posts.title, posts.content, posts.thumbnail, users.displayname AS author, posts.views FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($post) {
        echo '<div class="post-container">';
        echo '<h1 class="post-title">' . htmlspecialchars($post['title']) . '</h1>';
        echo '<h4 class="post-author">By ' . htmlspecialchars($post['author']) . ' | Views: ' . $post['views'] . '</h4>';
        if ($post['thumbnail']) {
            echo '<img src="' . $post['thumbnail'] . '" alt="Post Image" class="post-thumbnail">';
        }
        echo '<div class="post-content">' . nl2br(htmlspecialchars($post['content'])) . '</div>';

        if (isset($_SESSION['user_id'])) {
            echo '<form id="commentForm">';
            echo '<textarea name="comment" required></textarea>';
            echo '<button type="button" id="submitComment">Submit Comment</button>';
            echo '</form>';
        } else {
            echo '<p>Please <a href="login.php">login</a to comment.</p>';
        }

        echo '<div class="comments-section" id="commentsSection">';
        $comments_stmt = $pdo->prepare("SELECT comments.id, comments.content, users.displayname AS author FROM comments JOIN users ON comments.user_id = users.id WHERE comments.post_id = ?");
        $comments_stmt->execute([$post_id]);
        while ($comment = $comments_stmt->fetch(PDO::FETCH_ASSOC)) {
            echo '<div class="comment">' . htmlspecialchars($comment['content']) . ' - <strong>' . htmlspecialchars($comment['author']) . '</strong>';
            if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
                echo ' <form method="POST" action=""><input type="hidden" name="comment_id" value="' . $comment['id'] . '"><button type="submit" name="delete_comment">Delete</button></form>';
            }
            echo '</div>';
        }
        echo '</div>'; // Close comments section

        echo '</div>'; // Close post container
    } else {
        echo '<p class="post-error">Post not found.</p>';
    }
} else {
    echo '<p class="post-error">Invalid post ID.</p>';
}
include 'footer.php';
?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    document.getElementById('submitComment').addEventListener('click', function() {
        const commentText = document.querySelector('#commentForm textarea').value;
        if (!commentText) {
            alert('Please enter a comment.');
            return;
        }
        
        const formData = new FormData();
        formData.append('comment', commentText);
        formData.append('user_id', <?php echo json_encode($_SESSION['user_id']); ?>);
        formData.append('post_id', <?php echo $post_id; ?>);

        fetch('submit_comment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const commentsSection = document.getElementById('commentsSection');
                const newComment = document.createElement('div');
                newComment.classList.add('comment');
                newComment.innerHTML = `${commentText} - <strong>You</strong>`;
                commentsSection.appendChild(newComment);
                document.querySelector('#commentForm textarea').value = ''; // Clear the textarea after submission
            } else {
                alert('Failed to add comment.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error submitting comment.');
        });
    });
});
</script>
