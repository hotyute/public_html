<?php
session_start();

// Auth: admins only
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /login.php');
    exit();
}

require_once '../base_config.php';
require_once '../includes/database.php';
require_once '../includes/sanitize.php';

// Fetch all posts for dropdown
$stmt = $pdo->prepare("SELECT id, title, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as formatted_date FROM posts ORDER BY created_at DESC");
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle POST (update/delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }

    if (isset($_POST['delete']) && isset($_POST['post_id'])) {
        $post_id = (int)$_POST['post_id'];

        // Delete thumbnail file if any
        $existing_thumbnail_stmt = $pdo->prepare("SELECT thumbnail FROM posts WHERE id = ?");
        $existing_thumbnail_stmt->execute([$post_id]);
        $existing_thumbnail = $existing_thumbnail_stmt->fetchColumn();

        $delete_stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
        if ($delete_stmt->execute([$post_id])) {
            if ($existing_thumbnail && file_exists($existing_thumbnail)) {
                @unlink($existing_thumbnail);
            }
            $status_message = "Post deleted successfully!";
        } else {
            $status_message = "Failed to delete post.";
        }
    } elseif (isset($_POST['post_id'])) {
        $post_id = (int)$_POST['post_id'];
        $title   = sanitize_html($_POST['title'] ?? '');

        // Convert CKEditor pagebreaks (<hr class="pagebreak">) to <!-- pagebreak -->
        $rawContent = $_POST['content'] ?? '';
        $rawContent = preg_replace('/<hr\b[^>]*class="[^"]*\bpagebreak\b[^"]*"[^>]*>/i', '<!-- pagebreak -->', $rawContent);

        // Sanitize but keep formatting
        $content    = sanitize_html2($rawContent);

        // Handle thumbnail (optional)
        $existing_thumbnail_stmt = $pdo->prepare("SELECT thumbnail FROM posts WHERE id = ?");
        $existing_thumbnail_stmt->execute([$post_id]);
        $existing_thumbnail = $existing_thumbnail_stmt->fetchColumn();

        $thumbnail = $existing_thumbnail;

        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            // Validate image
            $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $_FILES['thumbnail']['tmp_name']);
            finfo_close($finfo);

            if (in_array($mime, $allowed, true)) {
                if ($existing_thumbnail && file_exists($existing_thumbnail)) {
                    @unlink($existing_thumbnail);
                }
                $target_directory = "../images/uploads/";
                @mkdir($target_directory, 0755, true);
                $ext = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid('thumb_', true) . '.' . $ext;
                $target_file = $target_directory . $new_filename;

                if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $target_file)) {
                    $thumbnail = $target_file;
                } else {
                    $status_message = "Failed to move uploaded thumbnail.";
                }
            } else {
                $status_message = "Invalid image type.";
            }
        }

        $update_stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, thumbnail = ? WHERE id = ?");
        if ($update_stmt->execute([$title, $content, $thumbnail, $post_id])) {
            $status_message = "Post updated successfully!";
        } else {
            $status_message = "Failed to update post.";
        }
    }
}

include '../header.php';
?>
<style>
  .admin-content { max-width: 1200px; margin: 40px auto; }
  .admin-content .form-group label { font-weight: bold; }
  #current_thumbnail img { max-width: 240px; border-radius: 4px; border: 1px solid #ddd; }
  /* Ensure editor is clickable above other UI */
  .cke { position: relative; z-index: 10; }
</style>

<!-- CKEditor 4 (free, open-source) -->
<script src="https://cdn.ckeditor.com/4.25.1/full-all/ckeditor.js"></script>

