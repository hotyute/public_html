
<?php
require '../includes/database.php';  // Ensure the database connection is available

// Handle post submission (simplified example)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['title']) && !empty($_POST['content'])) {
    $title = htmlspecialchars($_POST['title']);
    $content = htmlspecialchars($_POST['content']);
    $insert_stmt = $pdo->prepare("INSERT INTO posts (title, content) VALUES (?, ?)");
    if ($insert_stmt->execute([$title, $content])) {
        echo "<p>Post added successfully!</p>";
    } else {
        echo "<p>Failed to add post.</p>";
    }
}
?>

<?php
include '../header.php'; 
?>
    <h1>Manage Posts</h1>
    <form method="POST" action="posts.php">
        <label for="title">Title:</label>
        <input type="text" id="title" name="title" required><br>
        <label for="content">Content:</label>
        <textarea id="content" name="content" required></textarea><br>
        <button type="submit">Add Post</button>
    </form>
<?php include '../footer.php'; ?>
