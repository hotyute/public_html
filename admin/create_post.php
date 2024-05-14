<?php
session_start();
require '../includes/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'admin') {
    $title = htmlspecialchars($_POST['title']);
    $content = htmlspecialchars($_POST['content']);
    $user_id = $_SESSION['user_id'];
    $thumbnail = null;

    // Handle file upload
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
        $target_directory = "../images/uploads/";
        $target_file = $target_directory . basename($_FILES["thumbnail"]["name"]);
        if (move_uploaded_file($_FILES["thumbnail"]["tmp_name"], $target_file)) {
            $thumbnail = $target_file;
        } else {
            echo "<p>Error Thumbnail: Failed to move uploaded file.</p>";
        }
    } else {
        echo "<p>Error Thumbnail: " . $_FILES['thumbnail']['error'] . "</p>";
    }

    // Prepare the SQL statement to include user_id
    $insert_stmt = $pdo->prepare("INSERT INTO posts (title, content, user_id, thumbnail) VALUES (?, ?, ?, ?)");
    if ($insert_stmt->execute([$title, $content, $user_id, $thumbnail])) {
        echo "<p>Post added successfully!</p>";
    } else {
        echo "<p>Failed to add post.</p>";
    }
} else {
    echo "<p>Please login to submit posts.</p>";
}
?>

<?php include '../header.php'; ?>
<div class="admin-content">
    <h2 style="text-align: center;">Create New Post</h2>
    <form class="admin-form" method="POST" action="create_post.php" enctype="multipart/form-data">
        <label for="title">Title:</label>
        <input type="text" id="title" name="title" required><br>
        <label for="content">Content:</label>
        <textarea id="content" name="content" rows="10" required></textarea><br>
        <label for="thumbnail">Thumbnail:</label>
        <input type="file" id="thumbnail" name="thumbnail"><br>
        <input type="submit" value="Create Post">
    </form>
</div>

<?php
// Fetch and display the post
$stmt = $pdo->prepare("SELECT title, content FROM posts ORDER BY created_at DESC LIMIT 1");
$stmt->execute();
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if ($post) {
    $decoded_title = htmlspecialchars_decode($post['title']);
    $decoded_content = htmlspecialchars_decode($post['content']);
    echo "<h2>{$decoded_title}</h2>";
    echo "<p>{$decoded_content}</p>";
}
?>
