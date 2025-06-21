<?php
include 'header.php';
require 'includes/database.php';
require 'includes/sanitize.php'; // Include the sanitization function

$post_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;
$page = isset($_GET['page']) ? filter_var($_GET['page'], FILTER_VALIDATE_INT) : 1;

function getUserClass($user_role)
{
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
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }

    $comment_id = filter_var($_POST['comment_id'], FILTER_VALIDATE_INT);
    $comment_owner_query = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
    $comment_owner_query->execute([$comment_id]);
    $comment_owner_id = $comment_owner_query->fetchColumn();

    if ($_SESSION['user_id'] == $comment_owner_id || $_SESSION['user_role'] === 'admin') {
        $delete_stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
        if ($delete_stmt->execute([$comment_id])) {
            echo "Comment deleted successfully!";
        } else {
            error_log("Failed to delete comment ID $comment_id by user ID {$_SESSION['user_id']}");
            echo "An error occurred. Please try again later.";
        }
    } else {
        echo "You do not have permission to delete this comment.";
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_comment']) && isset($_POST['comment_id']) && isset($_POST['content'])) {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }

    $comment_id = filter_var($_POST['comment_id'], FILTER_VALIDATE_INT);
    $content = htmlspecialchars(trim($_POST['content']), ENT_QUOTES, 'UTF-8');
    $comment_owner_query = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
    $comment_owner_query->execute([$comment_id]);
    $comment_owner_id = $comment_owner_query->fetchColumn();

    if ($_SESSION['user_id'] == $comment_owner_id || $_SESSION['user_role'] === 'admin') {
        $update_stmt = $pdo->prepare("UPDATE comments SET content = ? WHERE id = ?");
        if ($update_stmt->execute([$content, $comment_id])) {
            echo "Comment updated successfully!";
        } else {
            error_log("Failed to update comment ID $comment_id by user ID {$_SESSION['user_id']}");
            echo "An error occurred. Please try again later.";
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

    $stmt = $pdo->prepare("SELECT posts.title, posts.content, posts.thumbnail, posts.voiceover_url, users.displayname AS author, users.role AS user_role, posts.views 
                           FROM posts 
                           JOIN users ON posts.user_id = users.id 
                           WHERE posts.id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($post) {
        $content = sanitize_html2(htmlspecialchars_decode($post['content']));
        $pages = explode('<!-- pagebreak -->', $content);
        $total_pages = count($pages);
        $content_page = isset($pages[$page - 1]) ? $pages[$page - 1] : '';
        $userClass = getUserClass($post['user_role']);

        echo '<div class="post-container">';
        echo '<h1 class="post-title">' . htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') . '</h1>';
        echo '<h4 class="post-author">By <span class="' . $userClass . '">' . htmlspecialchars($post['author'], ENT_QUOTES, 'UTF-8') . '</span> | Views: ' . htmlspecialchars($post['views'], ENT_QUOTES, 'UTF-8') . '</h4>';
        if ($post['thumbnail']) {
            echo '<img src="' . htmlspecialchars($post['thumbnail'], ENT_QUOTES, 'UTF-8') . '" alt="Post Image" class="post-thumbnail">';
        }


        // Check if there is a base voiceover URL
        if (!empty($post['voiceover_url'])) {
            // 1. Deconstruct the original URL to get the base name and extension
            $path_info = pathinfo($post['voiceover_url']);
            $base_filename = $path_info['dirname'] . '/' . $path_info['filename'];
            $extension = $path_info['extension'];

            // 2. Construct the page-specific filename
            // For page 1, we can use the original or the _p1 version for consistency
            $page_specific_filename = $base_filename . '_p' . $page . '.' . $extension;

            // 3. IMPORTANT: Check if the page-specific audio file actually exists on the server
            // Note: This requires the URL path to be a relative server path. 
            // Adjust $_SERVER['DOCUMENT_ROOT'] if your files are stored elsewhere.
            $server_path_to_audio = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($page_specific_filename, '/');

            if (file_exists($server_path_to_audio)) {
                // Construct the VTT file path
                $vtt_filename = $base_filename . '_p' . $page . '.vtt';
                $server_path_to_vtt = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($vtt_filename, '/');

                echo '<div class="post-voiceover">';
                echo '<audio id="post-audio-player" controls>';
                echo '<source src="' . htmlspecialchars($page_specific_filename, ENT_QUOTES, 'UTF-8') . '" type="audio/mpeg">';

                // Add the track element if the VTT file exists
                if (file_exists($server_path_to_vtt)) {
                    echo '<track label="English" kind="subtitles" srclang="en" src="' . htmlspecialchars($vtt_filename, ENT_QUOTES, 'UTF-8') . '" default>';
                }

                echo 'Your browser does not support the audio element.';
                echo '</audio>';
                echo '</div>';
            }
        }

        // Pagination controls
        echo '<div class="pagination" style="display: flex; justify-content: space-between; align-items: center; padding: 35px 0;">';
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

        // We add an ID to the content wrapper to easily target it with JS
        echo '<div id="post-content-wrapper" class="post-content">' . nl2br_skip($content_page) . '</div>';

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
            echo '<textarea oninput="autoExpand(this)" name="comment" required placeholder="Add a comment..."></textarea>';
            echo '<input type="hidden" name="csrf_token" value="' . $csrf_token . '">';
            echo '<button type="button" id="submitComment">Comment</button>';
            echo '</form>';
        } else {
            echo '<p>Please <a href="login.php">Login</a> to make a comment.</p>';
        }

        echo '<h3 class="comments-title">Comments</h3>';
        echo '<div class="comments-section" id="commentsSection">';

        $comments_stmt = $pdo->prepare("SELECT comments.id, comments.content, comments.user_id, comments.created_at, users.displayname AS author, users.role AS user_role 
                                        FROM comments 
                                        JOIN users ON comments.user_id = users.id 
                                        WHERE comments.post_id = ? AND comments.parent_id IS NULL");
        $comments_stmt->execute([$post_id]);

        while ($comment = $comments_stmt->fetch(PDO::FETCH_ASSOC)) {
            $commentUserClass = getUserClass($comment['user_role']);
            $timeAgo = time_ago($comment['created_at']);

            echo '<div class="comment" data-comment-id="' . htmlspecialchars($comment['id'], ENT_QUOTES, 'UTF-8') . '">';
            echo '<strong class="' . $commentUserClass . '">' . htmlspecialchars($comment['author'], ENT_QUOTES, 'UTF-8') . '</strong> <span class="time-ago">' . $timeAgo . '</span>';
            echo '<p class="comment-content">' . nl2br(htmlspecialchars($comment['content'], ENT_QUOTES, 'UTF-8')) . '</p>';

            // Display edit and delete buttons if the user is the comment owner or an admin
            if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $comment['user_id'] || $_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'owner')) {
                echo '<button type="button" class="editComment" data-comment-id="' . htmlspecialchars($comment['id'], ENT_QUOTES, 'UTF-8') . '">Edit</button>';
                echo '<button type="button" class="deleteComment" data-comment-id="' . htmlspecialchars($comment['id'], ENT_QUOTES, 'UTF-8') . '">Delete</button>';
            }

            // Display reply form for logged-in users
            if (isset($_SESSION['user_id'])) {
                echo '<form class="reply-form">';
                echo '<textarea oninput="autoExpand(this)" required placeholder="Reply to this comment..."></textarea>';
                echo '<input type="hidden" name="csrf_token" value="' . $csrf_token . '">';
                echo '<button type="button" class="submitReply" style="display: block;" data-parent-id="' . htmlspecialchars($comment['id'], ENT_QUOTES, 'UTF-8') . '">Reply</button>';
                echo '</form>';
            }

            // Fetch and display replies to this comment
            $replies_stmt = $pdo->prepare("SELECT comments.id, comments.content, comments.user_id, comments.created_at, users.displayname AS author, users.role AS user_role 
                                           FROM comments 
                                           JOIN users ON comments.user_id = users.id 
                                           WHERE comments.parent_id = ?");
            $replies_stmt->execute([$comment['id']]);

            while ($reply = $replies_stmt->fetch(PDO::FETCH_ASSOC)) {
                $replyTimeAgo = time_ago($reply['created_at']);
                $replyUserClass = getUserClass($reply['user_role']);

                echo '<div class="comment reply" data-comment-id="' . htmlspecialchars($reply['id'], ENT_QUOTES, 'UTF-8') . '">';
                echo '<strong class="' . $replyUserClass . '">' . htmlspecialchars($reply['author'], ENT_QUOTES, 'UTF-8') . '</strong> <span class="time-ago">' . $replyTimeAgo . '</span>';
                echo '<p class="comment-content">' . nl2br(htmlspecialchars($reply['content'], ENT_QUOTES, 'UTF-8')) . '</p>';

                // Display edit and delete buttons for replies if the user is the owner or an admin
                if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $reply['user_id'] || $_SESSION['user_role'] === 'admin')) {
                    echo '<button type="button" class="editComment" data-comment-id="' . htmlspecialchars($reply['id'], ENT_QUOTES, 'UTF-8') . '">Edit</button>';
                    echo '<button type="button" class="deleteComment" data-comment-id="' . htmlspecialchars($reply['id'], ENT_QUOTES, 'UTF-8') . '">Delete</button>';
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
        // --- ROBUST HIGHLIGHTING SCRIPT START ---

        const audio = document.getElementById('post-audio-player');
        const contentWrapper = document.getElementById('post-content-wrapper');

        if (audio && contentWrapper) {
            // Wait for all metadata (including the VTT track) to be loaded.
            audio.addEventListener('loadedmetadata', () => {
                // Find the text track. It might not be loaded instantly.
                const track = audio.textTracks[0];
                if (!track) {
                    // This is a rare fallback, but good to have.
                    audio.textTracks.onaddtrack = (e) => setupCues(e.track);
                } else {
                    setupCues(track);
                }
            });
        }

        /**
         * PHASE 1: Prepare the content.
         * This function runs ONCE. It finds all the text from the VTT cues
         * and wraps them in a <span data-cue-id="..."> so we can easily target them later.
         */
        function setupCues(track) {
            // We need to wait for the cues themselves to be available.
            // Using a small delay is a reliable way to ensure this.
            setTimeout(() => {
                if (!track.cues || track.cues.length === 0) {
                    console.error("VTT track loaded, but no cues were found. Check the VTT file format.");
                    return;
                }

                // Give each cue a unique ID if it doesn't have one.
                // VTT cues get a default ID like "1", "2", etc. which is fine.
                // We will use this ID to link the cue to a span.
                for (let i = 0; i < track.cues.length; i++) {
                    const cue = track.cues[i];
                    if (!cue.id) cue.id = `cue-${i+1}`;
                    
                    const treeWalker = document.createTreeWalker(contentWrapper, NodeFilter.SHOW_TEXT);
                    let currentNode;
                    while (currentNode = treeWalker.nextNode()) {
                        const text = currentNode.nodeValue;
                        const index = text.indexOf(cue.text);

                        if (index !== -1) {
                            const range = document.createRange();
                            range.setStart(currentNode, index);
                            range.setEnd(currentNode, index + cue.text.length);

                            const span = document.createElement('span');
                            span.setAttribute('data-cue-id', cue.id);
                            range.surroundContents(span);

                            // We've wrapped this text, so we break to avoid re-wrapping
                            // if the same sentence appears twice.
                            break; 
                        }
                    }
                }

                console.log(`Prepared content by wrapping ${track.cues.length} text cues.`);
                
                // Now that the content is prepared, start the animation phase.
                startHighlighting(track);

            }, 250); // 250ms delay to ensure browser has parsed cues.
        }

        /**
         * PHASE 2: Animate the highlights.
         * This runs continuously but is very lightweight. It only adds/removes a class.
         */
        function startHighlighting(track) {
            track.mode = 'hidden'; // We control the display, not the browser.
            let currentHighlightElement = null;

            audio.addEventListener('timeupdate', () => {
                const activeCue = track.activeCues[0];

                let elementToHighlight = null;
                if (activeCue) {
                    elementToHighlight = contentWrapper.querySelector(`span[data-cue-id="${activeCue.id}"]`);
                }

                // If the element that should be highlighted is different from the one that is, update.
                if (elementToHighlight !== currentHighlightElement) {
                    // Remove highlight from the old element
                    if (currentHighlightElement) {
                        currentHighlightElement.classList.remove('highlight');
                    }
                    // Add highlight to the new element
                    if (elementToHighlight) {
                        elementToHighlight.classList.add('highlight');
                    }
                    // Update our state
                    currentHighlightElement = elementToHighlight;
                }
            });
        }

        // --- HIGHLIGHTING SCRIPT END ---

        const userId = <?php echo isset($_SESSION['user_id']) ? json_encode($_SESSION['user_id']) : 'null'; ?>;

        if (userId) {
            // Handle main comment submission
            document.getElementById('submitComment').addEventListener('click', function() {
                const commentText = document.querySelector('#commentForm textarea').value;
                const csrfToken = document.querySelector('#commentForm input[name="csrf_token"]').value;

                if (!commentText) {
                    alert('Please enter a comment.');
                    return;
                }

                const formData = new FormData();
                formData.append('comment', commentText);
                formData.append('user_id', userId);
                formData.append('post_id', <?php echo $post_id; ?>);
                formData.append('csrf_token', csrfToken);

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
                            alert(data.message);
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
                    const csrfToken = replyForm.querySelector('input[name="csrf_token"]').value;

                    if (!replyText) {
                        alert('Please enter a reply.');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('comment', replyText);
                    formData.append('user_id', userId);
                    formData.append('post_id', <?php echo $post_id; ?>);
                    formData.append('parent_id', parentId);
                    formData.append('csrf_token', csrfToken);

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
                                alert(data.message);
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
                        formData.append('csrf_token', '<?php echo $csrf_token; ?>');

                        fetch('/includes/comments/delete_comment.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    const commentElement = document.querySelector(`[data-comment-id="${commentId}"]`);
                                    if (commentElement) {
                                        commentElement.remove();
                                    }
                                } else {
                                    alert(data.message);
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
                        const csrfToken = '<?php echo $csrf_token; ?>';

                        const formData = new FormData();
                        formData.append('comment_id', commentId);
                        formData.append('edit_comment', true);
                        formData.append('content', newText);
                        formData.append('csrf_token', csrfToken);

                        fetch('/includes/comments/edit_comment.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Remove contenteditable attribute
                                    commentContentElement.removeAttribute('contenteditable');

                                    // Change the save button back to an edit button
                                    this.textContent = 'Edit';
                                    this.classList.remove('saveEdit');
                                } else {
                                    alert(data.message);
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
        } else {
            //alert('User is not logged in. Please log in to comment or reply.');
        }
    });
</script>