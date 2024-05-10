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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
    <div class="main-container">
        <h1>Admin Dashboard</h1>
        <p>Welcome to the admin dashboard. Use the links below to manage the site:</p>
        <ul>
            <li><a href="create_post.php">Create New Post</a></li>
            <!-- Additional links for other admin tasks can be added here -->
        </ul>
    </div>
</body>
</html>

<?php
// Include footer file
include 'footer.php';
?>
