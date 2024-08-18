<?php
include 'header.php';
require 'includes/database.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$post_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

function getUserClass($user_role) {
    switch ($user_role) {
        case 'admin':
        case 'owner':
            return 'admin-owner';
        case 'editor':
            return 'editor-user';
        default:
            return 'regular-user';
    }
}

function time_ago($datetime)
{
    $time = strtotime($datetime);
    $time_difference = time() - $time;

    if ($time_difference < 1) {
        return 'just now';
    }
    $condition = array(
        12 * 30 * 24 * 60 * 60 => 'year',
        30 * 24 * 60 * 60 => 'month',
        24 * 60 * 60 => 'day',
        60 * 60 => 'hour',
        60 => 'minute',
        1 => 'second'
    );

    foreach ($condition as $secs => $str) {
        $d = $time_difference / $secs;

        if ($d >= 1) {
            $r = round($d);
            return $r . ' ' . $str . ($r > 1 ? 's' : '') . ' ago';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_comment']) && isset($_POST['comment_id'])) {
    $comment_id = $_POST['comment_id'];
    $comment_owner_query = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
    $comment_owner_query->execute([$comment_id]);
    $comment_owner_id = $comment_owner_query->fetchColumn();

    if ($_SESSION['user_id'] == $comment_owner_id || $_SESSION['user_role'] === 'admin') {
        $delete_stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
        if ($delete_stmt->execute([$comment_id])) {
            echo "Comment deleted successfully!";
        } else {
            echo "Failed to delete comment.";
        }
    } else {
        echo "You do not have permission to delete this comment.";
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_comment']) && isset($_POST['comment_id']) && isset($_POST['content'])) {
    $comment_id = $_POST['comment_id'];
    $content = htmlspecialchars($_POST['content']);
    $comment_owner_query = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
    $comment_owner_query->execute([$comment_id]);
    $comment_owner_id = $comment_owner_query->fetchColumn();

    if ($_SESSION['user_id'] == $comment_owner_id || $_SESSION['user_role'] === 'admin') {
        $update_stmt = $pdo->prepare("UPDATE comments SET content = ? WHERE id = ?");
        if ($update_stmt->execute([$content, $comment_id])) {
            echo "Comment updated successfully!";
        } else {
            echo "Failed to update comment.";
        }
    } else {
        echo "You do not have permission to edit this comment.";
    }
    exit;
}

if ($post_id > 0) {
    if (!isset($_SESSION['viewed_posts'])) {
        $_SESSION['viewed_posts'] = [];
    }

    if (!in_array($post_id, $_SESSION['viewed_posts'])) {
        $pdo->exec("UPDATE posts SET views = views + 1 WHERE id = $post_id");
        $_SESSION['viewed_posts'][] = $post_id;
    }

    $stmt = $pdo->prepare(" SELECT posts.title, posts.content, posts.thumbnail, posts.voiceover_url, users.displayname AS author, users.role AS user_role, posts.views FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);;

    if ($post) {
        $content = htmlspecialchars_decode($post['content']);
        $pages = explode('<!-- pagebreak -->', $content);
        $total_pages = count($pages);
        $content_page = isset($pages[$page - 1]) ? $pages[$page - 1] : '';
        $userClass = getUserClass($post['user_role']);


        echo '<div class="post-container">';
        echo '<h1 class="post-title">' . htmlspecialchars_decode($post['title']) . '</h1>';
        echo '<h4 class="post-author">By <span class="' . $userClass . '">' . htmlspecialchars_decode($post['author']) . '</span> | Views: ' . $post['views'] . '</h4>';
        if ($post['thumbnail']) {
            echo '<img src="' . $post['thumbnail'] . '" alt="Post Image" class="post-thumbnail">';
        }

        // Check if there is a voiceover URL and display the player
        if (!empty($post['voiceover_url'])) {
            echo '<div class="post-voiceover">';
            echo '<audio controls>';
            echo '<source src="' . $post['voiceover_url'] . '" type="audio/mpeg">';
            echo 'Your browser does not support the audio element.';
            echo '</audio>';
            echo '</div>';
        }

        echo '<div class="post-content">' . nl2br($content_page) . '</div>';

        // Pagination controls
        echo '<div class="pagination" style="display: flex; justify-content: space-between; align-items: center;">';
        if ($page > 1) {
            echo '<a href="post.php?id=' . $post_id . '&page=' . ($page - 1) . '">Previous</a>';
        } else {
            echo '<span></span>';
        }
        echo '<span>Page ' . $page . ' of ' . $total_pages . '</span>';
        if ($page < $total_pages) {
            echo '<a href="post.php?id=' . $post_id . '&page=' . ($page + 1) . '">Next</a>';
        }
        echo '</div>';

        if (isset($_SESSION['user_id'])) {
            echo '<form id="commentForm" class="comment-form">';
            echo '<textarea name="comment" required placeholder="Add a comment..."></textarea>';
            echo '<button type="button" id="submitComment">Comment</button>';
            echo '</form>';
        } else {
            echo '<p>Please <a href="login.php">Login</a> to make a comment.</p>';
        }

        echo '<h3 class="comments-title">Comments</h3>';
        echo '<div class="comments-section" id="commentsSection">';

        $comments_stmt = $pdo->prepare("SELECT comments.id, comments.content, comments.user_id, comments.created_at, users.displayname AS author, users.role AS user_role FROM comments 
        JOIN users ON comments.user_id = users.id 
        WHERE comments.post_id = ? AND comments.parent_id IS NULL
    ");
        $comments_stmt->execute([$post_id]);

        while ($comment = $comments_stmt->fetch(PDO::FETCH_ASSOC)) {
            $commentUserClass = getUserClass($comment['user_role']);
            $timeAgo = time_ago($comment['created_at']);
            $userClass = ($comment['user_role'] === 'admin' || $comment['user_role'] === 'owner') ? 'admin-owner' : 'regular-user';

            echo '<div class="comment" data-comment-id="' . $comment['id'] . '">';
            echo '<strong class="' . $commentUserClass . '">' . htmlspecialchars_decode($comment['author']) . '</strong> <span class="time-ago">' . time_ago($comment['created_at']) . '</span>';
            echo '<p class="comment-content">' . htmlspecialchars_decode($comment['content']) . '</p>';

            // Display edit and delete buttons if the user is the comment owner or an admin
            if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $comment['user_id'] || $_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'owner')) {
                echo '<button type="button" class="editComment" data-comment-id="' . $comment['id'] . '">Edit</button>';
                echo '<button type="button" class="deleteComment" data-comment-id="' . $comment['id'] . '">Delete</button>';
            }

            // Display reply form for logged-in users
            if (isset($_SESSION['user_id'])) {
                echo '<form class="reply-form">';
                echo '<textarea required placeholder="Reply to this comment..."></textarea>';
                echo '<button type="button" class="submitReply" style="display: block;" data-parent-id="' . $comment['id'] . '">Reply</button>';
                echo '</form>';
            }

            // Fetch and display replies to this comment
            $replies_stmt = $pdo->prepare("
            SELECT comments.id, comments.content, comments.user_id, comments.created_at, users.displayname AS author, users.role AS user_role 
            FROM comments 
            JOIN users ON comments.user_id = users.id 
            WHERE comments.parent_id = ?
        ");
            $replies_stmt->execute([$comment['id']]);

            while ($reply = $replies_stmt->fetch(PDO::FETCH_ASSOC)) {
                $replyTimeAgo = time_ago($reply['created_at']);
                $replyUserClass = ($reply['user_role'] === 'admin' || $reply['user_role'] === 'owner') ? 'admin-owner' : 'regular-user';

                echo '<div class="comment reply" data-comment-id="' . $reply['id'] . '">';
                echo '<strong class="' . $replyUserClass . '">' . htmlspecialchars_decode($reply['author']) . '</strong> <span class="time-ago">' . $replyTimeAgo . '</span>';
                echo '<p class="comment-content">' . htmlspecialchars_decode($reply['content']) . '</p>';

                // Display edit and delete buttons for replies if the user is the owner or an admin
                if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $reply['user_id'] || $_SESSION['user_role'] === 'admin')) {
                    echo '<button type="button" class="editComment" data-comment-id="' . $reply['id'] . '">Edit</button>';
                    echo '<button type="button" class="deleteComment" data-comment-id="' . $reply['id'] . '">Delete</button>';
                }

                echo '</div>';
            }

            echo '</div>'; // Close original comment div
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
        // Handle main comment submission
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
                        const noCommentsMsg = commentsSection.querySelector('p');
                        if (noCommentsMsg && noCommentsMsg.textContent === 'No Comments Yet.') {
                            commentsSection.removeChild(noCommentsMsg);
                        }

                        const newComment = document.createElement('div');
                        newComment.classList.add('comment');
                        newComment.innerHTML = `<strong>You</strong><span class="time-ago">just now</span><p class="comment-content">${commentText}</p>`;
                        commentsSection.appendChild(newComment);

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

        // Handle reply submission
        document.querySelectorAll('.submitReply').forEach(function(button) {
            button.addEventListener('click', function() {
                const replyForm = this.closest('.reply-form');
                const replyText = replyForm.querySelector('textarea').value;
                const parentId = this.dataset.parentId;

                if (!replyText) {
                    alert('Please enter a reply.');
                    return;
                }

                const formData = new FormData();
                formData.append('comment', replyText);
                formData.append('user_id', <?php echo json_encode($_SESSION['user_id']); ?>);
                formData.append('post_id', <?php echo $post_id; ?>);
                formData.append('parent_id', parentId);

                fetch('/includes/comments/submit_comment.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const replySection = replyForm.parentElement;
                            const newReply = document.createElement('div');
                            newReply.classList.add('comment', 'reply');
                            newReply.innerHTML = `<strong>You</strong><span class="time-ago">just now</span><p class="comment-content">${replyText}</p>`;
                            replySection.appendChild(newReply);

                            replyForm.querySelector('textarea').value = '';
                        } else {
                            alert('Failed to add reply.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error submitting reply.');
                    });
            });
        });

        // Handle comment deletion
        document.querySelectorAll('.deleteComment').forEach(function(button) {
            button.addEventListener('click', function() {
                const commentId = this.dataset.commentId;

                if (confirm('Are you sure you want to delete this comment?')) {
                    const formData = new FormData();
                    formData.append('comment_id', commentId);
                    formData.append('delete_comment', true);

                    fetch('post.php?id=<?php echo $post_id; ?>', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.text())
                        .then(data => {
                            if (data.includes('Comment deleted successfully!')) {
                                const commentElement = document.querySelector(`[data-comment-id="${commentId}"]`);
                                if (commentElement) {
                                    commentElement.remove();
                                }
                            } else {
                                alert('Failed to delete comment.');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error deleting comment.');
                        });
                }
            });
        });

        // Handle comment editing
        document.querySelectorAll('.editComment').forEach(function(button) {
            button.addEventListener('click', function() {
                const commentId = this.dataset.commentId;
                const commentElement = document.querySelector(`[data-comment-id="${commentId}"]`);
                const commentContentElement = commentElement.querySelector('.comment-content');

                // Convert the comment text to an editable div (contenteditable)
                commentContentElement.setAttribute('contenteditable', 'true');
                commentContentElement.focus();

                // Change the edit button to a save button
                this.textContent = 'Save';
                this.classList.add('saveEdit');

                // Handle the save action
                this.addEventListener('click', function() {
                    const newText = commentContentElement.textContent.trim();

                    const formData = new FormData();
                    formData.append('comment_id', commentId);
                    formData.append('edit_comment', true);
                    formData.append('content', newText);

                    fetch('post.php?id=<?php echo $post_id; ?>', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.text())
                        .then(data => {
                            if (data.includes('Comment updated successfully!')) {
                                // Remove contenteditable attribute
                                commentContentElement.removeAttribute('contenteditable');

                                // Change the save button back to an edit button
                                this.textContent = 'Edit';
                                this.classList.remove('saveEdit');
                            } else {
                                alert('Failed to update comment.');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error updating comment.');
                        });
                }, {
                    once: true
                }); // Ensure the event listener runs only once
            });
        });
    });
</script>