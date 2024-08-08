<?php
// Start the session and check if the user is authenticated and is an admin.
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'editor', 'member'])) {
    header('Location: /login.php'); // Redirect to login if not authenticated as admin.
    exit();
}

// Include header file
include 'header.php';
?>

<div class="uportal-container">
    <h1>Admin Dashboard</h1>
    <p>Welcome to the admin dashboard. Use the links below to manage the site:</p>
    <ul class="uportal-links">
        <li><a href="test_history.php">Test History</a></li>
        <!-- Additional links for other admin tasks can be added here -->
    </ul>
</div>

<?php
// Include footer file
include '../footer.php';
?>