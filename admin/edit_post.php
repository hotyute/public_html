<?php
session_start();
require '../includes/database.php';

// Fetch all posts for dropdown
$stmt = $pdo->prepare("SELECT id, title, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i') as formatted_date FROM posts ORDER BY created_at DESC");
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_id'])) {
    $post_id = $_POST['post_id'];
    $title = htmlspecialchars($_POST['title']);
    $content = htmlspecialchars($_POST['content']);
    $thumbnail = null;

    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
        $target_directory = "../images/uploads/";
        $target_file = $target_directory . basename($_FILES["thumbnail"]["name"]);
        if (move_uploaded_file($_FILES["thumbnail"]["tmp_name"], $target_file)) {
            $thumbnail = $target_file;
        }
    }

    $update_stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, thumbnail = ? WHERE id = ?");
    if ($update_stmt->execute([$title, $content, $thumbnail, $post_id])) {
        echo "<p>Post updated successfully!</p>";
    } else {
        echo "<p>Failed to update post.</p>";
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
            <label for="thumbnail">Thumbnail (optional):</label>
            <input type="file" id="thumbnail" name="thumbnail" class="form-control">
        </div>
        
        <button type="submit" class="btn btn-primary">Update Post</button>
    </form>
</div>
<script>
function loadPostData(postId) {
    if (postId) {
        fetch('get_post_data.php?post_id=' + postId)
            .then(response => response.json())
            .then(data => {
                document.getElementById('title').value = data.title;
                document.getElementById('content').value = data.content;
                // Handle thumbnail and other fields as needed
            });
    } else {
        document.getElementById('title').value = '';
        document.getElementById('content').value = '';
        // Reset other fields as needed
    }
}
</script>
<?php include '../footer.php'; ?>
