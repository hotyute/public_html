<?php
session_start();
// Check if admin is logged in
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /login.php');
    exit();
}
require_once '../base_config.php';
require 'includes/database.php';
require 'includes/sanitize.php'; // Include the sanitization function

// Fetch all posts for dropdown
$stmt = $pdo->prepare("SELECT id, title, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as formatted_date FROM posts ORDER BY created_at DESC");
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete']) && isset($_POST['post_id'])) {
        // Delete post
        $post_id = $_POST['post_id'];
        // Fetch existing thumbnail to delete the file
        $existing_thumbnail_stmt = $pdo->prepare("SELECT thumbnail FROM posts WHERE id = ?");
        $existing_thumbnail_stmt->execute([$post_id]);
        $existing_thumbnail = $existing_thumbnail_stmt->fetchColumn();

        // Delete the post
        $delete_stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        if ($delete_stmt->execute([$post_id])) {
            // Delete the thumbnail file
            if ($existing_thumbnail && file_exists($existing_thumbnail)) {
                unlink($existing_thumbnail);
            }
            echo "<p>Post deleted successfully!</p>";
        } else {
            echo "<p>Failed to delete post.</p>";
        }
    } else if (isset($_POST['post_id'])) {
        // Update post
        $post_id = $_POST['post_id'];
        $title = sanitize_html($_POST['title']);
        $content = sanitize_html($_POST['content']);

        // Fetch existing thumbnail
        $existing_thumbnail_stmt = $pdo->prepare("SELECT thumbnail FROM posts WHERE id = ?");
        $existing_thumbnail_stmt->execute([$post_id]);
        $existing_thumbnail = $existing_thumbnail_stmt->fetchColumn();

        // Initialize $thumbnail to the existing thumbnail
        $thumbnail = $existing_thumbnail;

        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
            // Delete old thumbnail file if it exists
            if ($existing_thumbnail && file_exists($existing_thumbnail)) {
                unlink($existing_thumbnail);
            }

            // Move the new uploaded file
            $target_directory = "../images/uploads/";

            // Ensure unique file name to avoid overwriting
            $file_extension = pathinfo($_FILES["thumbnail"]["name"], PATHINFO_EXTENSION);
            $new_filename = uniqid('thumb_', true) . '.' . $file_extension;

            $target_file = $target_directory . $new_filename;

            if (move_uploaded_file($_FILES["thumbnail"]["tmp_name"], $target_file)) {
                $thumbnail = $target_file;
            }
        }

        // Update the post in the database
        $update_stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, thumbnail = ? WHERE id = ?");
        if ($update_stmt->execute([$title, $content, $thumbnail, $post_id])) {
            echo "<p>Post updated successfully!</p>";
        } else {
            echo "<p>Failed to update post.</p>";
        }
    }
}

include '../header.php';
?>
<div class="admin-content">
    <h2>Edit Post</h2>
    <form method="POST" action="edit_post.php" enctype="multipart/form-data" class="admin-form">
        <div class="form-group">
            <label for="post_id">Choose a post to edit:</label>
            <select id="post_id" name="post_id" onchange="loadPostData(this.value)" class="form-control">
                <option value="">Select a post</option>
                <?php foreach ($posts as $post): ?>
                    <option value="<?= $post['id'] ?>"><?= htmlspecialchars($post['title']) ?> - <?= $post['formatted_date'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="title">Title:</label>
            <input type="text" id="title" name="title" required class="form-control">
        </div>
        
        <div class="form-group">
            <label for="content">Content:</label>
            <textarea id="content" name="content" rows="10" required class="form-control"></textarea>
        </div>
        
        <div class="form-group">
            <label for="current_thumbnail">Current Thumbnail:</label>
            <div id="current_thumbnail"></div>
        </div>

        <div class="form-group">
            <label for="thumbnail">Thumbnail (optional):</label>
            <input type="file" id="thumbnail" name="thumbnail" class="form-control">
        </div>
        
        <button type="submit" class="btn btn-primary">Update Post</button>
        <button type="submit" name="delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this post?');">Delete Post</button>
    </form>
</div>
<script>
function loadPostData(postId) {
    if (postId) {
        fetch('/includes/posts/get_post_data.php?post_id=' + postId)
            .then(response => response.json())
            .then(data => {
                document.getElementById('title').value = decodeHtmlEntities(data.title);
                document.getElementById('content').value = decodeHtmlEntities(data.content);

                // Handle thumbnail
                var currentThumbnailDiv = document.getElementById('current_thumbnail');
                if (data.thumbnail) {
                    // Adjust the path if necessary
                    var thumbnailPath = data.thumbnail.replace('../', '/');
                    currentThumbnailDiv.innerHTML = '<img src="' + thumbnailPath + '" alt="Current Thumbnail" style="max-width: 200px;">';
                } else {
                    currentThumbnailDiv.innerHTML = 'No thumbnail.';
                }
            });
    } else {
        document.getElementById('title').value = '';
        document.getElementById('content').value = '';
        document.getElementById('current_thumbnail').innerHTML = '';
    }
}

function decodeHtmlEntities(str) {
    var textarea = document.createElement('textarea');
    textarea.innerHTML = str;
    return textarea.value;
}
</script>
<?php include '../footer.php'; ?>
