<?php
include 'header.php';
require 'includes/database.php';

$post_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$posts_per_page = 1000;  // Number of characters per page

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_comment']) && isset($_POST['comment_id'])) {
    // Get the user_id of the comment owner
    $comment_id = $_POST['comment_id'];
    $comment_owner_query = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
    $comment_owner_query->execute([$comment_id]);
    $comment_owner_id = $comment_owner_query->fetchColumn();

    // Check if logged-in user is the comment owner or an admin
    if ($_SESSION['user_id'] == $comment_owner_id || $_SESSION['user_role'] === 'admin') {
        $delete_stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
        if ($delete_stmt->execute([$comment_id])) {
            echo "<p>Comment deleted successfully!</p>";
        } else {
            echo "<p>Failed to delete comment.</p>";
        }
    } else {
        echo "<p>You do not have permission to delete this comment.</p>";
    }
}

if ($post_id > 0) {
    $pdo->exec("UPDATE posts SET views = views + 1 WHERE id = $post_id");

    $stmt = $pdo->prepare("SELECT posts.title, posts.content, posts.thumbnail, users.displayname AS author, posts.views FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($post) {
        $content = htmlspecialchars_decode($post['content']);
        $total_pages = ceil(strlen($content) / $posts_per_page);
        $start = ($page - 1) * $posts_per_page;
        $content_page = substr($content, $start, $posts_per_page);

        echo '<div class="post-container">';
        echo '<h1 class="post-title">' . htmlspecialchars_decode($post['title']) . '</h1>';
        echo '<h4 class="post-author">By ' . htmlspecialchars_decode($post['author']) . ' | Views: ' . $post['views'] . '</h4>';
        if ($post['thumbnail']) {
            echo '<img src="' . $post['thumbnail'] . '" alt="Post Image" class="post-thumbnail">';
        }
        echo '<div class="post-content">' . nl2br($content_page) . '</div>';

        // Pagination controls
        echo '<div class="pagination">';
        if ($page > 1) {
            echo '<a href="post.php?id=' . $post_id . '&page=' . ($page - 1) . '">Previous</a>';
        }
        if ($page < $total_pages) {
            echo '<a href="post.php?id=' . $post_id . '&page=' . ($page + 1) . '">Next</a>';
        }
        echo '</div>';

        echo '</div>';

        if (isset($_SESSION['user_id'])) {
            echo '<form id="comment_form" method="post" action="submit_comment.php">';
            echo '<textarea name="comment" placeholder="Write your comment..."></textarea>';
            echo '<input type="hidden" name="post_id" value="' . $post_id . '">';
            echo '<button type="submit">Submit Comment</button>';
            echo '</form>';
        }
    } else {
        echo '<p>Post not found.</p>';
    }
} else {
    echo '<p>Invalid post ID.</p>';
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
                // Check if there is a "No Comments Yet" message and remove it
                const noCommentsMsg = commentsSection.querySelector('p');
                if (noCommentsMsg && noCommentsMsg.textContent === 'No Comments Yet.') {
                    commentsSection.removeChild(noCommentsMsg);
                }

                // Create and append the new comment
                const newComment = document.createElement('div');
                newComment.classList.add('comment');
                newComment.innerHTML = `${commentText} - <strong>You</strong>`;
                commentsSection.appendChild(newComment);

                // Clear the textarea after successful submission
                document.querySelector('#commentForm textarea').value = ''; 
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