<div class="admin-content">
    <h2>Edit Post</h2>
    <?php if (!empty($status_message)): ?>
        <p style="color: <?= strpos($status_message, 'successfully') !== false ? 'green' : 'red' ?>;">
            <?= htmlspecialchars($status_message) ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="edit_post.php" enctype="multipart/form-data" class="admin-form" id="edit-post-form">
        <div class="form-group">
            <label for="post_id">Choose a post to edit:</label>
            <select id="post_id" name="post_id" onchange="loadPostData(this.value)" class="form-control">
                <option value="">Select a post</option>
                <?php foreach ($posts as $post): ?>
                    <option value="<?= (int)$post['id'] ?>"><?= htmlspecialchars($post['title']) ?> - <?= $post['formatted_date'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="title">Title:</label>
            <input type="text" id="title" name="title" required class="form-control">
        </div>

        <div class="form-group">
            <label for="content">Content (Bold/Italic/Underline, Colors/Highlight, Fonts, Alignment, Lists, Tables, Links, Page Breaks, Fullscreen):</label>
            <textarea id="content" name="content" rows="18"></textarea>
        </div>

        <div class="form-group">
            <label for="current_thumbnail">Current Thumbnail:</label>
            <div id="current_thumbnail">No thumbnail.</div>
        </div>

        <div class="form-group">
            <label for="thumbnail">Thumbnail (optional):</label>
            <input type="file" id="thumbnail" name="thumbnail" class="form-control" accept="image/*">
        </div>

        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <button type="submit" class="btn btn-primary">Update Post</button>
        <button type="submit" name="delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this post?');">Delete Post</button>
    </form>
</div>

<script>
// Transform between server format and editor format for page breaks
function serverToEditor(html) {
  // Convert <!-- pagebreak --> to a visible rule for CKEditor
  return html.replace(/<!--\s*pagebreak\s*-->/gi, '<hr class="pagebreak" />');
}
function editorToServer(html) {
  // Convert CKEditor pagebreaks back to <!-- pagebreak -->
  return html.replace(/<hr\b[^>]*class="[^"]*\bpagebreak\b[^"]*"[^>]*>/gi, '<!-- pagebreak -->');
}

// Initialize CKEditor with a Word-like toolbar
CKEDITOR.replace('content', {
  height: 650,
  extraPlugins: 'pagebreak,colorbutton,font,justify',
  removePlugins: 'elementspath',
  resize_enabled: true,
  allowedContent: true, // keep formatting; server still sanitizes
  toolbar: [
    { name: 'document', items: ['Source','Preview','Maximize','ShowBlocks'] },
    { name: 'clipboard', items: ['Undo','Redo'] },
    { name: 'styles', items: ['Styles','Format','Font','FontSize'] },
    { name: 'basicstyles', items: ['Bold','Italic','Underline','Strike','Subscript','Superscript','RemoveFormat'] },
    { name: 'colors', items: ['TextColor','BGColor'] },
    { name: 'paragraph', items: ['NumberedList','BulletedList','Outdent','Indent','Blockquote','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'] },
    { name: 'insert', items: ['Table','HorizontalRule','PageBreak','Link','Unlink'] }
  ],
});

// Load post data into editor (converting pagebreak comments to visible rules)
function loadPostData(postId) {
    if (!postId) {
        document.getElementById('title').value = '';
        if (CKEDITOR.instances.content) CKEDITOR.instances.content.setData('');
        document.getElementById('current_thumbnail').innerHTML = 'No thumbnail.';
        return;
    }
    fetch('/includes/posts/get_post_data.php?post_id=' + encodeURIComponent(postId))
        .then(response => response.json())
        .then(data => {
            document.getElementById('title').value = decodeHtmlEntities(data.title || '');
            const html = serverToEditor(decodeHtmlEntities(data.content || ''));
            if (CKEDITOR.instances.content) CKEDITOR.instances.content.setData(html);

            var currentThumbnailDiv = document.getElementById('current_thumbnail');
            if (data.thumbnail) {
                var thumbnailPath = data.thumbnail.replace('../', '/');
                currentThumbnailDiv.innerHTML = '<img src="' + thumbnailPath + '" alt="Current Thumbnail">';
            } else {
                currentThumbnailDiv.innerHTML = 'No thumbnail.';
            }
        });
}

function decodeHtmlEntities(str) {
    var textarea = document.createElement('textarea');
    textarea.innerHTML = str || '';
    return textarea.value;
}

// On submit: convert pagebreak rules to <!-- pagebreak --> and move content into the textarea
document.getElementById('edit-post-form').addEventListener('submit', function() {
    if (CKEDITOR.instances.content) {
        var html = CKEDITOR.instances.content.getData();
        html = editorToServer(html);
        document.getElementById('content').value = html; // set textarea value
    }
});
</script>
<?php include '../footer.php'; ?>