<?php
// Start the session and check if the user is authenticated and is an admin.
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php'); // Redirect to login if not authenticated as admin.
    exit();
}

// Include header file
include '../header.php';
?>

<body>
    <div class="admin-container">
        <h1>Admin Dashboard</h1>
        <p>Welcome to the admin dashboard. Use the links below to manage the site:</p>
        <ul>
            <li><a href="create_post.php">Create New Post</a></li>
            <li><a href="edit_post.php">Edit Post</a></li>
            <!-- Additional links for other admin tasks can be added here -->
        </ul>
    </div>
</body>

<?php
// Include footer file
include '../footer.php';
?>