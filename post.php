<?php
include 'header.php';
require 'includes/database.php';

// Start the session if it hasn't been started already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$post_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

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
    // Check if the post has been viewed in the current session
    if (!isset($_SESSION['viewed_posts'])) {
        $_SESSION['viewed_posts'] = [];
    }

    if (!in_array($post_id, $_SESSION['viewed_posts'])) {
        $pdo->exec("UPDATE posts SET views = views + 1 WHERE id = $post_id");
        $_SESSION['viewed_posts'][] = $post_id;
    }

    $stmt = $pdo->prepare("SELECT posts.title, posts.content, posts.thumbnail, users.displayname AS author, posts.views FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($post) {
        $content = htmlspecialchars_decode($post['content']);
        $pages = explode('<!-- pagebreak -->', $content);
        $total_pages = count($pages);
        $content_page = isset($pages[$page - 1]) ? $pages[$page - 1] : '';

        echo '<div class="post-container">';
        echo '<h1 class="post-title">' . htmlspecialchars_decode($post['title']) . '</h1>';
        echo '<h4 class="post-author">By ' . htmlspecialchars_decode($post['author']) . ' | Views: ' . $post['views'] . '</h4>';
        if ($post['thumbnail']) {
            echo '<img src="' . $post['thumbnail'] . '" alt="Post Image" class="post-thumbnail">';
        }
        echo '<div class="post-content">' . nl2br($content_page) . '</div>';

        // Pagination controls
        echo '<div class="pagination" style="display: flex; justify-content: space-between; align-items: center;">';
        if ($page > 1) {
            echo '<a href="post.php?id=' . $post_id . '&page=' . ($page - 1) . '">Previous</a>';
        } else {
            echo '<span></span>'; // Empty span to maintain space
        }
        echo '<span>Page ' . $page . ' of ' . $total_pages . '</span>';
        if ($page < $total_pages) {
            echo '<a href="post.php?id=' . $post_id . '&page=' . ($page + 1) . '">Next</a>';
        }
        echo '</div>';

        if (isset($_SESSION['user_id'])) {
            echo '<form id="commentForm" class="comment-form">';
            echo '<textarea name="comment" required></textarea>';
            echo '<button type="button" id="submitComment">Submit Comment</button>';
            echo '</form>';
        } else {
            echo '<p>Please <a href="login.php">Login</a> to make a comment.</p>';
        }

        echo '<h3 class="comments-title">Comments</h3>';
        echo '<div class="comments-section" id="commentsSection">';
        $comments_stmt = $pdo->prepare("SELECT comments.id, comments.content, comments.user_id, users.displayname AS author FROM comments JOIN users ON comments.user_id = users.id WHERE comments.post_id = ?");
        $comments_stmt->execute([$post_id]);
        while ($comment = $comments_stmt->fetch(PDO::FETCH_ASSOC)) {
            echo '<div class="comment">' . htmlspecialchars_decode($comment['content']) . ' - <strong>' . htmlspecialchars_decode($comment['author']) . '</strong>';
            if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $comment['user_id'] || $_SESSION['user_role'] === 'admin')) {
                echo ' <form method="POST" action=""><input type="hidden" name="comment_id" value="' . $comment['id'] . '"><button type="submit" name="delete_comment">Delete</button></form>';
            }
            echo '</div>';
        }
        if ($comments_stmt->rowCount() == 0) {
            echo '<p>No Comments Yet.</p>';
        }
        echo '</div>'; // Close comments section

        echo '</div>'; // Close post container
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

        fetch('/includes/comments/submit_comment.php', {
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
