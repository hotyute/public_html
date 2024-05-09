<?php
session_start(); // Start the session to access session variables
require '../includes/database.php';  // Ensure the database connection is available

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $title = htmlspecialchars($_POST['title']);
    $content = htmlspecialchars($_POST['content']);
    $user_id = $_SESSION['user_id']; // Fetch the user_id from session
    $thumbnail = null;

    // Handle file upload
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
        $target_directory = "../images/uploads/";
        $target_file = $target_directory . basename($_FILES["thumbnail"]["name"]);
        if (move_uploaded_file($_FILES["thumbnail"]["tmp_name"], $target_file)) {
            $thumbnail = $target_file;
        }
    }

    // Prepare the SQL statement to include user_id
    $insert_stmt = $pdo->prepare("INSERT INTO posts (title, content, user_id, thumbnail) VALUES (?, ?, ?, ?)");
    if ($insert_stmt->execute([$title, $content, $user_id, $thumbnail])) {
        echo "<p>Post added successfully!</p>";
    } else {
        echo "<p>Failed to add post.</p>";
    }
} else {
    echo "<p>Please login to submit posts.</p>"; // Prompt user to log in if session does not contain user_id
}
?>

<?php include '../header.php'; ?>
    <h1>Manage Posts</h1>
    <form method="POST" action="posts.php" enctype="multipart/form-data">
        <label for="title">Title:</label>
        <input type="text" id="title" name="title" required><br>
        <label for="content">Content:</label>
        <textarea id="content" name="content" required></textarea><br>
        <label for="thumbnail">Thumbnail:</label>
        <input type="file" id="thumbnail" name="thumbnail"><br>
        <button type="submit">Add Post</button>
    </form>
<?php include '../footer.php'; ?>
