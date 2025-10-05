<?php
// admin/admin_panel.php
session_start();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'editor'])) {
    header('Location: /login.php');
    exit();
}
include '../header.php';
?>
<div class="admin-container">
    <h1>Admin Dashboard</h1>
    <p>Welcome to the admin dashboard. Use the links below to manage the site:</p>
    <ul class="admin-links">
        <li><a href="create_post.php">Create New Post</a></li>
        <li><a href="edit_post.php">Edit Post</a></li>
        <li><a href="delete_post.php">Delete Post</a></li>
        <li><a href="edit_video.php">Edit Video of the Week</a></li>
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') : ?>
            <li><a href="manage_users.php">Manage Users</a></li>
            <li><a href="test_manage.php">Manage Tests</a></li>
            <li><a href="manage_magazines.php">Manage External Magazines</a></li>
        <?php endif; ?>
    </ul>
</div>
<?php include '../footer.php'; ?>