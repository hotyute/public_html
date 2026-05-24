<?php
include 'header.php';
require 'includes/database.php';
require 'includes/sanitize.php'; // Include the sanitization function
require_once __DIR__ . '/includes/content_helpers.php';
require_once __DIR__ . '/includes/article_audio.php';

$post_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;
$page = max(1, (int)(filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1));
$canEditPosts = app_can_edit_posts();

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

function render_comment_text(string $content): string
{
    $decoded = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return nl2br(htmlspecialchars($decoded, ENT_QUOTES, 'UTF-8'));
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

    if (($_SESSION['user_id'] ?? null) == $comment_owner_id || ($_SESSION['user_role'] ?? '') === 'admin') {
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

    if (($_SESSION['user_id'] ?? null) == $comment_owner_id || ($_SESSION['user_role'] ?? '') === 'admin') {
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
        $viewStmt = $pdo->prepare("UPDATE posts SET views = views + 1 WHERE id = ?");
        $viewStmt->execute([$post_id]);
        $_SESSION['viewed_posts'][] = $post_id;
    }

    $stmt = $pdo->prepare("SELECT posts.title, posts.content, posts.thumbnail, posts.thumbnail_style, posts.voiceover_url,
                                  COALESCE(users.displayname, 'Unknown') AS author,
                                  COALESCE(users.role, 'member') AS user_role,
                                  posts.views
                           FROM posts 
                           LEFT JOIN users ON posts.user_id = users.id
                           WHERE posts.id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($post) {
        $content = sanitize_html2(htmlspecialchars_decode($post['content']));
        $pages = explode('<!-- pagebreak -->', $content);
        $total_pages = count($pages);
        $content_page = isset($pages[$page - 1]) ? $pages[$page - 1] : '';
        $userClass = getUserClass($post['user_role']);

        echo '<div class="post-container" data-inline-post data-post-id="' . (int)$post_id . '" data-page="' . (int)$page . '">';
        if ($canEditPosts) {
            echo '<div class="admin-inline-toolbar">';
            echo '<button type="button" class="js-edit-post" data-post-id="' . (int)$post_id . '">Edit This Article</button>';
            echo '<a class="button secondary-button" href="/admin/edit_post.php?post_id=' . (int)$post_id . '">Advanced Editor</a>';
            echo '</div>';
        }
        echo '<h1 class="post-title" data-edit-field="title">' . htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8') . '</h1>';
        echo '<h4 class="post-author">By <span class="' . $userClass . '">' . htmlspecialchars($post['author'], ENT_QUOTES, 'UTF-8') . '</span> | Views: ' . htmlspecialchars($post['views'], ENT_QUOTES, 'UTF-8') . '</h4>';
        if ($post['thumbnail']) {
            $thumbnailStyle = app_safe_image_style($post['thumbnail_style'] ?? '');
            echo '<img src="' . htmlspecialchars($post['thumbnail'], ENT_QUOTES, 'UTF-8') . '" alt="Post Image" class="post-thumbnail" data-edit-image' . ($thumbnailStyle !== '' ? ' style="' . htmlspecialchars($thumbnailStyle, ENT_QUOTES, 'UTF-8') . '"' : '') . '>';
        } elseif ($canEditPosts) {
            echo '<img src="/images/thumbnail.png" alt="Post image placeholder" class="post-thumbnail post-thumbnail--placeholder" data-edit-image>';
        }

        $manifestUrl = article_audio_manifest_url((int)$post_id);
        $manifestPath = article_audio_manifest_path((int)$post_id);
        if (article_audio_manifest_needs_refresh((int)$post_id) && !empty($post['voiceover_url'])) {
            article_audio_generate_for_post($pdo, (int)$post_id, $content);
        }
        if (file_exists($manifestPath) || !empty($post['voiceover_url'])) {
            $nextReaderUrl = $page < $total_pages ? '/post.php?id=' . (int)$post_id . '&page=' . ($page + 1) . '&autoplay=1' : '';
            $autoplayReader = (filter_input(INPUT_GET, 'autoplay', FILTER_VALIDATE_INT) ?: 0) === 1 ? '1' : '0';

            echo '<section class="article-reader" data-reader data-manifest="' . htmlspecialchars($manifestUrl, ENT_QUOTES, 'UTF-8') . '" data-page="' . (int)$page . '" data-total-pages="' . (int)$total_pages . '" data-next-url="' . htmlspecialchars($nextReaderUrl, ENT_QUOTES, 'UTF-8') . '" data-autoplay="' . $autoplayReader . '">';
            echo '<div class="article-reader__top">';
            echo '<strong>Article Reader</strong>';
            echo '<span>Page ' . (int)$page . ' of ' . (int)$total_pages . '</span>';
            echo '</div>';
            echo '<div class="article-reader__controls">';
            echo '<button type="button" data-reader-play>Play</button>';
            echo '<button type="button" class="secondary-button" data-reader-pause>Pause</button>';
            echo '<label>Speed <select data-reader-rate><option value="0.85">0.85x</option><option value="1" selected>1x</option><option value="1.15">1.15x</option><option value="1.3">1.3x</option></select></label>';
            echo '</div>';
            echo '<div class="article-reader__progress"><span data-reader-progress></span></div>';
            echo '<p class="article-reader__status" data-reader-status>Ready.</p>';
            echo '<audio id="post-audio-player" preload="metadata"></audio>';
            echo '</section>';
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
            echo '<input type="hidden" name="post_id" value="' . (int)$post_id . '">';
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
                                        WHERE comments.post_id = ? AND comments.parent_id IS NULL
                                        ORDER BY comments.created_at ASC");
        $comments_stmt->execute([$post_id]);

        $hasComments = false;
        while ($comment = $comments_stmt->fetch(PDO::FETCH_ASSOC)) {
            $hasComments = true;
            $commentUserClass = getUserClass($comment['user_role']);
            $timeAgo = time_ago($comment['created_at']);

            echo '<div class="comment" data-comment-id="' . htmlspecialchars($comment['id'], ENT_QUOTES, 'UTF-8') . '">';
            echo '<strong class="' . $commentUserClass . '">' . htmlspecialchars($comment['author'], ENT_QUOTES, 'UTF-8') . '</strong> <span class="time-ago">' . $timeAgo . '</span>';
            echo '<p class="comment-content">' . render_comment_text($comment['content']) . '</p>';

            // Display edit and delete buttons if the user is the comment owner or an admin
            if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $comment['user_id'] || ($_SESSION['user_role'] ?? '') === 'admin' || ($_SESSION['user_role'] ?? '') === 'owner')) {
                echo '<button type="button" class="editComment" data-comment-id="' . htmlspecialchars($comment['id'], ENT_QUOTES, 'UTF-8') . '">Edit</button>';
                echo '<button type="button" class="deleteComment" data-comment-id="' . htmlspecialchars($comment['id'], ENT_QUOTES, 'UTF-8') . '">Delete</button>';
            }

            // Display reply form for logged-in users
            if (isset($_SESSION['user_id'])) {
                echo '<form class="reply-form">';
                echo '<textarea oninput="autoExpand(this)" required placeholder="Reply to this comment..."></textarea>';
                echo '<input type="hidden" name="post_id" value="' . (int)$post_id . '">';
                echo '<input type="hidden" name="csrf_token" value="' . $csrf_token . '">';
                echo '<button type="button" class="submitReply" style="display: block;" data-parent-id="' . htmlspecialchars($comment['id'], ENT_QUOTES, 'UTF-8') . '">Reply</button>';
                echo '</form>';
            }

            // Fetch and display replies to this comment
            $replies_stmt = $pdo->prepare("SELECT comments.id, comments.content, comments.user_id, comments.created_at, users.displayname AS author, users.role AS user_role 
                                           FROM comments 
                                           JOIN users ON comments.user_id = users.id 
                                           WHERE comments.parent_id = ?
                                           ORDER BY comments.created_at ASC");
            $replies_stmt->execute([$comment['id']]);

            while ($reply = $replies_stmt->fetch(PDO::FETCH_ASSOC)) {
                $replyTimeAgo = time_ago($reply['created_at']);
                $replyUserClass = getUserClass($reply['user_role']);

                echo '<div class="comment reply" data-comment-id="' . htmlspecialchars($reply['id'], ENT_QUOTES, 'UTF-8') . '">';
                echo '<strong class="' . $replyUserClass . '">' . htmlspecialchars($reply['author'], ENT_QUOTES, 'UTF-8') . '</strong> <span class="time-ago">' . $replyTimeAgo . '</span>';
                echo '<p class="comment-content">' . render_comment_text($reply['content']) . '</p>';

                // Display edit and delete buttons for replies if the user is the owner or an admin
                if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $reply['user_id'] || ($_SESSION['user_role'] ?? '') === 'admin')) {
                    echo '<button type="button" class="editComment" data-comment-id="' . htmlspecialchars($reply['id'], ENT_QUOTES, 'UTF-8') . '">Edit</button>';
                    echo '<button type="button" class="deleteComment" data-comment-id="' . htmlspecialchars($reply['id'], ENT_QUOTES, 'UTF-8') . '">Delete</button>';
                }

                echo '</div>';
            }

            echo '</div>'; // Close original comment div
        }

        if (!$hasComments) {
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
